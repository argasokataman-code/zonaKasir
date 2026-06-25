# Deploy to Vercel

## Branch Strategy
- **`main`** → Production (`https://zona-kasir.vercel.app`)
- **`vercel`** → Preview (branch deploy, URL otomatis dari Vercel)

## Deployment Flow

```bash
# 1. Kerja di branch vercel
git checkout vercel
git add -A && git commit -m "feat: ..."
git push origin vercel

# 2. PR / Merge ke main
git checkout main
git merge vercel
git push origin main

# 3. Vercel auto-deploy dari main (tunggu ~2-3 menit)
```

## Environment Variables (Vercel Dashboard)

Set di **Project Settings → Environment Variables** untuk **Production**:

| Name | Value |
|------|-------|
| `APP_URL` | `https://zona-kasir.vercel.app` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `DB_CONNECTION` | `pgsql` |
| `DB_SSLMODE` | `require` |
| `SESSION_DRIVER` | `cookie` |

Database vars (`POSTGRES_*`, `SUPABASE_*`) sudah otomatis dari Supabase integration.

> **Important:** `SESSION_DRIVER=cookie` prevents 419 CSRF errors. Vercel uses `/tmp` for file storage which resets on cold starts. Cookie driver stores session data encrypted on the client side — no server-side storage needed.

## Build
- Build command: `yarn run build`
- Output directory: `public`
- PHP runtime: `vercel-php@0.9.0`
- Filament assets sudah di-commit ke repo (read-only FS di Vercel)

## Troubleshooting

| Problem | Fix |
|---------|-----|
| 404 all routes | `APP_URL` harus sesuai domain production |
| CSS 404 | `php artisan filament:assets` → commit hasilnya |
| DB connection refused | Set `DB_SSLMODE=require` + `POSTGRES_*` env |
| Migration not run | Auto-run di `api/index.php` (cold start pertama) |
