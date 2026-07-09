# Setup & run (macOS/Darwin)
composer setup   # first time: install, .env, key, migrate, seed, npm ci, build
composer dev     # concurrent: serve + queue:listen + pail + vite + reverb

# Test
composer test    # config:clear + php artisan test (Pest)
php artisan test --coverage

# Lint/format
./vendor/bin/pint   # or composer (after install)

# Manual
php artisan queue:work --verbose --tries=3 --timeout=90
php artisan reverb:start --host=0.0.0.0 --port=8080
php artisan schedule:run
php artisan backup:recover-stale-jobs

# Docker
docker compose up -d --build
# note: sqlite-web on :8082 (debug)

# Audit (no full install needed for basic)
composer audit
npm audit

# Note: external bins (mysqldump etc) must be present for real runs; tests mock them.