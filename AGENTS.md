# AGENTS.md — Quick Reference

> **Rules are now modular in `.opencode/rules/`:**
> - `00-task-framework.mdc` — 6-phase task execution framework
> - `01-code-style.mdc` — Code style, naming, conventions, build/test commands
> - `02-security.mdc` — Security, deploy, CI/CD, git conventions

## 🚨 WAJIB: SETIAP KALI DAPAT TASK, BACA DULU INI

Sebelum mengerjakan task apapun, WAJIB:
1. **Baca `.opencode/rules/00-task-framework.mdc`** — 6-phase task execution
2. **Baca `.opencode/rules/01-code-style.mdc`** — code style, build commands
3. **Baca `.opencode/rules/02-security.mdc`** — security, deploy, git rules

**Jika tidak membaca rules di atas, tugas tidak boleh dimulai.**

## 🔄 Quick Reference

### Commands
```bash
# Tests
php artisan test                                    # Run all
php artisan test --filter=TestName                  # Filter by name
php artisan test tests/Feature/Path/To/Test.php     # Single file
vendor/bin/pest --filter="test name here"           # Pest directly

# Build
npm run dev              # Development (Vite)
npm run build            # Production build

# Setup
composer install && npm install
php artisan key:generate
php artisan migrate --path=database/migrations/tenant --seed
php artisan filament:assets
php artisan livewire:publish --assets
```

### File Conventions
- Models: `$guarded = ['id']`
- PSR-12: 4-space PHP, 2-space Blade/JS/CSS
- Pest for testing, `RefreshDatabaseWithTenant` trait

### Key Directories
- `app/Http/Controllers/Api/Tenants/` — API
- `app/Filament/Tenant/` — Filament admin
- `app/Services/` — Business logic
- `app/Models/Tenants/` — Multi-tenant models
- `database/migrations/tenant/` — Tenant migrations
- `tests/Feature/` — Tests
- `docs/` — Project documentation

### Staging
- `ssh -p 2223 jogn3455@jogjatourdrive.com`
- Auto-deploy on push to `main`
- `gh workflow run ssh-command.yml --ref main -f command="..."`
