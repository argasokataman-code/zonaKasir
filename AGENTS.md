# AGENTS.md - Coding Agent Instructions

## Critical Rules

### Always use Context7 for library/API documentation
Before generating ANY code that uses a library, framework, or API:
1. Use Context7 (`ctx7` CLI or MCP tools) to fetch up-to-date documentation
2. Don't rely on training data — it may be outdated
3. Always include `use context7` or `use library /library/id` in your prompt
4. This prevents hallucinated APIs, wrong parameters, and outdated examples

## Project Overview

This is a multi-tenant Point of Sale (POS) application built with Laravel 11.x, Filament 3.x admin panel, and Livewire/Volt for reactive UI. Uses `stancl/tenancy` for multi-tenancy (database per tenant pattern).

## Build/Lint/Test Commands

### Dependencies
```bash
composer install          # Install PHP dependencies
npm install              # Install frontend dependencies
```

### Build
```bash
npm run dev              # Development build (Vite)
npm run build            # Production build
```

### Test Commands
```bash
php artisan test                                    # Run all tests
php artisan test --filter=TestName                  # Run specific test by name
php artisan test tests/Feature/Path/To/Test.php     # Run single test file
vendor/bin/pest                                     # Run Pest directly
vendor/bin/pest --filter="test name here"           # Run specific test
```

### Database Setup
```bash
php artisan key:generate
php artisan migrate --path=database/migrations/tenant --seed
php artisan filament:assets
php artisan livewire:publish --assets
```

### Code Formatting
- Blade: Uses `.bladeformatterrc.json` (2-space indent, 120 line length)
- EditorConfig: Defined in `.editorconfig`

## Code Style Guidelines

### Import Ordering
```php
// Internal classes first, then external packages
use App\Models\Tenants\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
```

### Naming Conventions
- **Classes:** PascalCase (e.g., `ProductController`, `VoucherService`)
- **Methods:** camelCase (e.g., `filterCategory()`, `applicable()`)
- **Variables:** camelCase (e.g., `$product`, `$cartItems`)
- **Database columns:** snake_case (e.g., `product_id`, `created_at`)
- **Constants:** UPPER_CASE (e.g., `ROLE_ADMIN`)

### Formatting
- **PHP:** 4 spaces indentation
- **Blade/JS/CSS:** 2 spaces indentation
- **Line length:** 120 characters max
- **Arrays:** Short syntax `[]` not `array()`
- **Braces:** PSR-12 style (opening brace on same line)

### Type Annotations
- Use PHP 8.1+ typed properties and return types
- Always declare parameter types and return types
```php
public function index(): JsonResponse
public function validate(string $attribute, mixed $value, Closure $fail): void
```

### Models
```php
protected $guarded = ['id'];              // Prefer guarded over fillable
protected $appends = ['computed_field'];   // For accessors

public function product(): BelongsTo       // Relationship with return type
{
    return $this->belongsTo(Product::class);
}
```

### Controllers
- Use Form Requests for validation (e.g., `ProductRequest`)
- Use `ApiResponseService` for consistent JSON responses
- Controllers extend base `Controller` class

### Error Handling
```php
try {
    DB::beginTransaction();
    // operations
    DB::commit();
} catch (Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### Testing (Pest)
```php
<?php

use App\Models\Tenants\User;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

test('can create product', function () {
    $user = User::first();
    actingAs($user)->postJson('/api/master/product', $data)
        ->assertStatus(Response::HTTP_CREATED);
});
```

## Architecture Patterns

### Multi-tenancy
- Uses `stancl/tenancy` with database-per-tenant
- Tenant models in `app/Models/Tenants/`
- Use `RefreshDatabaseWithTenant` trait in tests
- Call `mockTenant()` for tenant context in tests

### Filament Resources
- Located in `app/Filament/Tenant/Resources/`
- Use `HasTranslatableResource` trait for navigation labels

### Feature Flags
Use Laravel Pennant:
```php
if (feature(FeatureClass::class)) {
    // feature enabled
}
```

### Helper Functions
Global helpers in `app/helpers.php`:
- `hasFeatureAndPermission()`
- `can()`
- `price_format()`

## Key Directories
- `app/Http/Controllers/Api/Tenants/` - API controllers
- `app/Filament/Tenant/Resources/` - Filament admin resources
- `app/Services/` - Business logic services
- `app/Policies/` - Authorization policies
- `database/migrations/tenant/` - Tenant migrations
- `tests/Feature/` - Feature tests

## Documentation & Resources

**All project documentation is organized in `docs/` folder:**

- **[docs/README.md](docs/README.md)** — Documentation index and quick links
- **[docs/reports/AUDIT.md](docs/reports/AUDIT.md)** — Comprehensive code quality audit (20 issues identified, severity levels, fixes)
- **[docs/guides/QUICK_FIXES.md](docs/guides/QUICK_FIXES.md)** — Priority action list to production readiness (critical, high, medium priorities)
- **[.cursor/00-universal-agent-rules.mdc](.cursor/00-universal-agent-rules.mdc)** — Mandatory 6-phase task execution framework (eliminates hallucination, enforces scope discipline)

**Current Project Status:**
- Test Suite: 🟢 52 passed, 0 failed
- Code Quality: 🟢 Good (high priority items resolved)
- Production Ready: 🟡 Medium (E2E tests, rate limiting, audit logging remain)

**Immediate Actions Required (See docs/guides/QUICK_FIXES.md):**
- 🔴 Critical: ✅ All done (debug dump removed, permission checks added, tests running)
- 🟠 High: ✅ All done (API responses standardized, type hints/transactions/null checks added to all controllers)
- 🟡 Medium: ✅ All done (E2E tests, rate limiting, audit logging completed)
1. Run `php artisan test` to ensure all tests pass
2. Verify code follows PSR-12 formatting
3. Use `$guarded = ['id']` for new models
4. Add proper type hints to all methods
5. Follow 6-phase task framework (see .cursor/00-universal-agent-rules.mdc)

## CI/CD Workflow

### Deploy Pipeline (Auto — push to `main`)
```yaml
.github/workflows/deploy-staging.yml
```

**What happens on push to `main`:**
1. ✅ Checkout code
2. ✅ Install PHP + Node dependencies
3. ✅ Build frontend (`npm run build`) — runs in CI, NOT locally or on server
4. ✅ Package `public/build/` as tar.gz artifact
5. ✅ Upload via SCP to staging server (port 2223)
6. ✅ SSH into server:
   - `git pull origin main`
   - `composer install --no-dev`
   - Extract build assets to `public/build/`
   - Migrate tenant DB
   - Config/route/view cache
   - Storage symlink
   - `php artisan up`

### Local Dev → Staging Flow
```bash
# 1. Make changes locally
# 2. Test locally
php artisan test

# 3. Commit
git add -A
git commit -m "type(scope): description"

# 4. Push → auto-deploys to staging
git push origin main
```

**No need to build locally anymore** — CI handles `npm run build`.

---

## Commit Conventions

Format: `type(scope): description`

| Type | When |
|------|------|
| `fix` | Bug fix |
| `feat` | New feature |
| `refactor` | Code restructure |
| `style` | UI/CSS only |
| `perf` | Performance |
| `ci` | CI/CD workflow changes |
| `docs` | Documentation |
| `chore` | Tooling, deps, config |

**Examples:**
```
fix(pos): mobile cart bottom sheet not closing
feat(deploy): add CI build step for frontend assets
ci: add SSH port config to deploy workflow
style(cashier): responsive grid layout for mobile
```

---

## Branch Naming

| Branch | Purpose | Auto-deploy |
|--------|---------|-------------|
| `main` | Production-ready staging | ✅ Yes |
| `develop` | Active development | ❌ No |
| `feat/*` | Feature branches | ❌ No |
| `fix/*` | Bug fix branches | ❌ No |

Work on `main` directly (single-dev), or create `feat/*` / `fix/*` branches for parallel work.

---

## Important Rules

### Commit + Build Rule
When instructed to `commit` and `build`:
- Stage ALL workspace changes (`git add -A`)
- Do NOT create partial commits (unless user explicitly requests)
- If there are unrelated/risky changes, pause and ask first
- Verify with `git status --porcelain` before committing
- After commit, push triggers auto-deploy via CI/CD
- **Do NOT build locally** — CI handles `npm run build`

### CI/CD Safety
- Never commit `public/build/` (gitignored)
- Never commit `.env` or secrets
- If deploy fails, check: SSH keys, GitHub Secrets, build process
- Staging URL: `https://zonakasir.jogjatourdrive.com`