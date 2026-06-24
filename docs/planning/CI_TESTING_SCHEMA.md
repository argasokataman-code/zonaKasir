# CI Testing Schema

## Goal
Full test suite otomatis di GitHub Actions setiap push/pr — coverage seluruh auth flow, webhook, subscription, dan negative scenarios.

---

## 1. Test Infrastructure

### DB Strategy
| Environment | DB | Kecepatan | Use Case |
|-------------|-----|-----------|----------|
| `testing` | SQLite `:memory:` | ⚡ Fast | Unit + Feature (non-PG-specific) |
| `pgsql_test` | PostgreSQL service | 🐢 Slow | Integration (tenant isolation, PG-specific) |

**Default:** SQLite in-memory (all feature tests run here)
**Optional matrix:** PostgreSQL untuk verifikasi compatibility

### phpunit.xml (already set)
```xml
<env name="DB_CONNECTION" value="testing"/>
<env name="DB_TESTING_DRIVER" value="sqlite"/>
<env name="DB_DATABASE_TESTING" value=":memory:"/>
```

### Key Configs
```bash
APP_ENV=testing
APP_KEY=base64:...
CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
```

---

## 2. Test Categories

### 2a. Auth Tests (`tests/Feature/E2E/AuthenticationE2ETest.php`)

| Test | What it verifies | Status |
|------|-----------------|--------|
| Login API success | POST `/api/auth/login` → 200 + token | ✅ |
| Login invalid creds | Wrong email/password → 422/401 | ✅ |
| Logout | POST `/api/auth/logout` → 200 | ✅ |
| Auth route with token | GET `/api/auth/me` with Bearer → 200 | ✅ |
| Auth route without token | GET `/api/auth/me` no auth → 401 JSON | ✅ |
| Full login→token→profile | Login then use token to get profile | ✅ |
| **GET `/api/auth/login`** | Browser redirect → 302 `/member/login` | ✅ |
| **Web route no auth** | GET `/member/subscription` → 302 `/member/login` | ✅ |
| **API no auth returns JSON** | GET `/api/auth/me` → 401 `{message}` | ✅ NEW |
| **Login rate limit** | 6 wrong attempts → 422 with throttle error | ✅ |

### 2b. Authorization Tests (`AuthorizationE2ETest.php`)
Existing tests — permission checks for categories, members, profile, etc.

### 2c. Negative Scenario Tests (NEW — to add)

| Scenario | Test | Expected |
|----------|------|----------|
| Expired token | Set expired token via `Carbon::now()->subDays(8)` | 401 JSON |
| Expired session + Livewire | Web page + expired session | Redirect `/member/login` |
| Rate limited login >5/min | 6 POST `/api/auth/login` in loop | 422 throttle error |
| CSRF mismatch | POST without session cookie | 419 (web) / 401 (API) |
| Suspended tenant | Mark tenant suspended → try API | 403 |
| Webhook no auth | POST `/api/webhooks/midtrans` no signature | 401 / processed by handler |

### 2d. Webhook Tests (NEW)

| Test | What it verifies |
|------|-----------------|
| Midtrans webhook valid signature | POST with valid HMAC → 200 |
| Midtrans webhook invalid signature | POST with wrong signature → 401 |
| Subscription webhook | POST with `SUB-` order → updates subscription |
| Flip webhook | POST with valid Flip payload → 200 |

### 2e. Subscription Tests (NEW)

| Test | What it verifies |
|------|-----------------|
| Subscription page no auth | Redirect to login |
| Subscription page with auth | 200 + page rendered |
| Invoice history shows `number` | `$inv['number']` exists |
| Snap redirect URL set | `$snapRedirectUrl` not null after subscribe |

---

## 3. GitHub Actions Workflow

```yaml
# .github/workflows/test.yml
name: test

on:
  push:
    branches: [main, vercel, 1.x]
  pull_request:
    branches: [main, vercel, 1.x]

jobs:
  # Fast track — SQLite, all feature tests
  sqlite-tests:
    name: SQLite Test Suite
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php-version: [8.4]

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP ${{ matrix.php-version }}
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          coverage: xdebug
          tools: composer

      - name: Get composer cache
        uses: actions/cache@v3
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}

      - name: Install dependencies
        run: composer install --no-progress --prefer-dist

      - name: Setup .env
        run: |
          cp .env.example .env
          php artisan key:generate

      - name: Run tests (SQLite)
        run: php artisan test --parallel
        env:
          DB_TESTING_DRIVER: sqlite
          DB_DATABASE_TESTING: ':memory:'

  # Slow track — PostgreSQL, integration tests
  pgsql-tests:
    name: PostgreSQL Test Suite
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php-version: [8.4]

    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_USER: zonakasir
          POSTGRES_PASSWORD: secret
          POSTGRES_DB: lakasir_test
        ports:
          - 5432:5432
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP ${{ matrix.php-version }}
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          coverage: none
          tools: composer

      - name: Install dependencies
        run: composer install --no-progress --prefer-dist

      - name: Setup .env for PgSQL
        run: |
          cp .env.example .env
          php artisan key:generate
          php -r "
            \$env = file_get_contents('.env');
            \$env = preg_replace('/DB_CONNECTION=.*/', 'DB_CONNECTION=testing', \$env);
            \$env = preg_replace('/DB_HOST=.*/', 'DB_HOST=127.0.0.1', \$env);
            \$env = preg_replace('/DB_PORT=.*/', 'DB_PORT=5432', \$env);
            \$env = preg_replace('/DB_DATABASE=.*/', 'DB_DATABASE=lakasir_test', \$env);
            \$env = preg_replace('/DB_USERNAME=.*/', 'DB_USERNAME=zonakasir', \$env);
            \$env = preg_replace('/DB_PASSWORD=.*/', 'DB_PASSWORD=secret', \$env);
            file_put_contents('.env', \$env);
          "

      - name: Run tests (PostgreSQL)
        run: php artisan test --parallel
        env:
          DB_TESTING_DRIVER: pgsql
          DB_DATABASE_TESTING: lakasir_test
          DB_USERNAME_TESTING: zonakasir
          DB_PASSWORD_TESTING: secret
          DB_HOST: 127.0.0.1
          DB_PORT: 5432

  # Coverage report
  coverage:
    name: Code Coverage
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4
          coverage: xdebug

      - name: Install dependencies
        run: composer install --no-progress --prefer-dist

      - name: Setup .env
        run: |
          cp .env.example .env
          php artisan key:generate

      - name: Run tests with coverage
        run: php artisan test --coverage-clover clover.xml
        env:
          DB_TESTING_DRIVER: sqlite
          DB_DATABASE_TESTING: ':memory:'

      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          file: clover.xml
```

---

## 4. Required Scripts

### `scripts/test.sh` (optional — simplifies CI)
```bash
#!/bin/bash
set -e

# Run tests with specific filter
FILTER=${1:-''}

if [ -n "$FILTER" ]; then
    php artisan test --filter="$FILTER"
else
    php artisan test
fi
```

### `scripts/test-coverage.sh`
```bash
#!/bin/bash
set -e

php artisan test --coverage-html coverage/
echo "Coverage report: coverage/index.html"
```

---

## 5. Tests Per Bugfix

Setiap bugfix WAJIB disertai minimal 1 test yang:
1. **RED phase** — test fails (demonstrates bug exists)
2. **GREEN phase** — test passes (bug fixed)
3. **REGRESSION phase** — existing tests still pass

### Auth bugfix — sudah selesai ✅
| Test | RED | GREEN |
|------|-----|-------|
| GET `/api/auth/login` returns 200 → redirect | ✅ Test failed | ✅ Now 302 |
| Web route no auth redirects to `/member/login` | ✅ | ✅ |
| API no auth returns 401 JSON | ✅ | ✅ |
| Login rate limit | ✅ | ✅ |

---

## 6. Running Tests Locally

```bash
# All tests (SQLite in-memory — fast)
php artisan test

# Single file
php artisan test tests/Feature/E2E/AuthenticationE2ETest.php

# Single test by name
php artisan test --filter="GET api/auth/login"

# With coverage
php artisan test --coverage-html coverage/

# PostgreSQL (requires local PG running)
DB_TESTING_DRIVER=pgsql DB_DATABASE_TESTING=lakasir_test php artisan test
```

---

## 7. Current Test Status

| Suite | Tests | Pass | Fail |
|-------|-------|------|------|
| Auth E2E | 10 | 10 | 0 |
| Full Feature (SQLite) | ~40 | 40 | 0 (target) |

**Next:** Add negative scenario tests (2c), webhook tests (2d), subscription tests (2e).
