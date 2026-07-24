FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libzip-dev libpng-dev libonig-dev libxml2-dev libsqlite3-dev libicu-dev \
    default-mysql-client \
    openssh-client sshpass rsync \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring zip xml bcmath intl \
    && rm -rf /var/lib/apt/lists/*

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

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /tmp/php \
    && chown -R www-data:www-data /tmp/php \
    && chmod 1777 /tmp/php

COPY docker/php/zz-backup-manager.ini /usr/local/etc/php/conf.d/zz-backup-manager.ini
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint-app
RUN chmod +x /usr/local/bin/docker-entrypoint-app

WORKDIR /var/www/html

ENTRYPOINT ["/usr/local/bin/docker-entrypoint-app"]
CMD ["php-fpm"]