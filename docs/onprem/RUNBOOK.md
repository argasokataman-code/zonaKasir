# On-Premise RUNBOOK — zonaKasir Kafe

> FR-9.1–9.7: Panduan operasional onprem kafe. Simpan di server client.

## 1. Install & Setup

```bash
# 1. Clone repo
git clone https://github.com/argasokataman-code/zonaKasir.git /var/www/zonakasir
cd /var/www/zonakasir

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 3. Setup .env
cp .env.example .env
php artisan key:generate

# 4. Database (PostgreSQL)
sudo -u postgres createdb zonakasir
php artisan migrate --path=database/migrations/tenant --seed

# 5. Buat admin user
php artisan tenant:create-user --name="Admin" --email="admin@kafe.local" --password="password"

# 6. Start services
sudo systemctl start nginx php8.2-fpm postgresql
```

## 2. Update (app:update)

```bash
php artisan app:update
```

- Otomatis download ZIP dari GitHub, extract, jalankan migration
- Backup otomatis sebelum update (`storage/app/backups/`)
- Rollback: `php artisan app:restore-app`

## 3. Backup Database (FR-9.5)

```bash
# Backup manual
php artisan backup:database

# Backup otomatis berjalan daily jam 04:00 (via scheduler)
# Retention: 7 hari (config backup.retention_days)
```

### Restore Database

```bash
# Restore dari file backup
gunzip < storage/app/backups/db/zonaKasir_YYYY-MM-DD_HHMMSS.sql.gz | psql -U forge -d zonakasir
php artisan migrate --path=database/migrations/tenant
```

### Lokasi Backup

| Tipe | Lokasi |
|------|--------|
| Database | `storage/app/backups/db/` |
| App files | `storage/app/backups/` |

## 4. Monitoring

```bash
# Cek versi
cat version.txt

# Cek status service
sudo systemctl status nginx php8.2-fpm postgresql

# Cek logs
tail -f storage/logs/laravel.log
sudo tail -f /var/log/nginx/error.log
```

## 5. Offline Mode (FR-9.1)

Core kasir jalan **tanpa internet**:
- Open bill, table, split bill, struk, laporan
- Semua data lokal di PostgreSQL server client

**Butuh internet (FR-9.2):**
- Struk digital shareable (generate link/QR publik)
- Best-seller auto-post
- QR menu digital
- Grafik jam ramai
- Auto-caption

## 6. Troubleshooting

| Masalah | Solusi |
|---------|--------|
| App tidak bisa diakses | `sudo systemctl restart nginx php8.2-fpm` |
| Database connection error | Cek `.env`, pastikan PostgreSQL running |
| Migration error | `php artisan migrate --path=database/migrations/tenant --force` |
| Update gagal | `php artisan app:restore-app` |
| Backup kosong | Cek `pg_dump` terinstall, cek credentials di `.env` |
| Permission error | `chown -R www-data:www-data storage bootstrap/cache` |
