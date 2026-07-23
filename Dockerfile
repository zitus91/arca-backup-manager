FROM php:8.3-fpm

# System dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libzip-dev libpng-dev libonig-dev libxml2-dev libsqlite3-dev \
    default-mysql-client \
    openssh-client sshpass rsync \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring zip xml bcmath \
    && rm -rf /var/lib/apt/lists/*

# MongoDB Database Tools (mongodump / mongorestore) — not in Debian repos, download from MongoDB CDN
RUN ARCH=$(dpkg --print-architecture) \
    && case "$ARCH" in \
        amd64) TOOLS_ARCH="x86_64" ;; \
        arm64|aarch64) TOOLS_ARCH="arm64" ;; \
        *) echo "Unsupported architecture: $ARCH" && exit 1 ;; \
    esac \
    && curl -fsSL "https://fastdl.mongodb.org/tools/db/mongodb-database-tools-ubuntu2204-${TOOLS_ARCH}-100.17.0.tgz" \
        | tar -xz -C /tmp \
    && cp /tmp/mongodb-database-tools-*/bin/* /usr/local/bin/ \
    && rm -rf /tmp/mongodb-database-tools-*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Install PHP and JS dependencies, build assets
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --optimize-autoloader

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

# storage/* is excluded via .dockerignore; without these dirs, package:discover fails ("Please provide a valid cache path")
RUN mkdir -p storage/framework/cache/data storage/framework/sessions \
    storage/framework/testing storage/framework/views storage/logs bootstrap/cache \
    && npm run build \
    && rm -rf node_modules \
    && composer run-script post-autoload-dump \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
