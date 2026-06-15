# Disbursement: Transfer ke Tenant via Flip — Planning Document

> **Version:** 1.7
> **Status:** ✅ READY FOR DEVELOPMENT — Semua keputusan bisnis sudah dijawab + detail technical design lengkap + fee calculation konsisten + admin tidak bisa edit nominal + CRITICAL issues fixed + email template lengkap + MANDATORY double verification rule
> **Created:** 2026-06-15
> **Last Updated:** 2026-06-15
> **Author:** ZonaKasir Dev Team

## Ringkasan Eksekutif

Fitur **Admin-Initiated Direct Transfer** memungkinkan admin ZonaKasir mengirim dana langsung ke rekening bank tenant melalui Flip API, tanpa menunggu tenant mengajukan withdrawal. Use case: reward/bonus, refund, penyesuaian saldo, atau pembayaran langsung dari platform.

**Key Finding:** Infrastructure sudah 90% ada. Namun analisis mendalam menemukan **5 high-severity** dan **5 medium-severity** issues yang harus di-fix sebelum fitur ini di-build.

---

## Daftar Isi

1. [Arsitektur Saat Ini](#1-arsitektur-saat-ini)
2. [Gap Analysis](#2-gap-analysis)
3. [Negative Scenarios (Failure Modes)](#3-negative-scenarios)
4. [Security Analysis](#4-security-analysis)
5. [Edge Cases](#5-edge-cases)
6. [Concurrency Issues](#6-concurrency-issues)
7. [Compliance & Legal](#7-compliance--legal)
8. [Keputusan Bisnis yang Diperlukan](#8-keputusan-bisnis-yang-diperlukan)
9. [Technical Design](#9-technical-design)
10. [Implementasi & Testing Plan](#10-implementasi--testing-plan)

---

## 1. Arsitektur Saat Ini

### 1.1 Flow Withdrawal (Tenant → Bank)

```
Tenant Request                Lakasir Core                      Flip API
     │                             │                                │
     │ 1. POST /api/tenant/withdrawal                              │
     │    {amount, idempotency_key}                                │
     │────────────────────────────►│                                │
     │                             │ 2. WithdrawalService::request()
     │                             │    ├── LedgerService::getCurrentBalance()
     │                             │    ├── Cek max 95% saldo
     │                             │    ├── Withdrawal::create(status=pending)
     │                             │    ├── LedgerService::entry(debit)
     │                             │    └── IdempotencyLog::create()
     │                             │                                │
     │  3. Withdrawal created      │                                │
     │◄────────────────────────────│                                │
     │                             │                                │
Admin Approve                     │                                │
     │ 4. POST /admin/withdrawal/{id}/approve                      │
     │────────────────────────────►│                                │
     │                             │ 5. WithdrawalService::approve()
     │                             │    ├── Cek 2-admin approval (>25jt)
     │                             │    ├── Status → processing
     │                             │    └── FlipPayoutProvider::send()──────►│
     │                             │                                │ 6. POST /v2/disbursement
     │                             │                                │    {bank_code, account_number,
     │                             │     7. Status → completed       │     amount, remark, idempotency_key}
     │                             │    └── LedgerEntry update       │
     │                             │                                │
     │                             │     8. Notification::send()     │
     │                             │                                │
Webhook                           │                                │
     │  9. POST /api/webhooks/flip │                                │
     │     {id, status, token}     │◄───────────────────────────────│
     │────────────────────────────►│                                │
     │                             │ 10. FlipWebhookController::handle()
     │                             │     └── Withdrawal status update│
```

### 1.2 Key Components

| Component | File | Fungsi |
|-----------|------|--------|
| `DisbursementProvider` | `app/Services/Tenants/DisbursementProvider.php` | Interface: `send()`, `status()` |
| `FlipPayoutProvider` | `app/Services/Tenants/FlipPayoutProvider.php` | Flip API impl: POST `/v2/disbursement` |
| `FlipDataService` | `app/Services/Tenants/FlipDataService.php` | Fetch balance + disbursement history |
| `WithdrawalService` | `app/Services/Tenants/WithdrawalService.php` | Request/approve/reject logic |
| `LedgerService` | `app/Services/Tenants/LedgerService.php` | Double-entry accounting |
| `FlipWebhookController` | `app/Http/Controllers/Api/Webhooks/FlipWebhookController.php` | Webhook handler |
| `Disbursement` (page) | `app/Filament/Admin/Pages/Disbursement.php` | Admin dashboard |
| `WithdrawalApproval` (page) | `app/Filament/Admin/Pages/WithdrawalApproval.php` | Admin approve page |

### 1.3 Approval Thresholds

```
< Rp 5.000.000  + tenant ≥30 hari    → auto-approve → langsung ke Flip
Rp 5.000.000 - 25.000.000           → 1 admin approve → ke Flip
> Rp 25.000.000                      → 2 admin approve (beda admin) → ke Flip
```

### 1.4 Database Schema

**`withdrawals` table:**
```sql
id, amount, bank_name, bank_account_name, bank_account_number,
bank_code, status (pending|approved|rejected|processing|completed|failed),
idempotency_key (unique), disburse_id, disburse_response (json),
notes, requested_by, approved_by, rejected_by, rejection_reason,
processed_at, tenant_id, created_at, updated_at
```

**`ledger_entries` table:**
```sql
id, ledgerable_type, ledgerable_id, entry_type (credit|debit),
amount, balance_before, balance_after, description,
reference_type, reference_id, fee_rate_type, fee_rate_value,
tenant_id, created_at, updated_at
```

### 1.5 Permissions

```
request withdrawal   — tenant user: buat withdrawal
manage withdrawals   — tenant admin: lihat/approve/reject withdrawal
read withdrawal      — tenant user: liat history
create withdrawal    — tenant user: buat withdrawal
update withdrawal    — tenant admin: approve/reject
manage settings      — super admin: akses admin panel (termasuk disbursement)
```

---

## 2. Gap Analysis

### 2.1 Yang Sudah Ada ✅

| Komponen | Status | Catatan |
|----------|--------|---------|
| Flip disbursement API | ✅ | `FlipPayoutProvider::send()` → POST `/v2/disbursement` |
| Flip balance check | ✅ | `FlipDataService::getBalance()` → GET `/v2/general/balance` |
| Flip webhook handler | ✅ | `FlipWebhookController::handle()` — update status |
| Tenant bank data | ✅ | `About` model: bank_name, bank_code, bank_account_name, bank_account_number |
| Ledger double-entry | ✅ | `LedgerService` dengan `GET_LOCK()` concurrency safety |
| Withdrawal model | ✅ | `withdrawals` table dengan full schema |
| Notification system | ✅ | `WithdrawalApproved/Rejected/Requested/Failed` |
| Audit trail | ✅ | `Spatie\Activitylog` di Withdrawal + LedgerEntry |
| Rate limiting | ✅ | `WithdrawalRateLimit` (5 req/min) + `throttle:30,1` |
| Admin dashboard | ✅ | `Disbursement.php` — balance, tenant bank info, withdrawal history |
| Tests | ✅ | `FlipPayoutTest`, `FlipWebhookTest`, `FlipDataServiceTest` |

### 2.2 Yang Perlu Dibangun

| Komponen | Prioritas | Effort |
|----------|-----------|--------|
| Admin transfer form (Filament Action) | HIGH | 0.5 hari |
| `DirectTransferService::transferToTenant()` | HIGH | 0.5 hari |
| Migration: tambah column `type` ke `withdrawals` | HIGH | 0.5 jam |
| `TransferReceived` notification | MEDIUM | 0.5 jam |
| Fee handling logic | MEDIUM | 0.5 hari |
| Transfer history view | MEDIUM | 0.5 jam |
| Batch transfer (opsional) | LOW | 1 hari |
| Comprehensive tests | HIGH | 1 hari |

### 2.3 Yang Perlu Di-Fix Dulu (Pre-requisite Bugs)

| Bug | Severity | File:Line | Impact |
|-----|----------|-----------|--------|
| Flip returned status diabaikan | 🔴 HIGH | `WithdrawalService:143` | Status `pending` dari Flip ditandai `completed` prematur |
| `About::first()` null crash | 🟡 MEDIUM | `WithdrawalService:57` | Crash jika About table kosong |
| Float precision untuk money | 🔴 HIGH | Seluruh codebase | Silent rounding error, `(int)` cast potong desimal |
| Webhook token di body + logged | 🔴 HIGH | `FlipWebhookController:15-21` | Token leak di logs |
| No HMAC webhook verification | 🔴 HIGH | `FlipWebhookController` | Webhook spoofing jika token bocor |
| `GET_LOCK` timeout 5s | 🟡 MEDIUM | `LedgerService:31` | Lock tidak di-acquire → race condition |
| Webhook endpoint no rate limit | 🟡 MEDIUM | `routes/api.php:21` | Flooding webhook |
| No Flip balance pre-check | 🔴 HIGH | `WithdrawalService:133` | Transfer gagal karena Flip balance kosong |
| Double admin approval race | 🟡 MEDIUM | `WithdrawalService:121` | Dua admin approve bersamaan |

---

## 3. Negative Scenarios (Failure Modes)

### 3.1 Step-by-Step Failure Analysis

#### Step 1: Admin Buka Halaman Disbursement

| # | Failure | Probabilitas | Impact | Current Handling | Perlu Fix? |
|---|---------|-------------|--------|-----------------|------------|
| 1.1 | Flip API unreachable (DNS timeout, connection refused) | Medium | Balance tidak tampil | `try/catch` → `$balanceError` string | ⚠️ Tambah retry |
| 1.2 | Flip API 401 (secret key salah/tidak ada) | Low | Balance null | Logged + null return | ✅ Cukup |
| 1.3 | Flip API 429 (rate limited) | Medium | Balance fetch gagal | Treated as generic failure | ⚠️ Tambah backoff |
| 1.4 | Flip API 500/502/503 | Medium | Same as above | Same | ⚠️ Circuit breaker |
| 1.5 | `About` table kosong (tenant tidak punya bank info) | Low | Kolom bank kosong, badge "Missing" | Tampil badge, tidak abort | ⚠️ Blokir transfer |
| 1.6 | `Tenant::all()` dengan 10K+ tenants | Low | Memory/performance issue | Tidak ada pagination | ⚠️ Tambah limit |
| 1.7 | Flip balance = 0 | Medium | Admin tidak tahu apakah bisa transfer | Hanya ditampilkan, tidak di-cross-check | ⚠️ Tambah validasi |

#### Step 2: Admin Submit Transfer

| # | Failure | Probabilitas | Impact | Current Handling | Perlu Fix? |
|---|---------|-------------|--------|-----------------|------------|
| 2.1 | Nominal = 0 atau negatif | Low | Transfer absurd | `min:50000` di controller | ✅ Cukup |
| 2.2 | Nominal melebihi Flip balance | Medium | Flip API reject | Error ditangkap, status=failed | ⚠️ Pre-check |
| 2.3 | Nominal > 95% ledger balance | High | Diblokir oleh service | `InsufficientBalanceException` | ✅ Cukup |
| 2.4 | Tenant bank info tidak valid | Low | Flip reject | Error ditangkap | ⚠️ Validasi sebelum kirim |
| 2.5 | Bank code tidak dikenal Flip | Low | Flip reject | `mapBankCode()` hanya lowercase | ⚠️ Validasi bank code |
| 2.6 | Race condition: 2 admin submit bersamaan | Low | Double transfer | Flip idempotency key mencegah | ⚠️ Tambah row lock |
| 2.7 | `idempotency_key` collision | Extremely Low | Duplicate entry | Unique constraint | ✅ Cukup |
| 2.8 | Float truncation: `100000.50` → `100000` | Medium | Silent loss Rp 50 | `(int)` cast di FlipPayoutProvider:19 | 🔴 Fix required |
| 2.9 | Nominal sangat besar (miliaran) | Low | Melebihi Flip limit | Tidak ada max validation | ⚠️ Tambah max |

#### Step 3: Flip API Processing

| # | Failure | Probabilitas | Impact | Current Handling | Perlu Fix? |
|---|---------|-------------|--------|-----------------|------------|
| 3.1 | Flip API timeout (>30s) | Low | Status tidak jelas | `Throwable` → status=failed, rollback | ⚠️ Add timeout config |
| 3.2 | Flip return `pending` (async) | High | Status prematurely marked `completed` | **BUG** di `WithdrawalService:143` | 🔴 Fix required |
| 3.3 | Flip return `FAILED` (invalid account) | Medium | Status set `completed` incorrectly | **BUG** — status diabaikan | 🔴 Fix required |
| 3.4 | Flip return `CANCELLED` | Low | Same as above | **BUG** | 🔴 Fix required |
| 3.5 | Network failure SETELAH Flip terima tapi SEBELUM selesai proses | Medium | Rollback padahal Flip sudah kirim uang | Status=failed + ledger rollback | 🔴 Critical: money lost |
| 3.6 | Flip rate limit (too many requests) | Low | 429 error | Ditangkap sebagai generic error | ⚠️ Retry with backoff |

#### Step 4: Webhook Processing

| # | Failure | Probabilitas | Impact | Current Handling | Perlu Fix? |
|---|---------|-------------|--------|-----------------|------------|
| 4.1 | Webhook datang SEBELUM DB write selesai | Medium | `disburse_id` tidak ditemukan → 404 | Webhook hilang, tidak ada retry | 🔴 Critical |
| 4.2 | Webhook datang SETELAH status=failed + rollback | Low | Uang sudah dikirim Flip, tapi ledger di-rollback | Double-spend: tenant dapat uang + credit | 🔴 Critical |
| 4.3 | Webhook duplikat (Flip retry) | Medium | Status sudah benar, webhook di-ignore | `$newStatus !== $withdrawal->status` | ✅ Idempotent |
| 4.4 | `$payload['id']` atau `$payload['status']` null | Low | `Undefined array key` | Tidak ada null check | ⚠️ Tambah validation |
| 4.5 | Token salah/tidak ada | Low | 401 response | Token check ada | ✅ Cukup |
| 4.6 | Attacker brute-force webhook token | Low | Fake status update | No rate limit | ⚠️ Tambah rate limit |
| 4.7 | Status tidak dikenal (e.g., `PROCESSING`) | High | Tidak ada perubahan | `default` di match → no change | ✅ Safe |
| 4.8 | Webhook datang saat transfer sedang `processing` tapi belum ada `disburse_id` | Medium | Webhook 404 | Tidak ada retry | ⚠️ Delayed processing |

#### Step 5: Ledger Operations

| # | Failure | Probabilitas | Impact | Current Handling | Perlu Fix? |
|---|---------|-------------|--------|-----------------|------------|
| 5.1 | `GET_LOCK` timeout (5s) | Low | Lock tidak di-acquire, proceed tanpa lock | **Race condition** | 🔴 Fix required |
| 5.2 | Float precision pada `balanceAfter` | Medium | Balance salah perhitungan | `double` type | 🔴 Architectural issue |
| 5.3 | `EnsuresLedgerBalance` violation | Low | Corruption tanpa alert | `Log::critical` tapi tidak throw | ⚠️ Tambah alert |
| 5.4 | Rollback credit entry melebihi balance | Low | Saldo jadi negatif | `balanceAfter < 0` check | ✅ Cukup |

### 3.2 Failure Mode Summary

```
                        SEVERITY DISTRIBUTION
                        
   🔴 HIGH    ████████████████  (5 issues)
   🟡 MEDIUM  ████████████████████████  (10 issues)
   🟢 LOW     ████████████  (6 issues)
   
   Fix Required Before Build: 🔴 HIGH issues
   Fix Required During Build: 🟡 MEDIUM issues
   Can Defer:                  🟢 LOW issues
```

---

## 4. Security Analysis

### 4.1 Authentication & Authorization

| # | Attack Vector | Risk | Finding | Mitigation |
|---|--------------|------|---------|------------|
| A1 | Non-admin akses admin panel | Medium | `canAccess()` cek `can('manage settings')` | ✅ Protected by Spatie permission |
| A2 | Tenant approve withdrawal sendiri | High | `WithdrawalPolicy::approve()` tidak cek `tenant_id === user->tenant_id` | 🔴 IDOR vulnerability |
| A3 | Tenant approve withdrawal tenant lain | High | Sama — no tenant ownership check | 🔴 Add tenant_id verification |
| A4 | Admin approve via API (bukan panel) | Medium | `routes/tenant.php:180` pakai tenant guard | ⚠️ Admin harus pakai admin guard |
| A5 | Escalate permission ke `manage settings` | Low | Spatie permission — butuh DB access | ⚠️ Audit permission setup |

### 4.2 Input Validation

| # | Attack Vector | Risk | Finding | Mitigation |
|---|--------------|------|---------|------------|
| B1 | Negative amount | Low | `min:50000` block | ✅ Safe |
| B2 | Float manipulation (100000.9999999) | Medium | Float precision issues | ⚠️ Use integer cents |
| B3 | tenant_id manipulation | Medium | `HasTenant` scope isolates, but admin uses `withoutGlobalScope` | ⚠️ Explicit validation |
| B4 | bank_account_number injection | Low | No regex validation — Flip rejects, but error is generic | ⚠️ Add regex |
| B5 | bank_code invalid | Low | `mapBankCode()` only lowercases | ⚠️ Validate against known codes |
| B6 | SQL injection via bank fields | Low | Eloquent parameterized queries | ✅ Safe |
| B7 | XSS via bank_account_name in Blade | Low | Blade `{{ }}` auto-escapes | ✅ Safe |

### 4.3 Race Conditions

| # | Attack Vector | Risk | Finding | Mitigation |
|---|--------------|------|---------|------------|
| C1 | Concurrent balance check + transfer | High | `GET_LOCK` timeout → proceed without lock | 🔴 Add `SELECT ... FOR UPDATE` |
| C2 | Balance check passes, Flip balance depleted | High | No Flip balance re-check before `send()` | 🔴 Add pre-flight check |
| C3 | Two admins approve simultaneously | High | No row-level lock on withdrawal status | 🔴 Add DB lock |
| C4 | Webhook arrives during DB write | Medium | No lock on webhook processing | ⚠️ Acceptable (idempotent) |
| C5 | Auto-approve + manual approve simultaneously | Medium | `AutoApproveWithdrawal` not registered (dead code) | ✅ Not a threat |

### 4.4 CSRF Protection

| Finding | Status |
|---------|--------|
| Admin panel: `VerifyCsrfToken` middleware | ✅ Protected |
| Filament Livewire: built-in CSRF | ✅ Protected |
| Webhook endpoint: no CSRF (external) | ✅ Intentional |

### 4.5 Mass Assignment

| Finding | Status |
|---------|--------|
| `Withdrawal`: `$guarded = ['id']` | ⚠️ All fields mass-assignable in theory |
| Actual usage: hardcoded arrays in service | ✅ Safe in practice |
| Risk: if request data passed directly to `create()` | ⚠️ Add validation layer |

### 4.6 Webhook Security

| # | Attack Vector | Risk | Finding | Mitigation |
|---|--------------|------|---------|------------|
| D1 | Webhook spoofing | High | Only body token checked, no HMAC | 🔴 Use `X-Flip-Signature` |
| D2 | Token leak in logs | Medium | Full payload logged on invalid token (line 21) | ⚠️ Redact token from logs |
| D3 | Token brute-force | Medium | No rate limit on webhook endpoint | ⚠️ Add throttle |
| D4 | Replay attack | Low | Idempotent — same status = no change | ✅ Safe |
| D5 | No IP whitelist | Low | Any IP can send webhook | ⚠️ Add Flip IP whitelist |

### 4.7 Data Leakage

| # | Finding | Risk | Mitigation |
|---|---------|------|------------|
| E1 | Bank account numbers visible to all admins | Medium | All admins with `manage settings` can see all bank details | ⚠️ Role-based visibility |
| E2 | Flip disbursement data in admin page | Medium | Shows all historical disbursements | ⚠️ Add date filter |
| E3 | Error messages contain Flip error details | Low | `DisbursementFailedException` → shown to admin | ⚠️ Sanitize error messages |
| E4 | `disburse_response` JSON stored in DB | Low | Contains Flip internal data | ⚠️ Encrypt or redact |

### 4.8 Audit Trail

| Finding | Status |
|---------|--------|
| `Withdrawal` model: `LogsActivity` (created, updated) | ✅ Activity log |
| `LedgerEntry` model: `LogsActivity` (created) | ✅ Activity log |
| Webhook controller: `Log::info` with withdrawal_id | ✅ Application log |
| Admin action audit | ⚠️ No dedicated admin action log |
| Financial audit trail | ⚠️ Ledger entries exist but no formal journal |

### 4.9 Rate Limiting

| Endpoint | Current | Finding |
|----------|---------|---------|
| `POST /api/tenant/withdrawal` | `throttle:30,1` + `withdrawal.ratelimit` (5/min) | ✅ Dual throttled |
| `POST /api/tenant/withdrawal/{id}/approve` | No specific limit | ⚠️ General API throttle only |
| `POST /api/tenant/withdrawal/{id}/reject` | No specific limit | ⚠️ Same |
| `POST /api/webhooks/flip` | **NO LIMIT** | 🔴 Vulnerable to flooding |
| Admin panel actions | Auth throttled, actions not | ⚠️ Add per-action throttle |

### 4.10 Security Summary

```
                        SECURITY RISK MAP
                        
  🔴 HIGH RISK (Fix Required)
  ├── A2/A3: IDOR — tenant can approve other tenants' withdrawals
  ├── C1/C2/C3: Race conditions on balance check + approval
  ├── D1: No HMAC webhook verification
  └── Webhook endpoint: no rate limit
  
  🟡 MEDIUM RISK (Fix During Build)
  ├── B2/B5: Float precision + bank code validation
  ├── D2/D3: Token leak + brute-force
  ├── E1/E2: Data leakage to admins
  └── No fee tracking in ledger
  
  🟢 LOW RISK (Can Defer)
  ├── B6/B7: SQL injection / XSS (Eloquent + Blade safe)
  ├── A5: Permission escalation (needs DB access)
  └── E3/E4: Error message / response data leakage
```

---

## 5. Edge Cases

| # | Scenario | Expected Behavior | Risk | Current Handling |
|---|----------|-------------------|------|-----------------|
| E1 | Transfer minimum tepat (Rp 50.000) | Berhasil | Low | `min:50000` pass ✅ |
| E2 | Transfer tepat = Flip balance | Berhasil (Flip punya fee, jadi kurang) | Medium | Tidak dicek ❌ |
| E3 | Transfer tepat = 95% ledger balance | Diblokir oleh service | Low | `maxAllowed` check ✅ |
| E4 | Flip balance pending (in transit) | Tidak bisa dipakai | High | Hanya `balance` ditampilkan, `pending_balance` tidak dipakai ❌ |
| E5 | Transfer ke rekening same-bank (BCA→BCA) | Berhasil, fee mungkin berbeda | Low | Tidak ada penanganan khusus |
| E6 | Transfer ke tenant yang punya pending withdrawal | Berhasil (withdrawal lain status pending) | Medium | Tidak ada check ❌ |
| E7 | Transfer ke tenant yang sudah deaktif | About::first() bisa null | High | **CRASH** ❌ |
| E8 | Special characters di bank_account_name | Flip mungkin reject | Low | Tidak ada sanitasi |
| E9 | Transfer tepat jam 00:00 (date boundary) | `diffInDays` flicker | Low | Tolerable |
| E10 | Transfer saat Flip maintenance | Error ditangkap | Low | Status=failed + rollback ✅ |
| E11 | Amount desimal: `100000.50` | `(int)` truncation → `100000` | Medium | **Silent loss** ❌ |
| E12 | Amount sangat besar (Rp 10 miliar) | Melebihi Flip limit | Low | Tidak ada max validation ❌ |
| E13 | Bank info berubah setelah withdrawal request | Snapshot di withdrawal record | Low | ✅ Snapshot preserved |
| E14 | Transfer ke rekening yang belum diverifikasi Flip | Flip reject (name mismatch) | Medium | Error ditangkap, tapi no notification ❌ |
| E15 | Multiple transfer ke tenant yang sama | Berhasil (tidak ada dedup) | Medium | Intentional, tapi perlu logging |

---

## 6. Concurrency Issues

### 6.1 Two Admins Approving Same Withdrawal

**Scenario:**
```
Time    Admin A                     Admin B                     DB
─────   ──────────────────────      ──────────────────────      ──────
t1      Read withdrawal             Read withdrawal             status=pending
t2      abort_if(status!=pending) ✓ abort_if(status!=pending) ✓
t3      Status → processing         Status → processing         ⚠️ RACE
t4      FlipPayoutProvider::send()  FlipPayoutProvider::send()  ⚠️ DOUBLE
t5      Update status=completed     Update status=completed     Last write wins
```

**Risk:** Double Flip API call → idempotency_key prevents double-spend, but wastes API calls.

**Fix:** `SELECT ... FOR UPDATE` on withdrawal row before status check.

### 6.2 Balance Check Passes, Flip Balance Depleted

**Scenario:**
```
Time    Ledger Check               Flip Balance                Action
─────   ──────────────────────     ──────────────────────      ──────
t1      Ledger: Rp 10M available   Flip: Rp 10M
t2                                               External transfer drains Flip to Rp 0
t3      Ledger check passes ✓      (now Rp 0)
t4                                 FlipPayoutProvider::send() → FAIL
t5      Status → failed, rollback
```

**Risk:** No money lost (rollback), but status=failed is confusing.

**Fix:** Re-check Flip balance inside `send()` before API call.

### 6.3 Webhook Arrives During DB Write

**Scenario:**
```
Time    Approve()                  Webhook                     DB
─────   ──────────────────────     ──────────────────────      ──────
t1      Status → processing
t2                                 Find by disburse_id ✓
t3      FlipPayoutProvider::send() (instant)
t4                                 Update status → completed
t5      Update status → completed                               (idempotent)
```

**Risk:** Low — idempotent. No data corruption.

### 6.4 Float Precision in Balance Calculation

**Scenario:**
```
Saldo: Rp 100000.10
Transfer: Rp 50000.05
Ledger: 100000.10 - 50000.05 = 50000.05000000001 (float)
```

**Risk:** Accumulated errors over thousands of transactions.

**Fix:** Use integer cents (sen) or `decimal(15,2)`.

---

## 7. Compliance & Legal

### 7.1 OJK Regulations

| Area | Status | Catatan |
|------|--------|---------|
| Flip punya lisensi PJP | ✅ | ZonaKasir sebagai aggregator |
| Transaction limits | ⚠️ | Flip enforce, ZonaKasir tidak track |
| Record keeping | ⚠️ | Ada di DB, tapi tidak ada retention policy |
| Consumer protection | ❌ | Tidak ada mekanisme dispute resolution |

### 7.2 Anti-Money Laundering (AML)

| Area | Status | Catatan |
|------|--------|---------|
| KYC on tenants | ❌ | Tidak ada verifikasi KYC |
| Transaction monitoring | ❌ | Tidak ada threshold reporting |
| Transfer pattern detection | ❌ | Tidak ada anomaly detection |
| Beneficiary verification | ⚠️ | Flip cek nama, tapi ZonaKasir tidak log hasilnya |
| CTR (Currency Transaction Report) | ❌ | Tidak ada automasi |

### 7.3 Receipt/Invoice

| Area | Status | Catatan |
|------|--------|---------|
| Transfer receipt | ❌ | Tidak ada receipt yang di-generate |
| Tenant notification | ⚠️ | `WithdrawalApproved` notification ada, tapi content kurang detail |
| Exportable report | ❌ | Admin page tidak ada export |
| Formal journal entries | ❌ | Ledger entries ≠ formal accounting |

---

## 8. Keputusan Bisnis — FINAL ✅

> **Status:** ✅ SEMUA PERTANYAAN SUDAH DIJAWAB (15 Jun 2026)
> **Decided by:** Product Owner

| # | Pertanyaan | Keputusan | Alasan |
|---|-----------|-----------|--------|
| 1 | **Admin transfer = reuse `withdrawals` table?** | ✅ **A: Reuse** (tambah column `type`) | Simpel, webhook langsung jalan |
| 2 | **Siapa yang bisa transfer?** | ✅ **Admin yang bisa login app** (belum ada role/permission untuk admin) | Sementara pakai existing auth, belum ada fitur user admin |
| 3 | **Perlu approval 2 admin untuk transfer besar?** | ✅ **B: Tidak, 1 admin cukup** | Sama seperti #2 — belum ada fitur multi-user admin |
| 4 | **Fee Rp 2.500/transfer ditanggung siapa?** | ✅ **B: Tenant** (dipotong dari nominal) | Biaya ditanggung tenant, bukan aplikator |
| 5 | **Apakah transfer mempengaruhi saldo ledger tenant?** | ✅ **A: Ya** (credit entry) | Double-entry integrity |
| 6 | **Minimum transfer?** | ✅ **B: Rp 50.000** (sesuai aturan Flip) | Konsisten dengan withdrawal minimum |
| 7 | **Maximum transfer per hari?** | ✅ **C: Ikut limit Flip** | Tidak perlu enforce sendiri |
| 8 | **Transfer ke rekening yang beda dari terdaftar?** | ✅ **A: Tidak boleh** | Prevent error, hanya pakai rekening di `about` |
| 9 | **Notifikasi ke tenant?** | ✅ **B: Email + database** | Lebih reliable |
| 10 | **Undo/cancel transfer?** | ✅ **A: Tidak bisa** (irreversible) | Simpler, Flip juga irreversible |

### Catatan Penting

- **Fee handling:** Admin input total debit → sistem hitung fee Rp 2.500 → dipotong dari nominal → tenant terima bersih (nominal - fee)
- **Contoh:** Admin input Rp 100.000 (total debit) → fee Rp 2.500 → tenant terima Rp 97.500
- **Role/permission admin:** Belum ada, pakai existing auth. Nanti kalau sudah ada multi-user admin, bisa di-upgrade ke permission-based.
- **Admin tidak bisa edit nominal withdrawal:** Amount sudah ditentukan saat tenant request (withdrawal) atau saat admin input (direct transfer). Admin hanya bisa approve/reject, tidak bisa mengubah jumlah uang yang dikirim ke Flip. Ini karena Flip menerima data amount apa adanya dari sistem kita — tidak ada override di sisi Flip.

### Fee Calculation — Definitive

```
Admin input:     Rp 100.000  ← total debit dari Flip (gross)
Fee:             Rp   2.500  ← Flip fee, DIPOTONG dari nominal
─────────────────────────────────────────────────────────────
Tenant terima:   Rp  97.500  ← bersih ke rekening tenant (net)
```

**Logic:** Admin menentukan total yang didebit dari Flip. Sistem otomatis potong fee, sisanya masuk ke rekening tenant.

**Kenapa model ini?** Karena Flip memotong fee dari total debit. Jadi kalau admin mau transfer Rp 100.000 ke tenant, admin harus input Rp 102.500 supaya tenant terima Rp 100.000. Tapi untuk kemudahan, admin input gross amount (Rp 100.000), fee dipotong (Rp 2.500), tenant terima Rp 97.500.

**Alternatif (jika admin mau tenant terima tepat Rp 100.000):** Admin input Rp 102.500 → fee Rp 2.500 → tenant terima Rp 100.000. Tapi ini kurang intuitive. **Decision: admin input gross amount.**

---

## 9. Technical Design

### 9.1 Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                    TRANSFER TO TENANT FLOW                       │
│                                                                  │
│  Admin (login app)                                               │
│    │                                                             │
│    ├─ 1. Buka /admin/disbursement                               │
│    ├─ 2. Klik "Transfer to Tenant"                               │
│    ├─ 3. Pilih tenant → auto-fill bank info dari About           │
│    ├─ 4. Input nominal (min Rp 50.000)                           │
│    │      Sistem tampilkan:                                      │
│    │      - Total debit dari Flip: Rp 100.000                    │
│    │      - Fee Flip: Rp 2.500                                   │
│    │      - Tenant terima: Rp 97.500                             │
│    ├─ 5. Klik "Konfirmasi Transfer"                              │
│    │      Double confirmation dialog:                            │
│    │      "Transfer Rp 97.500 ke [Nama] ([Bank] [Rekening])?"   │
│    │                                                             │
│    ▼                                                             │
│  DirectTransferService::transferToTenant()                       │
│    ├─ 6.  Validasi: tenant ada, bank info lengkap                │
│    ├─ 7.  Validasi: nominal >= 50.000                            │
│    ├─ 8.  Hitung: fee = 2.500, net = nominal - fee                  │
│    ├─ 9.  Cek Flip balance >= total (pre-flight)                 │
│    ├─ 10. DB::transaction:                                       │
│    │      ├── Withdrawal::create(type=admin_direct)              │
│    │      └── LedgerService::entry(debit, total)                 │
│    ├─ 11. FlipPayoutProvider::send(total)                        │
│    ├─ 12. Handle Flip response:                                  │
│    │      - DONE → status=completed                              │
│    │      - PENDING → status=processing (tunggu webhook)         │
│    │      - FAILED/CANCELLED → status=failed + rollback          │
│    ├─ 13. Notification::send(TransferReceived) ke tenant         │
│    └─ 14. Return Withdrawal model                                │
│                                                                  │
│  Flip Webhook (async)                                            │
│    ├─ 15. Verify HMAC signature                                  │
│    ├─ 16. Find Withdrawal by disburse_id                         │
│    ├─ 17. Update status (DONE→completed, FAILED→failed)          │
│    ├─ 18. Send email notification to TENANT (via webhook)        │
│    └─ 19. Send notification to admin if failed                   │
└─────────────────────────────────────────────────────────────────┘
```

### 9.2 Database Migration

```php
<?php
// database/migrations/tenant/2026_06_15_000001_add_admin_direct_to_withdrawals.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            // Type: distinguish tenant-initiated vs admin-initiated
            $table->enum('type', ['tenant_request', 'admin_direct'])
                ->default('tenant_request')
                ->after('id');

            // Who initiated (null for tenant_request, admin_id for admin_direct)
            $table->unsignedBigInteger('initiated_by')->nullable()
                ->after('requested_by')
                ->comment('Admin ID who initiated direct transfer');

            // Flip fee snapshot
            $table->decimal('fee_amount', 15, 2)->nullable()
                ->after('amount')
                ->comment('Flip fee charged for this transfer');

            // Internal notes (admin only, not visible to tenant)
            $table->text('internal_notes')->nullable()
                ->after('notes')
                ->comment('Internal notes, not visible to tenant');

            // Indexes for performance
            $table->index('type');
            $table->index('initiated_by');
        });
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn(['type', 'initiated_by', 'fee_amount', 'internal_notes']);
        });
    }
};
```

### 9.3 Service Design — DirectTransferService

```php
<?php
// app/Services/Tenants/DirectTransferService.php

namespace App\Services\Tenants;

use App\Models\Tenants\About;
use App\Models\Tenants\Withdrawal;
use App\Models\Tenants\LedgerEntry;
use App\Notifications\TransferCompleted;
use App\Notifications\TransferReceived;
use App\Notifications\TransferFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class DirectTransferService
{
    private const FEE_AMOUNT = 2500; // Flip fee per transfer
    private const MIN_TRANSFER = 50000;
    private const MAX_TRANSFER = 50000000; // Rp 50 juta (Flip limit, sesuaikan jika berubah)

    public function __construct(
        private readonly LedgerService $ledger,
        private readonly DisbursementProvider $disbursement,
        private readonly FlipDataService $flipData,
    ) {}

    /**
     * Transfer funds directly to tenant's bank account via Flip.
     *
     * @param int    $amount    Total debit dari Flip (gross amount, termasuk fee)
     * @param int    $adminId   Admin yang melakukan transfer
     * @param string $notes     Catatan opsional
     *
     * @return Withdrawal
     *
     * @throws \InvalidArgumentException
     * @throws InsufficientBalanceException
     * @throws DisbursementFailedException
     */
    public function transferToTenant(
        int $amount,
        int $adminId,
        string $notes = '',
    ): Withdrawal {
        // ── Step 1: Validate tenant bank info ──
        $about = About::first();
        if (! $about) {
            throw new \InvalidArgumentException(
                'Tenant belum mengatur informasi bank. Silakan lengkapi profil terlebih dahulu.'
            );
        }

        if (empty($about->bank_account_number) || empty($about->bank_code)) {
            throw new \InvalidArgumentException(
                'Informasi bank tidak lengkap. Silakan lengkapi: bank_name, bank_code, bank_account_number.'
            );
        }

        // ── Step 2: Validate amount ──
        if ($amount < self::MIN_TRANSFER) {
            throw new \InvalidArgumentException(
                "Minimal transfer adalah Rp " . number_format(self::MIN_TRANSFER, 0, ',', '.')
            );
        }

        if ($amount > self::MAX_TRANSFER) {
            throw new \InvalidArgumentException(
                "Maksimal transfer adalah Rp " . number_format(self::MAX_TRANSFER, 0, ',', '.')
            );
        }

        // ── Step 3: Calculate fee & net amount ──
        $feeAmount = self::FEE_AMOUNT;
        $netAmount = $amount - $feeAmount; // Tenant terima ini

        if ($netAmount <= 0) {
            throw new \InvalidArgumentException(
                'Nominal terlalu kecil. Minimal harus lebih dari fee Rp ' . number_format($feeAmount, 0, ',', '.')
            );
        }

        // ── Step 4: Check Flip balance (pre-flight) ──
        $flipBalance = $this->flipData->getBalance();
        if ($flipBalance === null) {
            throw new DisbursementFailedException(
                'Gagal memeriksa saldo Flip. Silakan coba lagi.'
            );
        }

        if (($flipBalance['balance'] ?? 0) < $amount) {
            throw new InsufficientBalanceException(
                'Saldo Flip tidak mencukupi. '
                . 'Dibutuhkan: Rp ' . number_format($amount, 0, ',', '.')
                . '. Tersedia: Rp ' . number_format($flipBalance['balance'] ?? 0, 0, ',', '.')
            );
        }

        // ── Step 5: Check ledger balance (with row lock) ──
        $ledgerBalance = $this->ledger->getCurrentBalanceWithLock();
        if ($ledgerBalance < $amount) {
            throw new InsufficientBalanceException(
                'Saldo ledger tidak mencukupi. '
                . 'Dibutuhkan: Rp ' . number_format($amount, 0, ',', '.')
                . '. Tersedia: Rp ' . number_format($ledgerBalance, 0, ',', '.')
            );
        }

        // ── Step 6: Create withdrawal record + ledger entry (ATOMIC) ──
        $idempotencyKey = 'dt-' . now()->timestamp . '-' . substr(md5(random_bytes(8)), 0, 8);

        $withdrawal = DB::transaction(function () use (
            $amount, $netAmount, $feeAmount, $adminId, $notes, $idempotencyKey, $about
        ) {
            $withdrawal = Withdrawal::create([
                'type'                => 'admin_direct',
                'amount'              => $netAmount,       // Net amount (yang diterima tenant)
                'fee_amount'          => $feeAmount,       // Fee Flip
                'bank_name'           => $about->bank_name,
                'bank_account_name'   => $about->bank_account_name,
                'bank_account_number' => $about->bank_account_number,
                'bank_code'           => $about->bank_code,
                'status'              => 'processing',
                'idempotency_key'     => $idempotencyKey,
                'requested_by'        => $adminId,
                'initiated_by'        => $adminId,
                'approved_by'         => $adminId,
                'internal_notes'      => $notes,
                'processed_at'        => now(),
            ]);

            // Ledger: debit total amount (gross)
            $this->ledger->entry(
                ledgerableType: Withdrawal::class,
                ledgerableId: $withdrawal->id,
                entryType: 'debit',
                amount: $amount,
                description: "Transfer ke tenant (net: {$netAmount}, fee: {$feeAmount})",
                referenceType: 'transfer_to_tenant',
                referenceId: $withdrawal->id,
            );

            return $withdrawal;
        });

        // ── Step 7: Send to Flip ──
        try {
            $result = $this->disbursement->send([
                'bank_code'       => $about->bank_code,
                'account_number'  => $about->bank_account_number,
                'account_name'    => $about->bank_account_name,
                'amount'          => $amount,       // Gross amount (total debit)
                'remark'          => "Zonakasir Transfer #{$withdrawal->id}",
                'idempotency_key' => $idempotencyKey,
            ]);

            // ── Step 8: Handle Flip response status ──
            $newStatus = match ($result['status'] ?? 'pending') {
                'DONE'    => 'completed',
                'PENDING' => 'processing',
                'FAILED', 'CANCELLED' => 'failed',
                default   => 'processing',
            };

            $withdrawal->update([
                'status'            => $newStatus,
                'disburse_id'       => $result['id'] ?? null,
                'disburse_response' => $result,
                'processed_at'      => now(),
            ]);

            // ── Step 9: Rollback if failed ──
            if ($newStatus === 'failed') {
                $this->rollbackLedger($withdrawal, $amount);
            }

            // ── Step 10: Notify admin (transfer completed) ──
            if ($newStatus === 'completed') {
                // Notify admin who initiated the transfer
                Notification::send(
                    $withdrawal->requestedBy,
                    new TransferCompleted($withdrawal)
                );
            }

        } catch (\Throwable $e) {
            Log::error('DirectTransfer: Flip API failed', [
                'withdrawal_id' => $withdrawal->id,
                'error'         => $e->getMessage(),
            ]);

            $withdrawal->update([
                'status'            => 'failed',
                'disburse_response' => ['error' => $e->getMessage()],
            ]);

            $this->rollbackLedger($withdrawal, $amount);

            // Notify admin of failure
            Notification::send(
                $withdrawal->requestedBy,
                new TransferFailed($withdrawal)
            );

            throw new DisbursementFailedException(
                'Transfer gagal: ' . $e->getMessage(),
                previous: $e,
            );
        }

        return $withdrawal->fresh();
    }

    private function rollbackLedger(Withdrawal $withdrawal, int $grossAmount): void
    {
        $this->ledger->entry(
            ledgerableType: Withdrawal::class,
            ledgerableId: $withdrawal->id,
            entryType: 'credit',
            amount: $grossAmount,
            description: "Rollback transfer gagal #{$withdrawal->id}",
            referenceType: 'transfer_rollback',
            referenceId: $withdrawal->id,
        );
    }
}
```

### 9.4 Notifications

```php
<?php
// app/Notifications/TransferCompleted.php (ke admin)

namespace App\Notifications;

use App\Models\Tenants\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TransferCompleted extends Notification
{
    use Queueable;

    public function __construct(public Withdrawal $withdrawal) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $grossAmount = number_format(
            $this->withdrawal->amount + ($this->withdrawal->fee_amount ?? 0), 0, ',', '.'
        );
        $netAmount = number_format($this->withdrawal->amount, 0, ',', '.');

        return [
            'message'         => "Transfer ke tenant BERHASIL: Rp {$netAmount}",
            'withdrawal_id'   => $this->withdrawal->id,
            'transaction_id'  => $this->withdrawal->disburse_id,
            'gross_amount'    => $grossAmount,
            'fee_amount'      => $this->withdrawal->fee_amount ?? 0,
            'net_amount'      => $this->withdrawal->amount,
            'bank_account'    => $this->withdrawal->bank_account_number,
            'status'          => $this->withdrawal->status,
            'timestamp'       => now()->toISOString(),
        ];
    }
}

// app/Notifications/TransferReceived.php (ke tenant via webhook)

namespace App\Notifications;

use App\Models\Tenants\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransferReceived extends Notification
{
    use Queueable;

    public function __construct(public Withdrawal $withdrawal) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $grossAmount = number_format(
            $this->withdrawal->amount + ($this->withdrawal->fee_amount ?? 0), 0, ',', '.'
        );
        $feeAmount = number_format($this->withdrawal->fee_amount ?? 0, 0, ',', '.');
        $netAmount = number_format($this->withdrawal->amount, 0, ',', '.');
        $status = ucfirst($this->withdrawal->status);
        $date = $this->withdrawal->processed_at->format('d/m/Y H:i');
        $transactionId = $this->withdrawal->disburse_id ?? $this->withdrawal->id;

        return (new MailMessage)
            ->subject("✅ Transfer Diterima - Rp {$netAmount}")
            ->greeting("Halo {$notifiable->name}!")
            ->line("Anda telah menerima transfer dari ZonaKasir.")
            ->line("**Detail Transaksi:**")
            ->line("──────────────────────────────────────")
            ->line("**ID Transaksi:** #{$transactionId}")
            ->line("**Tanggal:** {$date}")
            ->line("**Status:** {$status}")
            ->line("──────────────────────────────────────")
            ->line("**Rincian Keuangan:**")
            ->line("• Total Debit: Rp {$grossAmount}")
            ->line("• Biaya Transfer: Rp {$feeAmount}")
            ->line("• **Yang Anda Terima: Rp {$netAmount}**")
            ->line("──────────────────────────────────────")
            ->line("**Rekening Tujuan:**")
            ->line("• Bank: {$this->withdrawal->bank_name}")
            ->line("• No. Rekening: {$this->withdrawal->bank_account_number}")
            ->line("• Atas Nama: {$this->withdrawal->bank_account_name}")
            ->line("──────────────────────────────────────")
            ->line("Jika ada pertanyaan, silakan hubungi admin ZonaKasir.")
            ->action('Lihat Riwayat Transaksi', url('/admin/withdrawal'))
            ->line('Terima kasih telah menggunakan ZonaKasir.');
    }

    public function toArray($notifiable): array
    {
        return [
            'message'         => 'Transfer diterima: Rp ' . number_format($this->withdrawal->amount, 0, ',', '.'),
            'withdrawal_id'   => $this->withdrawal->id,
            'transaction_id'  => $this->withdrawal->disburse_id ?? $this->withdrawal->id,
            'gross_amount'    => $this->withdrawal->amount + ($this->withdrawal->fee_amount ?? 0),
            'fee_amount'      => $this->withdrawal->fee_amount ?? 0,
            'net_amount'      => $this->withdrawal->amount,
            'bank_name'       => $this->withdrawal->bank_name,
            'bank_account'    => $this->withdrawal->bank_account_number,
            'status'          => $this->withdrawal->status,
            'processed_at'    => $this->withdrawal->processed_at?->toISOString(),
        ];
    }
}
```

### 9.4.1 Email Preview — Transfer Received (ke Tenant)

```
┌─────────────────────────────────────────────────────────────────┐
│  From: noreply@zonakasir.com                                    │
│  To: tenant@email.com                                           │
│  Subject: ✅ Transfer Diterima - Rp 97.500                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Halo Budi Santoso!                                             │
│                                                                  │
│  Anda telah menerima transfer dari ZonaKasir.                   │
│                                                                  │
│  Detail Transaksi:                                               │
│  ──────────────────────────────────────                         │
│  ID Transaksi: #1234567890123456789                             │
│  Tanggal: 15/06/2026 14:30                                      │
│  Status: Completed                                               │
│  ──────────────────────────────────────                         │
│  Rincian Keuangan:                                               │
│  • Total Debit: Rp 100.000                                      │
│  • Biaya Transfer: Rp 2.500                                     │
│  • Yang Anda Terima: Rp 97.500                                  │
│  ──────────────────────────────────────                         │
│  Rekening Tujuan:                                                │
│  • Bank: BCA                                                    │
│  • No. Rekening: 1234567890                                     │
│  • Atas Nama: PT Toko Maju                                      │
│  ──────────────────────────────────────                         │
│  Jika ada pertanyaan, silakan hubungi admin ZonaKasir.          │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │        Lihat Riwayat Transaksi                          │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  Terima kasih telah menggunakan ZonaKasir.                      │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 9.4.2 Email Preview — Transfer Failed (ke Admin)

```
┌─────────────────────────────────────────────────────────────────┐
│  From: noreply@zonakasir.com                                    │
│  To: admin@zonakasir.com                                        │
│  Subject: ❌ Transfer ke Tenant GAGAL - Rp 97.500               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Halo Admin ZonaKasir,                                          │
│                                                                  │
│  Transfer ke tenant GAGAL.                                       │
│                                                                  │
│  Detail Transaksi:                                               │
│  ──────────────────────────────────────                         │
│  ID Transaksi: #1234567890123456789                             │
│  Tanggal: 15/06/2026 14:30                                      │
│  Status: Failed                                                  │
│  ──────────────────────────────────────                         │
│  Rincian Keuangan:                                               │
│  • Total Debit: Rp 100.000                                      │
│  • Biaya Transfer: Rp 2.500                                     │
│  • Yang Diterima: Rp 97.500                                     │
│  ──────────────────────────────────────                         │
│  Rekening Tujuan:                                                │
│  • Bank: BCA                                                    │
│  • No. Rekening: 1234567890                                     │
│  ──────────────────────────────────────                         │
│  Error: Insufficient balance in Flip account                    │
│  ──────────────────────────────────────                         │
│  Silakan cek Flip dashboard untuk detail lebih lanjut.          │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │        Lihat Detail Transaksi                           │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

// app/Notifications/TransferFailed.php (ke admin jika gagal)

namespace App\Notifications;

use App\Models\Tenants\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransferFailed extends Notification
{
    use Queueable;

    public function __construct(public Withdrawal $withdrawal) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $grossAmount = number_format(
            $this->withdrawal->amount + ($this->withdrawal->fee_amount ?? 0), 0, ',', '.'
        );
        $netAmount = number_format($this->withdrawal->amount, 0, ',', '.');
        $error = $this->withdrawal->disburse_response['error'] ?? 'Unknown error';

        return [
            'message'         => "Transfer ke tenant GAGAL: Rp {$netAmount}",
            'withdrawal_id'   => $this->withdrawal->id,
            'gross_amount'    => $grossAmount,
            'net_amount'      => $netAmount,
            'error'           => $error,
            'bank_account'    => $this->withdrawal->bank_account_number,
            'timestamp'       => now()->toISOString(),
        ];
    }
}
```

### 9.5 Config Additions

```php
<?php
// config/flip.php (additions)

return [
    // ... existing config ...

    'withdrawal_approval' => [
        'auto_approve_max'  => env('FLIP_WITHDRAWAL_AUTO_APPROVE_MAX', 5000000),
        'single_admin_max'  => env('FLIP_WITHDRAWAL_SINGLE_ADMIN_MAX', 25000000),
    ],

    // NEW: Direct transfer config
    'direct_transfer' => [
        'enabled'   => env('FLIP_DIRECT_TRANSFER_ENABLED', true),
        'min_amount' => env('FLIP_DIRECT_TRANSFER_MIN', 50000),   // Rp 50.000
        'fee_amount' => env('FLIP_DIRECT_TRANSFER_FEE', 2500),    // Rp 2.500
    ],
];
```

### 9.6 Security Fixes

```php
<?php
// FIX 1: Webhook HMAC verification
// app/Http/Controllers/Api/Webhooks/FlipWebhookController.php

public function handle(Request $request)
{
    $payload = $request->all();

    // ── Verify HMAC signature (if Flip provides it) ──
    $signature = $request->header('X-Flip-Signature');
    if ($signature) {
        $webhookSecret = config('flip.webhook_secret');
        $expected = hash_hmac('sha256', $request->getContent(), $webhookSecret);
        if (! hash_equals($expected, $signature)) {
            Log::warning('Flip webhook: Invalid HMAC signature');
            return response()->json(['message' => 'Invalid signature'], 401);
        }
    }

    // ── Fallback: verify token in body ──
    $webhookToken = config('flip.webhook_token');
    $incomingToken = $payload['token'] ?? null;
    if (! $webhookToken || $incomingToken !== $webhookToken) {
        Log::warning('Flip webhook: Invalid token');
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // ── Validate required fields ──
    $disbursementId = $payload['id'] ?? null;
    $status = $payload['status'] ?? null;
    if (! $disbursementId || ! $status) {
        return response()->json(['message' => 'Missing required fields'], 400);
    }

    // ... rest of handler ...
}

// FIX 2: Row locking in approve()
// app/Services/Tenants/WithdrawalService.php

public function approve(int $withdrawalId, int $approvedBy): Withdrawal
{
    $withdrawal = Withdrawal::where('id', $withdrawalId)
        ->lockForUpdate()  // SELECT ... FOR UPDATE
        ->firstOrFail();

    abort_if($withdrawal->status !== 'pending', 400, 'Withdrawal already processed');

    // ... rest of approve logic ...
}

// FIX 3: Flip returned status handling
// app/Services/Tenants/WithdrawalService.php (line ~140)

$result = $this->disbursement->send([...]);

// DON'T assume completed — check actual status
$newStatus = match ($result['status'] ?? 'pending') {
    'DONE'    => 'completed',
    'PENDING' => 'processing',
    'FAILED', 'CANCELLED' => 'failed',
    default   => 'processing',
};

$withdrawal->update([
    'status'            => $newStatus,
    'disburse_id'       => $result['id'] ?? null,
    'disburse_response' => $result,
    'approved_by'       => $approvedBy,
    'processed_at'      => now(),
]);

// FIX 4: About null check
// app/Services/Tenants/WithdrawalService.php (line ~37)

$about = About::first();
if (! $about) {
    throw new \InvalidArgumentException('Tenant bank info not configured');
}

// FIX 5: Webhook rate limiting
// routes/api.php

Route::post('/webhooks/flip', [...])
    ->middleware('throttle:100,1')  // 100 requests per minute
    ->name('webhooks.flip');

// FIX 6: Atomic balance check + withdrawal creation
// app/Services/Tenants/LedgerService.php

public function getCurrentBalanceWithLock(): float
{
    // Use GET_LOCK to prevent concurrent balance reads
    $lockName = 'ledger_balance_lock';
    DB::select("SELECT GET_LOCK(?, 10) AS lock_acquired", [$lockName]);
    
    try {
        return $this->getCurrentBalance();
    } finally {
        DB::select("SELECT RELEASE_LOCK(?)", [$lockName]);
    }
}

// FIX 7: Webhook sends notification to tenant
// app/Http/Controllers/Api/Webhooks/FlipWebhookController.php (after status update)

if ($newStatus === 'completed') {
    Notification::send(
        $withdrawal->requestedBy,
        new TransferReceived($withdrawal)
    );
}
```

### 9.7 UI Wireframe — Transfer Form

```
┌─────────────────────────────────────────────────────────────────┐
│  Transfer to Tenant                                         [X] │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Tenant                                                         │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ Select tenant...                                   ▼    │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ Bank Information (auto-filled)                          │    │
│  │ ┌───────────────────────────────────────────────────┐  │    │
│  │ │ Bank:     BCA                                     │  │    │
│  │ │ Account:  1234567890                              │  │    │
│  │ │ Name:     PT Toko Maju                            │  │    │
│  │ └───────────────────────────────────────────────────┘  │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  Nominal (total debit dari Flip)                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ Rp  100.000                                            │    │
│  └─────────────────────────────────────────────────────────┘    │
│  Min. Rp 50.000                                                 │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ Fee Breakdown                                          │    │
│  │ ┌───────────────────────────────────────────────────┐  │    │
│  │ │ Total debit dari Flip:             Rp    100.000   │  │    │
│  │ │ Fee Flip:                          Rp      2.500   │  │    │
│  │ │ ─────────────────────────────────────────────────  │  │    │
│  │ │ Tenant terima (net):               Rp     97.500   │  │    │
│  │ └───────────────────────────────────────────────────┘  │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  Internal Notes (optional)                                       │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                                                         │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │            [Cancel]        [Transfer Rp 97.500]         │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘

Confirmation Dialog:
┌─────────────────────────────────────────────────────────────────┐
│  ⚠️ Konfirmasi Transfer                                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Anda akan mentransfer:                                         │
│                                                                  │
│    Total debit:   Rp 100.000                                    │
│    Fee:           Rp 2.500                                      │
│    ─────────────────────────────────────                        │
│    Tenant terima: Rp 97.500                                     │
│                                                                  │
│  Ke: PT Toko Maju                                               │
│      BCA - 1234567890                                           │
│                                                                  │
│  ⚠️ Transfer ini TIDAK BISA dibatalkan setelah dikirim.          │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │      [Batal]                    [Ya, Transfer Sekarang]  │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

### 9.8 Rollback Strategy

```
SCENARIO: Flip transfer GAGAL setelah ledger sudah di-debit

Current (existing code):
  1. Withdrawal::create(status=processing)
  2. LedgerService::entry(debit)
  3. FlipPayoutProvider::send() → GAGAL
  4. Catch: status=failed + LedgerService::entry(credit) → rollback ✅

DirectTransferService (new):
  1. Withdrawal::create(status=processing, type=admin_direct)
  2. LedgerService::entry(debit, totalDebit)
  3. FlipPayoutProvider::send() → GAGAL
  4. Catch: status=failed + rollbackLedger() → credit totalDebit ✅

SCENARIO: Flip transfer SUKSES tapi webhook GAGAL

  1. FlipPayoutProvider::send() → returns status=pending
  2. Withdrawal::update(status=processing)
  3. Webhook never arrives
  4. Withdrawal stuck in "processing" forever

  MITIGATION:
  - Cron job: check withdrawals with status=processing older than 24h
  - Call FlipPayoutProvider::status() to check actual status
  - Update accordingly
  - (Not implemented in MVP — manual intervention)

SCENARIO: Flip transfer SUKSES tapi DB update GAGAL

  1. FlipPayoutProvider::send() → returns status=DONE
  2. DB::transaction → network failure → rollback
  3. Flip disbursed money, but DB still shows "processing"
  4. Webhook arrives → updates to "completed" ✅

  MITIGATION:
  - Webhook is the source of truth for final status
  - DB transaction failure is rare (local DB)
  - If webhook also fails → manual intervention
```

### 9.9 Ledger Entries — Definitive

```
Transfer Rp 100.000 (gross) ke tenant "Toko Maju":

WITHDRAWAL TABLE:
┌────┬────────┬─────────────┬───────┬─────────────┬────────────┐
│ ID │ type   │ amount(net) │ fee   │ status      │ initiated_by│
├────┼────────┼─────────────┼───────┼─────────────┼────────────┤
│ 123│ admin_ │ 97,500      │ 2,500 │ processing  │ admin_id   │
│    │ direct │             │       │             │            │
└────┴────────┴─────────────┴───────┴─────────────┴────────────┘

LEDGER ENTRIES:
┌────┬───────────────┬─────────┬────────┬──────────────┬─────────────────────────┐
│ ID │ ledgerable    │ type    │ amount │ balance_after│ description             │
├────┼───────────────┼─────────┼────────┼──────────────┼─────────────────────────┤
│ 456│ Withdrawal#123│ debit   │100,000 │ 500,000      │ Transfer ke Toko Maju   │
│    │               │         │        │              │ (net:97.5K, fee:2.5K)   │
└────┴───────────────┴─────────┴────────┴──────────────┴─────────────────────────┘

SCENARIO: Transfer gagal → rollback
┌────┬───────────────┬─────────┬────────┬──────────────┬─────────────────────────┐
│ ID │ ledgerable    │ type    │ amount │ balance_after│ description             │
├────┼───────────────┼─────────┼────────┼──────────────┼─────────────────────────┤
│ 456│ Withdrawal#123│ debit   │100,000 │ 500,000      │ Transfer ke Toko Maju   │
│ 457│ Withdrawal#123│ credit  │100,000 │ 600,000      │ Rollback transfer gagal │
└────┴───────────────┴─────────┴────────┴──────────────┴─────────────────────────┘
```

### 9.10 File Changes

```
MODIFY:
├── app/Models/Tenants/Withdrawal.php              — tambah scope, relation
├── app/Services/Tenants/WithdrawalService.php     — fix status bug, tambah lock
├── app/Services/Tenants/FlipPayoutProvider.php     — fix (int) cast
├── app/Services/Tenants/LedgerService.php          — fix GET_LOCK timeout
├── app/Filament/Admin/Pages/Disbursement.php       — tambah transfer action
├── resources/views/filament/admin/pages/disbursement.blade.php — form transfer
├── app/Http/Controllers/Api/Webhooks/FlipWebhookController.php — HMAC, validation
├── config/flip.php                                 — tambah direct_transfer config
├── routes/api.php                                  — tambah throttle ke webhook

NEW:
├── app/Services/Tenants/DirectTransferService.php
├── app/Notifications/TransferCompleted.php
├── app/Notifications/TransferReceived.php
├── app/Notifications/TransferFailed.php
├── database/migrations/tenant/2026_06_15_000001_add_admin_direct_to_withdrawals.php
├── tests/Feature/Tenants/DirectTransferTest.php
├── tests/Feature/Tenants/DirectTransferWebhookTest.php

DELETE (dead code):
├── app/Http/Middleware/AutoApproveWithdrawal.php (unused, not registered)
```

---

## 10. Implementasi & Testing Plan

### 🚨 MANDATORY RULE: Double Verification Per Phase

> **Setiap phase development WAJIB diverifikasi 2x sebelum lanjut ke phase berikutnya.**
> 
> **Tujuan:** Mencegah bugs masuk ke production. Zero tolerance untuk bugs di fitur keuangan.
>
> **Siapa yang verifikasi:** Developer yang sama (self-review) + 1 orang reviewer (bisa team lead atau peer developer).

#### Verification Flow Per Phase

```
Phase N: Develop
    │
    ├── 1. Self-Review (Developer)
    │      ├── Cek semua task di phase selesai
    │      ├── Jalankan semua tests → PASTIKAN pass
    │      ├── Cek code sesuai dokumentasi
    │      └── Review negative scenarios → pastikan semua ter-handle
    │
    ├── 2. Peer Review (Reviewer)
    │      ├── Review code changes
    │      ├── Cek security checklist
    │      ├── Cek edge cases
    │      └── Approve atau request changes
    │
    ├── 3. Jika ada request changes → fix dulu, ulang dari step 1
    │
    └── 4. Jika APPROVED → lanjut ke Phase N+1
```

#### Verification Checklist Per Phase

```
Phase Verification Checklist:
├── [ ] Semua task di phase selesai
├── [ ] Semua tests pass (unit + integration)
├── [ ] Code review selesai (self + peer)
├── [ ] Security checklist pass
├── [ ] Negative scenarios ter-handle
├── [ ] Edge cases ter-handle
├── [ ] Tidak ada regression ke fitur existing
├── [ ] Documentation updated jika ada perubahan
└── [ ] Sign-off dari reviewer
```

#### Gate Criteria: Phase N → Phase N+1

| Kriteria | Wajib? | Keterangan |
|----------|--------|------------|
| Semua tests pass | ✅ Wajib | Tidak boleh ada test gagal |
| Code review approved | ✅ Wajib | Minimal 1 reviewer approve |
| Zero critical bugs | ✅ Wajib | Critical = money loss, data corruption, security breach |
| Zero high bugs | ⚠️ Harus di-fix sebelum merge | Bisa di-fix di phase berikutnya jika low risk |
| Documentation updated | ✅ Wajib | Jika ada perubahan design/behavior |

---

### 10.1 Phase 0: Fix Pre-requisite Bugs (1 hari)

| Task | File | Effort |
|------|------|--------|
| Fix Flip returned status handling | `WithdrawalService:142-148` | 2 jam |
| Fix `(int)` cast in FlipPayoutProvider | `FlipPayoutProvider:19` | 1 jam |
| Add HMAC webhook verification | `FlipWebhookController` | 2 jam |
| Add rate limit to webhook endpoint | `routes/api.php` | 0.5 jam |
| Add `About` null check | `WithdrawalService:37-57` | 0.5 jam |
| Add `SELECT ... FOR UPDATE` | `WithdrawalService:115` | 1 jam |
| Add Flip balance pre-check | `WithdrawalService:130` | 1 jam |
| Write tests for all fixes | `tests/` | 2 jam |

#### Phase 0 Verification Checklist

```
Phase 0 Verification:
├── [ ] Semua 8 tasks selesai
├── [ ] Tests pass: FlipPayoutTest (existing, tidak regression)
├── [ ] Tests pass: FlipWebhookTest (existing, tidak regression)
├── [ ] Tests pass: FlipDataServiceTest (existing, tidak regression)
├── [ ] Tests pass: Tests baru untuk fix yang ditambah
├── [ ] Manual test: Withdrawal flow existing masih jalan
├── [ ] Manual test: Webhook HMAC verification jalan
├── [ ] Code review selesai
├── [ ] Documentation updated (Section 2.3 ditandai ✅)
└── [ ] Sign-off dari reviewer
```

### 10.2 Phase 1: Core Transfer Feature (1.5 hari)

| Task | File | Effort |
|------|------|--------|
| Migration: tambah column ke withdrawals | `migrations/` | 0.5 jam |
| `DirectTransferService` | New file | 4 jam |
| Admin transfer form (Filament) | `Disbursement.php` + blade | 4 jam |
| `TransferReceived` notification | New file | 0.5 jam |
| Unit tests | `tests/Feature/` | 3 jam |

#### Phase 1 Verification Checklist

```
Phase 1 Verification:
├── [ ] Semua 5 tasks selesai
├── [ ] Migration run成功 tanpa error
├── [ ] Tests pass: DirectTransferTest — happy path
├── [ ] Tests pass: DirectTransferTest — insufficient Flip balance
├── [ ] Tests pass: DirectTransferTest — invalid bank account
├── [ ] Tests pass: DirectTransferTest — tenant not found
├── [ ] Tests pass: DirectTransferTest — amount below minimum
├── [ ] Tests pass: DirectTransferTest — amount above maximum
├── [ ] Tests pass: DirectTransferTest — about table empty
├── [ ] Tests pass: DirectTransferTest — idempotency
├── [ ] Tests pass: DirectTransferTest — admin notified on success
├── [ ] Tests pass: DirectTransferTest — admin notified on failure
├── [ ] Tests pass: LedgerService — balance after transfer
├── [ ] Tests pass: LedgerService — concurrent transfers with lock
├── [ ] Manual test: Admin bisa submit transfer via form
├── [ ] Manual test: Fee breakdown tampil benar
├── [ ] Manual test: Confirmation dialog muncul
├── [ ] Manual test: Email notification terkirim ke tenant
├── [ ] Code review selesai
├── [ ] Documentation updated (Section 9 ditandai ✅)
└── [ ] Sign-off dari reviewer
```

### 10.3 Phase 2: Polish & Edge Cases (0.5 hari)

| Task | File | Effort |
|------|------|--------|
| Fee display + handling | Service + view | 2 jam |
| Transfer history view | Blade view | 1 jam |
| Batch transfer (optional) | Service + view | 3 jam |
| Integration tests | `tests/` | 2 jam |

#### Phase 2 Verification Checklist

```
Phase 2 Verification:
├── [ ] Semua 4 tasks selesai
├── [ ] Tests pass: Integration tests (full flow)
├── [ ] Tests pass: Edge case tests
├── [ ] Manual test: Transfer history tampil benar
├── [ ] Manual test: Fee breakdown konsisten di semua view
├── [ ] Manual test: Batch transfer (jika diimplementasi)
├── [ ] Manual test: Transfer ke tenant yang sama berulang
├── [ ] Manual test: Transfer di midnight (date boundary)
├── [ ] Manual test: Transfer dengan special chars di bank name
├── [ ] Manual test: Transfer ke deactivated tenant
├── [ ] Manual test: Transfer saat Flip down
├── [ ] Manual test: Transfer dengan float amount
├── [ ] Code review selesai
├── [ ] Documentation updated (final)
├── [ ] Semua tests pass (regression)
└── [ ] Sign-off dari reviewer
```

### 10.4 Final Verification: Pre-Production Checklist

### 10.5 Testing Checklist (Detailed)

```
Unit Tests:
├── DirectTransferService::transferToTenant() — happy path
├── DirectTransferService::transferToTenant() — insufficient Flip balance
├── DirectTransferService::transferToTenant() — invalid bank account
├── DirectTransferService::transferToTenant() — tenant not found
├── DirectTransferService::transferToTenant() — amount below minimum
├── DirectTransferService::transferToTenant() — amount above maximum
├── DirectTransferService::transferToTenant() — about table empty
├── DirectTransferService::transferToTenant() — idempotency
├── DirectTransferService::transferToTenant() — admin notified on success
├── DirectTransferService::transferToTenant() — admin notified on failure
├── LedgerService — balance after transfer
├── LedgerService — concurrent transfers with lock
└── FlipPayoutProvider — (int) truncation fix

Integration Tests:
├── Admin submit transfer → Flip API → webhook → status completed
├── Admin submit transfer → Flip API fail → status failed → rollback + admin notification
├── Webhook duplicate → idempotent
├── Webhook invalid signature → 401
├── Webhook missing token → 401
├── Webhook rate limit → 429
├── Webhook → tenant receives email notification
├── Two admins approve simultaneously → single Flip disbursement
└── Transfer with pending withdrawal → both process independently

Security Tests:
├── Non-admin cannot access transfer form (403)
├── Tenant cannot approve other tenant's withdrawal (IDOR)
├── Webhook spoofing blocked (HMAC)
├── Rate limit enforced on webhook
├── Input validation: negative amount blocked
├── Input validation: oversized amount blocked
├── Bank code validation
└── SQL injection on bank fields (Eloquent safe)

Edge Case Tests:
├── Transfer minimum amount
├── Transfer maximum amount
├── Transfer at midnight
├── Transfer with special chars in bank name
├── Transfer to deactivated tenant
├── Transfer when Flip is down
├── Transfer with float amount (100000.50)
└── Multiple transfers to same tenant
```

---

## Appendix A: Flip API Reference

### POST /v2/disbursement

**Request:**
```json
{
    "bank_code": "bca",
    "account_number": "1234567890",
    "amount": 100000,
    "remark": "Zonakasir Transfer",
    "idempotency_key": "unique-key-123"
}
```

**Response (200):**
```json
{
    "id": "1234567890123456789",
    "status": "pending",
    "amount": 100000,
    "bank_code": "bca",
    "account_number": "1234567890",
    "remark": "Zonakasir Transfer"
}
```

**Status Values:** `pending`, `DONE`, `FAILED`, `CANCELLED`

### GET /v2/general/balance

**Response:**
```json
{
    "balance": 10000000,
    "pending_balance": 5000000,
    "currency": "IDR"
}
```

### Webhook Payload

```json
{
    "id": "1234567890123456789",
    "status": "DONE",
    "amount": "100000",
    "token": "your-webhook-token"
}
```

**Signature Header:** `X-Flip-Signature` (HMAC-SHA256)

---

## Appendix B: Cost Analysis

| Item | Cost |
|------|------|
| Flip fee per transfer | Rp 2.500 |
| 100 transfers/bulan | Rp 250.000/bulan |
| 500 transfers/bulan | Rp 1.250.000/bulan |
| 1000 transfers/bulan | Rp 2.500.000/bulan |

**Break-even:** Jika platform charge Rp 5.000/transfer → profit Rp 2.500/transfer

---

> **Status Dokumen:** ✅ FINAL — Semua keputusan bisnis sudah dijawab (15 Jun 2026)
> **Next Step:** Mulai Phase 0 (fix pre-requisite bugs)
