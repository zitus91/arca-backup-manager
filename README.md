<p align="center">
  <img src="public/images/logo.png" alt="Backup Manager Logo" width="200">
</p>

<h1 align="center">Backup Manager</h1>

<p align="center">
  <strong>Una soluzione web completa per gestire backup e ripristini di database e filesystem.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-blue?logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/Laravel-12-red?logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/Livewire-4-purple" alt="Livewire">
  <img src="https://img.shields.io/badge/TailwindCSS-4-38bdf8?logo=tailwindcss" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/DaisyUI-5-green" alt="DaisyUI">
  <img src="https://img.shields.io/badge/Version-1.1.0-brightgreen" alt="Version">
  <img src="https://img.shields.io/badge/License-MIT-yellow" alt="License">
</p>

<p align="center">
  <img src="docs/images/screenshot-dashboard.png" alt="Dashboard Screenshot" width="800">
</p>

---

## 📋 Indice

- [Panoramica](#-panoramica)
- [Funzionalità](#-funzionalità)
- [Screenshots](#-screenshots)
- [Architettura](#-architettura)
- [Requisiti](#-requisiti)
- [Installazione con Docker (consigliato)](#-installazione-con-docker-consigliato)
- [Installazione su Host](#-installazione-su-host)
- [Configurazione](#-configurazione)
- [Utilizzo](#-utilizzo)
- [Immagini e Asset](#-immagini-e-asset)
- [Testing](#-testing)
- [Struttura del Progetto](#-struttura-del-progetto)
- [Changelog](#-changelog)
- [License](#-license)

---

## 🎯 Panoramica

**Backup Manager** è un'applicazione web costruita con Laravel 12 e Livewire 4 che permette di configurare, schedulare e monitorare backup automatici di database MySQL, MongoDB e filesystem. Supporta destinazioni di storage multiple (locale, S3, FTP) e offre funzionalità di ripristino granulare, audit log e notifiche in tempo reale via WebSocket.

---

## ✨ Funzionalità

### Backup
- **MySQL** — Dump completi con `mysqldump` (single transaction, routines, triggers)
- **MongoDB** — Dump con `mongodump`, supporto autenticazione e collections specifiche
- **Filesystem** — Archiviazione di directory con pattern di esclusione

### Storage Destinations
- **Locale** — Salvataggio su filesystem locale
- **Amazon S3** — Compatibile con qualsiasi storage S3 (AWS, MinIO, DigitalOcean Spaces, ecc.)
- **FTP/SFTP** — Upload su server FTP

### Scheduling
- Pianificazione: manuale, oraria, giornaliera, settimanale, mensile, CRON custom
- Calcolo automatico del prossimo run
- Retention policy configurabile per job

### Ripristino
- Ripristino selettivo: solo database, solo file, o completo
- Ripristino in database/directory con suffisso `_restored_<timestamp>` (non distruttivo)
- Supporto formati compressi (`.gz`, `.zip`, `.tar.gz`)
- **Ripristino su host remoto** — MySQL, MongoDB e filesystem via SSH/rsync
- **Nomi target personalizzati** — Nome database o percorso di destinazione editabile per ogni elemento
- **Override (sovrascrittura)** — Opzione per sovrascrivere database/directory esistenti con avvisi di sicurezza multipli
- **Disclaimer interattivo** — Riepilogo in tempo reale delle operazioni con tag contestuali (OVERRIDE, DROP IF EXISTS)
- **Conferma a doppio step** — Step di conferma aggiuntivo con dettagli completi prima dell'esecuzione

### Monitoraggio & UI
- **Dashboard real-time** con statistiche, grafici successi/fallimenti, salute dei job
- **Audit Log** completo di tutte le operazioni
- **Notifiche WebSocket** (Laravel Reverb) per aggiornamenti live
- **Notifiche email** configurabili per successo/fallimento
- Interfaccia responsive con Tailwind CSS 4 + DaisyUI 5

### Gestione Utenti
- Autenticazione con login/logout
- Gestione utenti multipli
- Profilo utente

---

## 📸 Screenshots

> Inserisci gli screenshot nella cartella `docs/images/` (vedi sezione [Immagini e Asset](#-immagini-e-asset))

| Dashboard | Backup Jobs | Sorgenti |
|:---------:|:-----------:|:--------:|
| ![Dashboard](docs/images/screenshot-dashboard.png) | ![Jobs](docs/images/screenshot-jobs.png) | ![Sources](docs/images/screenshot-sources.png) |

| Destinazioni | Log | Ripristino |
|:------------:|:---:|:----------:|
| ![Destinations](docs/images/screenshot-destinations.png) | ![Logs](docs/images/screenshot-logs.png) | ![Restore](docs/images/screenshot-restore.png) |

---

## 🏗 Architettura

```
┌─────────────────────────────────────────────────────────┐
│                    Browser (UI)                         │
│              Livewire 4 + DaisyUI 5                     │
└──────────────┬──────────────────────┬───────────────────┘
               │ HTTP                 │ WebSocket
               ▼                     ▼
┌──────────────────────┐  ┌───────────────────────┐
│   Laravel 12 App     │  │   Laravel Reverb      │
│                      │  │   (WebSocket Server)  │
│  ┌────────────────┐  │  └───────────────────────┘
│  │  Livewire      │  │
│  │  Components    │  │
│  └───────┬────────┘  │
│          │           │
│  ┌───────▼────────┐  │
│  │  Services      │  │
│  │  (Backup/      │  │
│  │   Restore)     │  │
│  └───────┬────────┘  │
│          │           │
│  ┌───────▼────────┐  │
│  │  Queue Jobs    │  │
│  │  (Background)  │  │
│  └───────┬────────┘  │
│          │           │
└──────────┼───────────┘
           │
    ┌──────┼──────────────────┐
    │      │                  │
    ▼      ▼                  ▼
┌──────┐ ┌──────┐   ┌─────────────┐
│MySQL │ │Mongo │   │ S3/FTP/Local│
│      │ │DB    │   │  Storage    │
└──────┘ └──────┘   └─────────────┘
```

---

## 📦 Requisiti

### Docker
- Docker Engine 20.10+
- Docker Compose v2+

### Host (senza Docker)
- PHP 8.2+
- Composer 2.x
- Node.js 18+ & npm
- SQLite 3 (o MySQL 8+ se preferito)
- Estensioni PHP: `pdo_sqlite`, `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `curl`
- *(Opzionale)* `mysqldump` — per backup MySQL
- *(Opzionale)* `mongodump` / `mongorestore` — per backup/restore MongoDB
- *(Opzionale)* `tar`, `gzip`, `zip` — per backup filesystem

---

## 🐳 Installazione con Docker (consigliato)

### 1. Clona il repository

```bash
git clone https://github.com/zitus91/backup-manager.git
cd backup-manager
```

### 2. Copia il file di configurazione

```bash
cp .env.example .env
```

### 3. Configura il `.env`

```dotenv
APP_NAME="Backup Manager"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8080

DB_CONNECTION=sqlite

QUEUE_CONNECTION=database
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=backup-manager
REVERB_APP_KEY=backup-manager-key
REVERB_APP_SECRET=backup-manager-secret
REVERB_HOST=reverb
REVERB_PORT=8080
REVERB_SCHEME=http
```

### 4. Avvia con Docker Compose

```bash
docker compose up -d
```

### 5. Setup iniziale (solo la prima volta)

```bash
docker compose exec app composer setup
```

Questo comando esegue automaticamente:
- Installazione dipendenze PHP e Node.js
- Generazione APP_KEY
- Migrazione database
- Build degli asset frontend

### 6. Accedi all'applicazione

Apri il browser su **http://localhost:8080**

**Credenziali default:**
| Campo | Valore |
|-------|--------|
| Email | `admin@backup.local` |
| Password | `password` |

> ⚠️ **Cambia la password al primo accesso dalla sezione Profilo.**

### Docker Compose di esempio

Crea un file `docker-compose.yml` nella root del progetto:

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: backup-manager-app
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - ./storage:/var/www/html/storage
      - ./.env:/var/www/html/.env
    depends_on:
      - reverb
    environment:
      - CONTAINER_ROLE=app

  queue:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: backup-manager-queue
    restart: unless-stopped
    volumes:
      - ./storage:/var/www/html/storage
      - ./.env:/var/www/html/.env
    environment:
      - CONTAINER_ROLE=queue
    command: php artisan queue:work --tries=1 --timeout=3600

  scheduler:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: backup-manager-scheduler
    restart: unless-stopped
    volumes:
      - ./storage:/var/www/html/storage
      - ./.env:/var/www/html/.env
    environment:
      - CONTAINER_ROLE=scheduler
    command: php artisan schedule:work

  reverb:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: backup-manager-reverb
    restart: unless-stopped
    ports:
      - "8081:8080"
    volumes:
      - ./.env:/var/www/html/.env
    environment:
      - CONTAINER_ROLE=reverb
    command: php artisan reverb:start --host=0.0.0.0 --port=8080
```

### Dockerfile di esempio

Crea un file `Dockerfile` nella root del progetto:

```dockerfile
FROM php:8.2-apache

# Installazione dipendenze di sistema
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
    libsqlite3-dev default-mysql-client mongodb-database-tools \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring zip xml bcmath \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Installazione Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Installazione Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Configurazione Apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf

# Copia applicazione
WORKDIR /var/www/html
COPY . .

# Installazione dipendenze e build
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && npm ci && npm run build && rm -rf node_modules \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Crea database SQLite
RUN mkdir -p database && touch database/database.sqlite \
    && chown www-data:www-data database/database.sqlite

EXPOSE 80

CMD ["apache2-foreground"]
```

---

## 🖥 Installazione su Host

### 1. Clona il repository

```bash
git clone https://github.com/zitus91/backup-manager.git
cd backup-manager
```

### 2. Esegui il setup

```bash
composer setup
```

Questo comando esegue:
1. `composer install` — Installa le dipendenze PHP
2. Copia `.env.example` in `.env` (se non esiste)
3. `php artisan key:generate` — Genera la chiave dell'applicazione
4. `php artisan migrate --force` — Esegue le migrazioni
5. `npm install` — Installa le dipendenze Node.js
6. `npm run build` — Compila gli asset frontend

### 3. Seed del database (opzionale)

```bash
php artisan db:seed
```

Crea l'utente admin con le credenziali:
- **Email:** `admin@backup.local`
- **Password:** `password`

### 4. Avvia l'applicazione in development

```bash
composer dev
```

Questo avvia contemporaneamente:
- 🌐 **Server HTTP** — `php artisan serve` (http://localhost:8000)
- 📋 **Queue Worker** — `php artisan queue:listen`
- 📝 **Log Viewer** — `php artisan pail`
- ⚡ **Vite Dev Server** — `npm run dev`

### 5. (Produzione) Configura il web server

Per ambienti di produzione, configura Apache o Nginx per puntare alla cartella `public/`.

**Esempio Nginx:**

```nginx
server {
    listen 80;
    server_name backup.tuodominio.com;
    root /var/www/backup-manager/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 6. (Produzione) Configura i servizi in background

Aggiungi al crontab:

```bash
* * * * * cd /var/www/backup-manager && php artisan schedule:run >> /dev/null 2>&1
```

Avvia il queue worker con Supervisor:

```ini
[program:backup-manager-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/backup-manager/artisan queue:work --tries=1 --timeout=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/backup-manager/storage/logs/queue.log
stopwaitsecs=3600
```

Avvia il server WebSocket:

```ini
[program:backup-manager-reverb]
command=php /var/www/backup-manager/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/backup-manager/storage/logs/reverb.log
```

---

## ⚙️ Configurazione

### Variabili d'ambiente principali

| Variabile | Descrizione | Default |
|-----------|-------------|---------|
| `APP_NAME` | Nome dell'applicazione | `Laravel` |
| `APP_ENV` | Ambiente (`local`, `production`) | `local` |
| `APP_DEBUG` | Modalità debug | `true` |
| `APP_URL` | URL base dell'applicazione | `http://localhost` |
| `DB_CONNECTION` | Driver database (`sqlite`, `mysql`) | `sqlite` |
| `QUEUE_CONNECTION` | Driver code (`database`, `redis`) | `database` |
| `BROADCAST_CONNECTION` | Driver broadcast (`reverb`, `log`) | `log` |
| `MAIL_MAILER` | Driver email per notifiche | `log` |

### Configurazione Email (per notifiche backup)

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.tuoserver.com
MAIL_PORT=587
MAIL_USERNAME=utente@tuoserver.com
MAIL_PASSWORD=password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=backup@tuodominio.com
MAIL_FROM_NAME="Backup Manager"
```

### Configurazione WebSocket (real-time updates)

```dotenv
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=backup-manager
REVERB_APP_KEY=your-reverb-key
REVERB_APP_SECRET=your-reverb-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

---

## 🚀 Utilizzo

### 1. Configura una Sorgente Backup

Vai su **Sorgenti** e crea una nuova sorgente. Puoi configurare:
- **MySQL**: host, porta, database, utente, password
- **MongoDB**: host, porta, database, utente, password, collections
- **Filesystem**: percorso/i da includere, pattern di esclusione

Una singola sorgente può contenere più tipi (multi-tipo).

### 2. Configura una Destinazione Storage

Vai su **Destinazioni** e crea una nuova destinazione:
- **Locale**: percorso di salvataggio sul server
- **S3**: bucket, regione, access key, secret key, endpoint (compatibile MinIO)
- **FTP**: host, porta, utente, password, percorso remoto

### 3. Crea un Backup Job

Vai su **Backup Jobs** e crea un nuovo job:
- Scegli sorgente e destinazione
- Imposta la schedulazione (manuale, oraria, giornaliera, settimanale, mensile, CRON)
- Configura la retention (numero massimo di backup da conservare)
- Abilita compressione e notifiche email

### 4. Monitora dalla Dashboard

La dashboard mostra in tempo reale:
- Statistiche generali (job attivi, successi, fallimenti)
- Grafico trend ultimi 14 giorni
- Stato di salute di ogni job
- Prossimi backup schedulati
- Breakdown storage per destinazione

### 5. Ripristina un Backup

Vai su **Ripristino**, seleziona un backup log e scegli il tipo di ripristino:
- **Solo Database** — Ripristina il dump in un nuovo database
- **Solo File** — Estrae i file in una nuova directory
- **Completo** — Ripristina tutto

---

## 🖼 Immagini e Asset

### Immagini da creare

Per completare la presentazione del progetto, crea e posiziona le seguenti immagini:

#### Logo dell'applicazione

| File | Dimensioni | Posizione | Descrizione |
|------|-----------|-----------|-------------|
| `logo.png` | 512×512 px | `public/images/logo.png` | Logo principale dell'app (quadrato, trasparente) |
| `logo-light.png` | 512×512 px | `public/images/logo-light.png` | Logo per sfondo scuro |
| `favicon.ico` | 32×32 px | `public/favicon.ico` | Favicon del browser (sostituisci quello esistente) |
| `favicon.svg` | scalabile | `public/favicon.svg` | Favicon SVG per browser moderni |
| `apple-touch-icon.png` | 180×180 px | `public/apple-touch-icon.png` | Icona per dispositivi Apple |

#### Screenshot per il README

| File | Dimensioni | Posizione | Descrizione |
|------|-----------|-----------|-------------|
| `screenshot-dashboard.png` | 1280×800 px | `docs/images/screenshot-dashboard.png` | Dashboard principale con statistiche e grafici |
| `screenshot-jobs.png` | 1280×800 px | `docs/images/screenshot-jobs.png` | Lista dei backup job configurati |
| `screenshot-sources.png` | 1280×800 px | `docs/images/screenshot-sources.png` | Configurazione sorgenti backup |
| `screenshot-destinations.png` | 1280×800 px | `docs/images/screenshot-destinations.png` | Configurazione destinazioni storage |
| `screenshot-logs.png` | 1280×800 px | `docs/images/screenshot-logs.png` | Vista log dei backup eseguiti |
| `screenshot-restore.png` | 1280×800 px | `docs/images/screenshot-restore.png` | Interfaccia di ripristino |

#### Open Graph / Social Preview

| File | Dimensioni | Posizione | Descrizione |
|------|-----------|-----------|-------------|
| `og-image.png` | 1200×630 px | `docs/images/og-image.png` | Immagine per condivisione social / GitHub |

### Come creare le cartelle

```bash
mkdir -p public/images
mkdir -p docs/images
```

### Suggerimenti per il logo

Il logo dovrebbe rappresentare visivamente il concetto di **backup/protezione dati**:
- Icone suggerite: scudo + database, nuvola + freccia, disco + lucchetto
- Colori suggeriti: blu (#3B82F6), verde (#10B981) per dare un senso di sicurezza e affidabilità
- Stile: flat/minimal, coerente con DaisyUI

---

## 🧪 Testing

```bash
# Esegui tutti i test
composer test

# Oppure direttamente con Pest
php artisan test

# Con coverage
php artisan test --coverage
```

---

## 📁 Struttura del Progetto

```
backup-manager/
├── app/
│   ├── Events/
│   │   ├── Backup/          # BackupJobStarted, BackupJobCompleted
│   │   └── Restore/         # RestoreJobStarted, RestoreJobCompleted
│   ├── Http/Controllers/    # BackupLogDownloadController
│   ├── Jobs/
│   │   ├── Backup/          # ProcessBackupJob
│   │   └── Restore/         # ProcessRestoreJob
│   ├── Livewire/
│   │   ├── Admin/           # UserIndex, Profile
│   │   ├── Auth/            # Login
│   │   └── Backup/          # Dashboard, Jobs, Sources, Destinations, Logs, Restore, Audit
│   ├── Models/              # BackupJob, BackupSource, BackupStorageDestination, BackupLog, RestoreLog, AuditLog, User
│   ├── Services/
│   │   ├── Backup/          # MysqlBackupService, MongodbBackupService, FilesystemBackupService,
│   │   │                    # S3StorageService, FtpStorageService, BackupSchedulerService
│   │   └── Restore/         # MysqlRestoreService, MongodbRestoreService, FilesystemRestoreService
│   └── Trait/               # HasCache
├── config/                  # Configurazioni Laravel
├── database/
│   ├── factories/           # Factory per testing
│   ├── migrations/          # Schema database
│   └── seeders/             # DatabaseSeeder (utente admin)
├── lang/
│   ├── en/                  # Traduzioni inglese
│   └── it/                  # Traduzioni italiano
├── resources/
│   ├── css/                 # Tailwind CSS
│   ├── js/                  # JavaScript (Echo, Vite)
│   └── views/
│       ├── components/      # Layout admin e guest
│       └── livewire/        # Viste Livewire
├── routes/
│   ├── channels.php         # Canali broadcast
│   ├── console.php          # Scheduler (verifica job ogni minuto)
│   └── web.php              # Rotte web
├── tests/                   # Test con Pest
├── Dockerfile               # Immagine Docker
├── docker-compose.yml       # Orchestrazione container
└── .env.example             # Template configurazione
```

---

## � Changelog

Consulta il file [CHANGELOG.md](CHANGELOG.md) per la lista completa delle modifiche per ogni versione.

---

## �📄 License

Questo progetto è rilasciato sotto licenza [MIT](LICENSE).

---

<p align="center">
  Creato con ❤️ usando <a href="https://laravel.com">Laravel</a>, <a href="https://livewire.laravel.com">Livewire</a> e <a href="https://daisyui.com">DaisyUI</a>
</p>
