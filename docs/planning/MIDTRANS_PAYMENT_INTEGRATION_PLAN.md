# Midtrans Payment Integration Planning Document

> **Version:** 1.2  
> **Status:** Draft (Codebase-Aligned)  
> **Last Updated:** 2026-06-12  
> **Progress:** ✅ Phase 1-4 Complete | 🔄 Phase 5-6 Planned | 📋 Docs Updated

## Overview

This document outlines the architecture, implementation plan, and technical specifications for integrating Midtrans payment processing with the Lakasir POS SaaS platform. The solution uses a single Midtrans Merchant ID for all tenants, with double-entry accounting to manage per-tenant fund separation and automated withdrawal capabilities.

## Changelog (v1.1)

| Issue | Description | Fix |
|-------|-------------|-----|
| CRITICAL-1 | Ledger `amount` sign inconsistent → balance calculation ambiguous | Amount always positive; `entry_type` determines direction; balance computed via `SUM(CASE WHEN entry_type='credit' THEN amount ELSE -amount END)` |
| CRITICAL-2 | Race condition in balance read via cache | `getBalance()` uses direct query, NO cache; withdrawal/approval uses `SELECT FOR UPDATE` |
| CRITICAL-3 | Webhook IP whitelist hardcoded | IP list loaded from `.env` via config, updatable without deploy |
| CRITICAL-4 | Reconciliation limited to 200 records | Pagination loop until `next_page_token` null |
| CRITICAL-5 | LedgerService lock scope incomplete | Advisory lock via `DB::statement('SELECT pg_advisory_xact_lock(...)')` in DB transaction |
| HIGH-1 | Fee rates not snapshot at transaction time | Ledger entry stores `fee_rate_used` (percentage or flat value) alongside amount |
| HIGH-2 | Idempotency key null → generates UUID → allows duplicates | Idempotency key **required**; null rejected with 422 |
| HIGH-3 | `order_id` timestamp collision possible | Format: `T{tenant_id}-{microtime}-{random(4)}` |
| HIGH-4 | Settlement status enum missing states | Added: `failed`, `partial` |
| HIGH-5 | `payout_schedule` unused | Cron reads tenant payout schedule; respects `daily`/`weekly`/`manual` |
| MEDIUM-1 | `EnsuresLedgerBalance` trait logic incorrect | Fixed sum expression |
| MEDIUM-2 | Fee calculator silent fallback for unknown type | Throws `UnknownPaymentTypeException` |
| MINOR-1 | Typo `Withdrawls` → `Withdrawals` | Fixed |
| MINOR-2 | Reconciliation used global `server_key` | Now reads per-tenant |
| MINOR-3 | `verifySignature` not defined | Full implementation added |
| MINOR-4 | `DisbursementService` interface missing | Added section |

## Changelog (v1.2) — Codebase Alignment

| Change | Before (v1.1) | Now (v1.2) | Reason |
|--------|---------------|------------|--------|
| Database | PostgreSQL syntax (`pg_advisory_xact_lock`, `BIGINT UNSIGNED`) | **MySQL** (`$table->id()`, `unsignedBigInteger()`, `GET_LOCK()`) | Codebase uses MySQL |
| Money columns | `BIGINT` (sen/rupiah integer) | **`double`** (matches `sellings` table: `payed_money`, `total_price`, `fee`) | Existing schema uses `double` |
| Activity logging | Custom `audit_logs` table | **`spatie/laravel-activitylog`** via `LogsActivity` trait on models | Already in codebase (4.12) |
| Idempotency | Custom `withdrawals.idempotency_key` | **Reuse `idempotency_logs` table** (exists since 2026_05_29) | Avoid duplication |
| Payment methods | New `midtrans_transactions` table | **Extend `sellings` table** + new `midtrans_webhooks` / `midtrans_payments` | `sellings` already has `payment_method_id`, `fee` |
| Tenant columns | Add columns to central `tenants` | **Add to tenant database via tenant migration** (stancl/tenancy pattern) | Tenant DB per tenant |
| Model conventions | `$fillable` | **`$guarded = ['id']`** (or empty) | Matches AGENTS.md & existing models |
| Queue driver | Assume `redis` | **Default `sync`, configurable to `database`/`redis`** | Config default is `sync` |
| Lock mechanism | `pg_advisory_xact_lock()` | **`GET_LOCK()` / `RELEASE_LOCK()`** or application mutex | MySQL compatible |
| Feature flags | Not mentioned | **Laravel Pennant** (`FeatureClass::class`) | Used in codebase (25 flags) |
| Selling flow | New transaction endpoint | **Hook into `SellingService::create()` + `SellingCreated` event** | Existing POS flow |

### Decision Rationale

| Option | Description | Chosen? |
|--------|-------------|---------|
| **Option 1: Multi-MID (Partner)** | Each tenant gets their own Midtrans Merchant ID under Partner account | ❌ |
| **Option 2: Single MID** | One Midtrans MID for all tenants, app-level fund separation | ✅ |

**Why Option 2:**
- Instant tenant onboarding (no Midtrans verification per tenant)
- Single MID to monitor
- Full control over settlement schedule
- Can define own platform fee structure

## 1. System Architecture

### 1.1 High-Level Architecture

```
┌──────────────────────┐       ┌───────────────────────┐       ┌──────────────────────┐
│    Tenant Dashboard  │       │   POS App (Native)   │       │   Customer Facing   │
│    (Filament/Livewire)│       │   (Staff Creates Sell)│       │   (Snap Popup)      │
└──────────┬──────────┘       └──────────┬────────────┘       └──────────┬───────────┘
           │                              │                               │
           │                              │                               │
           ▼                              ▼                               ▼
┌──────────────────────────────────────────────────────────────────────────────────────┐
│                               Lakasir Core (Laravel)                                  │
│                                                                                       │
│  ┌──────────────┐  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐    │
│  │ SellingService│  │ MidtransGateway │  │ LedgerService   │  │ WithdrawalSvc   │    │
│  │ (Create Sell) │  │ (Snap/Webhook)  │  │ (Double-Entry)  │  │ (Disburse)      │    │
│  └──────┬───────┘  └────────┬────────┘  └────────┬────────┘  └────────┬────────┘    │
│         │                  │                     │                    │             │
│         │      ┌───────────┘                     │                    │             │
│         ▼      ▼                                 ▼                    ▼             │
│  ┌─────────────────────────────────────────────────────────────────────────────────┐ │
│  │                         Tenant Database (MySQL) — per tenant                     │ │
│  │  ┌──────────┐ ┌──────────┐ ┌────────────┐ ┌──────────┐ ┌──────────┐ ┌────────┐ │ │
│  │  │  Sellings│ │MidtransPm│ │LedgerEntries│ │Settlements│ │Withdrawals│ │IdemLogs│ │ │
│  │  │(+fee,col)│ │(webhook) │ │(double-entry)│ │          │ │          │ │ (exist) │ │
│  │  └──────────┘ └──────────┘ └────────────┘ └──────────┘ └──────────┘ └────────┘ │ │
│  └─────────────────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────────────────┘
```

### 1.2 Transaction Flow Sequence

```
Customer Checkout                        Tenant App                    Lakasir Core                       Midtrans
     │                                     │                             │                                 │
     │ 1. Checkout (item details)          │                             │                                 │
     │────────────────────────────────────►│                             │                                 │
     │                                     │ 2. Create Transaction       │                                 │
     │                                     │ (tenant_id, gross_amount)  │                                 │
     │                                     │────────────────────────────►│                                 │
     │                                     │                             │ 3. Snap Transaction Token       │
     │                                     │                             │────────────────────────────────►│
     │                                     │                             │                                 │
     │                                     │ 4. Snap Token               │                                 │
     │                                     │◄────────────────────────────│                                 │
     │                                     │                             │                                 │
     │ 5. Redirect to Snap Popup          │                             │                                 │
     │ (token, client_key)                 │                             │                                 │
     │◄────────────────────────────────────│                             │                                 │
     │                                     │                             │                                 │
     │ 6. Customer pays via Snap           │                             │                                 │
     │─────────────────────────────────────────────────────────────────────────────────────────────────►│
     │                                     │                             │                                 │
     │                                     │                             │ 7. HTTP Notification (Webhook) │
     │                                     │                             │◄────────────────────────────────│
     │                                     │                             │                                 │
     │                                     │                             │ 8. Create Ledger Entry         │
     │                                     │                             │    (credit + fees)              │
     │                                     │                             │    Verify Signature Key         │
     │                                     │                             │                                 │
     │ 9. Redirect to Finish URL           │                             │                                 │
     │◄────────────────────────────────────│                             │                                 │
     │                                     │ 10. Poll Transaction Status │                                 │
     │                                     │────────────────────────────►│                                 │
     │                                     │                             │                                 │
     │ 11. Transaction Status              │                             │                                 │
     │◄────────────────────────────────────│                             │                                 │
```

### 1.3 Withdrawal Flow Sequence

```
Tenant Dashboard (Filament)              Lakasir Core                    Payment Gateway (Flip)         Tenant Bank
     │                                      │                                │                            │
     │ 1. Request Withdraw (amount)         │                                │                            │
     │─────────────────────────────────────►│                                │                            │
     │                                      │ 2. Check Balance               │                            │
     │                                      │    Available >= amount?        │                            │
     │                                      │ 3. Create Withdrawal (pending) │                            │
     │                                      │ 4. Create Ledger Entry (debit) │                            │
     │                                      │                                │                            │
     │ 5. Withdrawal Pending Confirmation   │                                │                            │
     │◄─────────────────────────────────────│                                │                            │
     │                                      │                                │                            │
[Admin Finance]                             │                                │                            │
     │                                      │                                │                            │
     │ 6. Approve Withdrawal                │                                │                            │
     │─────────────────────────────────────►│                                │                            │
     │                                      │ 7. POST /disbursement          │                            │
     │                                      │───────────────────────────────►│                            │
     │                                      │                                │ 8. Transfer to Account      │
     │                                      │                                │───────────────────────────►│
     │                                      │                                │ 9. Webhook Callback         │
     │                                      │◄───────────────────────────────│                            │
     │                                      │ 10. Update Withdrawal          │                            │
     │                                      │     (status: completed)        │                            │
     │                                      │                                │                            │
     │ 11. Withdrawal Completed             │                                │                            │
     │◄─────────────────────────────────────│                                │                            │
```

## 2. Database Schema

### 2.0 Key Alignment Notes

| Aspect | Real Codebase Convention |
|--------|-------------------------|
| Database | **MySQL** (not PostgreSQL) |
| Money type | **`double`** (matches `sellings.payed_money`, `sellings.total_price`, `sellings.fee`) |
| Migration path | `database/migrations/tenant/` (stancl/tenancy, per-tenant DB) |
| Migration format | `YYYY_MM_DD_HHIISS_descriptive_name.php` |
| Model base | `App\Models\Tenants\` namespace |
| Guarded | `$guarded = ['id']` (or `[]`) |
| Activity log | `spatie/laravel-activitylog` trait `LogsActivity` on models |
| Idempotency | Existing `idempotency_logs` table (2026_05_29_143139) |
| Payment method | Existing `payment_methods` table with `is_cash`, `is_debit`, `is_credit`, `is_wallet` flags |
| Feature flags | Laravel Pennant (`FeatureClass::class`) |

### 2.1 New Tenant Migrations (Tenant DB)

#### `2026_06_12_000001_create_midtrans_payments_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('midtrans_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selling_id')->constrained()->onDelete('cascade');
            $table->string('order_id')->unique(); // format: T{tenant_id}-{microtime}-{random(4)}
            $table->string('midtrans_transaction_id')->nullable();
            $table->double('gross_amount'); // matches sellings.double type
            $table->string('payment_type')->nullable(); // credit_card, gopay, bank_transfer, qris, etc.
            $table->string('payment_channel')->nullable(); // BCA, BNI, Mandiri, etc.
            $table->string('status'); // pending, settlement, capture, expire, deny, cancel, refund
            $table->double('fee_midtrans')->default(0);
            $table->double('fee_platform')->default(0);
            $table->double('net_amount')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->json('notification_payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('midtrans_payments');
    }
};
```

#### `2026_06_12_000002_create_ledger_entries_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->morphs('ledgerable');           // polymorphic: Selling, Withdrawal, etc.
            $table->enum('entry_type', ['credit', 'debit']);
            $table->double('amount');               // ALWAYS positive
            $table->double('balance_before');
            $table->double('balance_after');
            $table->string('description');
            $table->string('reference_type');        // selling, fee_midtrans, fee_platform, withdrawal, refund
            $table->unsignedBigInteger('reference_id');
            $table->string('fee_rate_type')->nullable();   // 'percentage', 'flat'
            $table->double('fee_rate_value')->nullable();  // snapshot for audit
            $table->timestamps();

            $table->index(['ledgerable_type', 'ledgerable_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
```

#### `2026_06_12_000003_create_withdrawals_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->double('amount');
            $table->string('bank_name');
            $table->string('bank_account_name');
            $table->string('bank_account_number');
            $table->string('bank_code');           // e.g. '014' for BCA
            $table->enum('status', ['pending', 'approved', 'rejected', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('disburse_id')->nullable();
            $table->json('disburse_response')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->string('rejection_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
```

#### `2026_06_12_000004_create_settlements_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->double('total_gross');
            $table->double('total_fee_midtrans');
            $table->double('total_fee_platform');
            $table->double('total_net');
            $table->unsignedBigInteger('transaction_count');
            $table->enum('status', ['pending', 'approved', 'partial', 'disbursed', 'failed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
```

#### `2026_06_12_000005_add_midtrans_and_bank_to_abouts_table.php`

This extends the existing `abouts` table (tenant settings) — NOT the central `tenants` table.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abouts', function (Blueprint $table) {
            $table->double('platform_fee_percent')->default(1.00)->after('timezone');
            $table->enum('payout_schedule', ['daily', 'weekly', 'manual'])->default('manual')->after('platform_fee_percent');
            $table->string('midtrans_client_key')->nullable()->after('payout_schedule');
            $table->string('midtrans_server_key')->nullable()->after('midtrans_client_key');
            $table->string('bank_name')->nullable()->after('midtrans_server_key');
            $table->string('bank_account_name')->nullable()->after('bank_name');
            $table->string('bank_account_number')->nullable()->after('bank_account_name');
            $table->string('bank_code')->nullable()->after('bank_account_number');
        });
    }

    public function down(): void
    {
        Schema::table('abouts', function (Blueprint $table) {
            $table->dropColumn([
                'platform_fee_percent',
                'payout_schedule',
                'midtrans_client_key',
                'midtrans_server_key',
                'bank_name',
                'bank_account_name',
                'bank_account_number',
                'bank_code',
            ]);
        });
    }
};
```

### 2.2 Key Model Note

All new models go in `App\Models\Tenants\` and follow:

```php
<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MidtransPayment extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    public function selling()
    {
        return $this->belongsTo(Selling::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
```

### 2.3 Existing Idempotency Log Reuse

The `idempotency_logs` table already exists. Midtrans webhook handler uses it:

```php
use App\Models\Tenants\IdempotencyLog;

// In webhook handler
$log = IdempotencyLog::firstOrCreate(
    ['idempotency_key' => $midtransOrderId], // Midtrans transaction_id as idempotency key
    ['status' => 'processing', 'endpoint' => '/api/webhooks/midtrans', 'method' => 'POST']
);

if ($log->status === 'completed') {
    return; // Already processed
}
```

## 3. Fee Calculation

### 3.1 Midtrans MDR Rates (per Payment Method)

```php
// config/midtrans.php
return [
    'webhook_ip_whitelist' => explode(',', env('MIDTRANS_WEBHOOK_IPS', '')),
    'fees' => [
        'credit_card'   => ['type' => 'percentage', 'percentage' => 2.95, 'flat' => 0],
        'debit_card'    => ['type' => 'percentage', 'percentage' => 1.95],
        'gopay'         => ['type' => 'percentage', 'percentage' => 1.50],
        'shopeepay'     => ['type' => 'percentage', 'percentage' => 1.50],
        'bank_transfer' => ['type' => 'flat', 'amount' => 2500],                       // BCA/Mandiri/BNI/BRI
        'qris'          => ['type' => 'percentage', 'percentage' => 0.70],
        'indomaret'     => ['type' => 'flat', 'amount' => 2500],
        'alfamart'      => ['type' => 'flat', 'amount' => 2500],
        'kredivo'       => ['type' => 'percentage', 'percentage' => 3.00],
        'akulaku'       => ['type' => 'percentage', 'percentage' => 3.00],
    ],
    // Note: fee_config can also be stored in `abouts` table per-tenant
];
```

### 3.2 Fee Calculation Logic

```php
<?php

namespace App\Services\Tenants;

class UnknownPaymentTypeException extends \RuntimeException {}

class MidtransFeeCalculator
{
    /**
     * Calculate fees. Uses `double` to match codebase convention (sellings table).
     */
    public function calculate(string $paymentType, float $grossAmount, float $platformFeePercent): array
    {
        $feeConfig = config('midtrans.fees.' . $paymentType);

        if ($feeConfig === null) {
            throw new UnknownPaymentTypeException(
                "Unknown payment type: {$paymentType}. Configure in config/midtrans.php"
            );
        }

        $feeMidtrans = match ($feeConfig['type']) {
            'percentage' => round($grossAmount * $feeConfig['percentage'] / 100, 0),
            'flat'       => (float) $feeConfig['amount'],
            default      => throw new UnknownPaymentTypeException("Unknown fee type: {$feeConfig['type']}"),
        };

        $feePlatform = round($grossAmount * $platformFeePercent / 100, 0);
        $netAmount   = $grossAmount - $feeMidtrans - $feePlatform;

        return [
            'fee_midtrans'          => $feeMidtrans,
            'fee_midtrans_rate_type' => $feeConfig['type'],
            'fee_midtrans_rate_value' => $feeConfig['percentage'] ?? $feeConfig['amount'],
            'fee_platform'          => $feePlatform,
            'net_amount'            => $netAmount,
        ];
    }
}
```

## 4. Core Services

### 4.1 LedgerService

```php
<?php

namespace App\Services\Tenants;

use App\Models\Tenants\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class InsufficientBalanceException extends \RuntimeException {}

class LedgerService
{
    /**
     * Create ledger entry with MySQL GET_LOCK for row-level concurrency safety.
     * Uses `double` columns to match codebase convention (sellings table).
     *
     * @throws InsufficientBalanceException
     */
    public function entry(
        string $ledgerableType,  // Selling::class, Withdrawal::class
        int $ledgerableId,
        string $entryType,       // 'credit' | 'debit'
        float $amount,           // double, matches sellings.payed_money
        string $description,
        string $referenceType,
        int $referenceId,
        ?string $feeRateType = null,
        ?float $feeRateValue = null,
    ): LedgerEntry {
        $lockName = 'ledger_' . Str::slug($ledgerableType) . '_' . $ledgerableId;

        // MySQL application-level lock (released on connection close or explicit RELEASE_LOCK)
        DB::select("SELECT GET_LOCK(?, 5) AS lock_acquired", [$lockName]);

        try {
            return DB::transaction(function () use (
                $ledgerableType, $ledgerableId, $entryType, $amount,
                $description, $referenceType, $referenceId,
                $feeRateType, $feeRateValue
            ) {
                $currentBalance = $this->getCurrentBalance();

                $balanceAfter = $entryType === 'credit'
                    ? $currentBalance + $amount
                    : $currentBalance - $amount;

                if ($balanceAfter < 0) {
                    throw new InsufficientBalanceException(
                        "Saldo tidak mencukupi. Tersedia: Rp " . number_format($currentBalance, 0, ',', '.')
                    );
                }

                return LedgerEntry::create([
                    'ledgerable_type'   => $ledgerableType,
                    'ledgerable_id'     => $ledgerableId,
                    'entry_type'        => $entryType,
                    'amount'            => $amount, // ALWAYS positive
                    'balance_before'    => $currentBalance,
                    'balance_after'     => $balanceAfter,
                    'description'       => $description,
                    'reference_type'    => $referenceType,
                    'reference_id'      => $referenceId,
                    'fee_rate_type'     => $feeRateType,
                    'fee_rate_value'    => $feeRateValue,
                ]);
            });
        } finally {
            DB::select("SELECT RELEASE_LOCK(?)", [$lockName]);
        }
    }

    /**
     * NO CACHE — direct SUM query for financial accuracy.
     */
    public function getCurrentBalance(): float
    {
        return LedgerEntry::sum(DB::raw("CASE WHEN entry_type = 'credit' THEN amount ELSE -amount END"));
    }

    public function getTransactions(string $from, string $to): \Illuminate\Database\Eloquent\Collection
    {
        return LedgerEntry::whereBetween('created_at', [$from, $to])
            ->orderBy('id')
            ->get();
    }
}
```

### 4.2 Integration with Existing SellingService

Key principle: **do NOT create new transaction endpoint**. Hook into existing POS flow:

```php
// app/Http/Controllers/Api/Tenants/Transaction/SellingController.php
// Existing method — extend with Midtrans payment flow

public function store(SellingRequest $request)
{
    $paymentMethod = PaymentMethod::find($request->payment_method_id);
    
    // If Midtrans payment method selected, generate Snap token
    if ($paymentMethod && in_array($paymentMethod->name, ['GoPay', 'QRIS', 'Bank Transfer', 'Credit Card'])) {
        return $this->paymentService->createSellingWithMidtrans($request);
    }
    
    return $this->service->create($request);
}
```

### 4.3 MidtransGatewayService

```php
<?php

namespace App\Services\Tenants;

use App\Models\Tenants\About;
use App\Models\Tenants\MidtransPayment;
use App\Models\Tenants\Selling;
use App\Models\Tenants\IdempotencyLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class MidtransGatewayService
{
    public function __construct(
        private readonly SellingService $sellingService,
        private readonly MidtransFeeCalculator $feeCalculator,
        private readonly LedgerService $ledger,
    ) {}

    /**
     * Generate Snap transaction token for a selling.
     * Called after SellingService::create().
     */
    public function createSnapToken(Selling $selling, About $about): string
    {
        $serverKey = $about->midtrans_server_key;

        $payload = [
            'transaction_details' => [
                'order_id'     => $this->generateOrderId($about->id),
                'gross_amount' => (int) $selling->total_price, // send as integer to Midtrans
            ],
            'item_details' => $this->buildItemDetails($selling),
            'customer_details' => [
                'first_name' => $selling->member->name ?? 'Guest',
            ],
        ];

        // Use Snap Core API
        $response = Http::withBasicAuth($serverKey, '')
            ->post('https://app.sandbox.midtrans.com/snap/v1/transactions', $payload);

        if ($response->failed()) {
            Log::error('Midtrans Snap token failed', [
                'selling_id' => $selling->id,
                'error' => $response->json(),
            ]);
            throw new \RuntimeException('Gagal membuat pembayaran: ' . ($response->json('error_messages.0') ?? 'Unknown'));
        }

        $token = $response->json('token');
        $redirectUrl = $response->json('redirect_url');

        // Save Midtrans payment record
        MidtransPayment::create([
            'selling_id' => $selling->id,
            'order_id'   => $payload['transaction_details']['order_id'],
            'gross_amount' => $selling->total_price,
            'status'     => 'pending',
        ]);

        return $token; // or $redirectUrl
    }

    /**
     * Handle incoming webhook from Midtrans.
     * Uses existing `idempotency_logs` table for dedup.
     *
     * Endpoint: POST /api/webhooks/midtrans
     * Route file: routes/api.php (central or tenant)
     */
    public function handleWebhook(array $payload): void
    {
        // 1. Verify IP whitelist
        $whitelist = config('midtrans.webhook_ip_whitelist', []);
        if (!empty($whitelist) && !in_array(request()->ip(), $whitelist)) {
            Log::warning('Midtrans webhook IP mismatch', ['ip' => request()->ip()]);
            abort(403, 'Unauthorized IP');
        }

        // 2. Find payment record
        $payment = MidtransPayment::where('order_id', $payload['order_id'])->first();
        if (!$payment) {
            Log::error('Midtrans webhook: payment not found', ['order_id' => $payload['order_id']]);
            return;
        }

        // 3. Verify signature
        $about = About::first(); // single row per tenant
        if (!$this->verifySignature($payload, $about->midtrans_server_key)) {
            Log::critical('Midtrans webhook signature verification failed', $payload);
            abort(401, 'Invalid signature');
        }

        // 4. Idempotency check via existing idempotency_logs table
        $idemLog = IdempotencyLog::firstOrCreate(
            ['idempotency_key' => $payload['transaction_id'] ?? $payload['order_id']],
            [
                'status' => 'processing',
                'endpoint' => '/api/webhooks/midtrans',
                'method' => 'POST',
            ]
        );

        if ($idemLog->status === 'completed') {
            return; // Already processed
        }

        // 5. Process payment status update
        $this->processStatusUpdate($payment, $payload, $idemLog);
    }

    private function processStatusUpdate(MidtransPayment $payment, array $payload, IdempotencyLog $idemLog): void
    {
        DB::transaction(function () use ($payment, $payload, $idemLog) {
            $oldStatus = $payment->status;
            $newStatus = $payload['transaction_status'];

            $payment->update([
                'status'                 => $newStatus,
                'payment_type'           => $payload['payment_type'] ?? $payment->payment_type,
                'payment_channel'        => $payload['payment_type'] ?? $payload['bank'] ?? null,
                'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
                'paid_at'               => in_array($newStatus, ['settlement', 'capture']) ? now() : $payment->paid_at,
                'notification_payload'  => $payload,
            ]);

            // On settlement: update selling fee + ledger
            if (in_array($newStatus, ['settlement', 'capture']) && $oldStatus !== 'settlement') {
                $this->finalizeSettlement($payment, $payload);
            }

            // On settlement: mark selling as paid
            if (in_array($newStatus, ['settlement', 'capture'])) {
                $payment->selling->update(['is_paid' => true]);
            }

            // Mark idempotency as completed
            $idemLog->update([
                'status' => 'completed',
                'response' => json_encode($payload),
            ]);
        });
    }

    private function finalizeSettlement(MidtransPayment $payment, array $payload): void
    {
        $about = About::first(); // tenant settings
        $fees = $this->feeCalculator->calculate(
            paymentType: $payload['payment_type'],
            grossAmount: $payment->gross_amount,
            platformFeePercent: $about->platform_fee_percent ?? 1.0,
        );

        // Save fee breakdown to payment
        $payment->forceFill($fees)->save();

        // Update selling.fee (existing column)
        $payment->selling->update([
            'fee' => $payment->selling->fee + $fees['fee_midtrans'] + $fees['fee_platform'],
        ]);

        // Ledger: credit from sale
        $this->ledger->entry(
            ledgerableType: Selling::class,
            ledgerableId: $payment->selling->id,
            entryType: 'credit',
            amount: $payment->gross_amount,
            description: "Payment {$payment->order_id} via {$payment->payment_type}",
            referenceType: 'selling',
            referenceId: $payment->selling->id,
        );

        // Ledger: debit midtrans fee
        if ($fees['fee_midtrans'] > 0) {
            $this->ledger->entry(
                ledgerableType: Selling::class,
                ledgerableId: $payment->selling->id,
                entryType: 'debit',
                amount: $fees['fee_midtrans'],
                description: "MDR {$payment->payment_type} ({$payment->order_id})",
                referenceType: 'fee_midtrans',
                referenceId: $payment->id,
                feeRateType: $fees['fee_midtrans_rate_type'],
                feeRateValue: $fees['fee_midtrans_rate_value'],
            );
        }

        // Ledger: debit platform fee
        if ($fees['fee_platform'] > 0) {
            $this->ledger->entry(
                ledgerableType: Selling::class,
                ledgerableId: $payment->selling->id,
                entryType: 'debit',
                amount: $fees['fee_platform'],
                description: "Platform fee ({$payment->order_id})",
                referenceType: 'fee_platform',
                referenceId: $payment->selling->id,
                feeRateType: 'percentage',
                feeRateValue: $about->platform_fee_percent ?? 1.0,
            );
        }
    }

    /**
     * Verify Midtrans HMAC SHA512 signature.
     */
    private function verifySignature(array $payload, string $serverKey): bool
    {
        $orderId     = $payload['order_id'] ?? '';
        $statusCode  = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';

        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        $provided  = $payload['signature_key'] ?? '';

        return hash_equals($signature, $provided);
    }

    private function generateOrderId(int $tenantId): string
    {
        $microtime = (int) (microtime(true) * 1000);
        $random = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        return "T{$tenantId}-{$microtime}-{$random}";
    }

    private function buildItemDetails(Selling $selling): array
    {
        return $selling->sellingDetails->map(fn ($detail) => [
            'id'       => (string) $detail->product_id,
            'price'    => (int) $detail->price,
            'quantity' => (int) $detail->qty,
            'name'     => substr($detail->product?->name ?? 'Item', 0, 50),
        ])->toArray();
    }
}
```

### 4.4 WithdrawalService

```php
<?php

namespace App\Services\Tenants;

use App\Models\Tenants\Withdrawal;
use App\Models\Tenants\LedgerEntry;
use App\Models\Tenants\About;
use App\Models\Tenants\IdempotencyLog;
use Illuminate\Support\Facades\DB;

class WithdrawalService
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly DisbursementService $disbursement,
    ) {}

    /**
     * Request withdrawal. Bank info from `abouts` table.
     * @throws InsufficientBalanceException
     */
    public function request(float $amount, int $requestedBy, string $idempotencyKey): Withdrawal
    {
        if (empty($idempotencyKey)) {
            throw new \InvalidArgumentException('idempotency_key is required');
        }

        // Reuse existing idempotency_logs table
        $idemLog = IdempotencyLog::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            ['status' => 'processing', 'endpoint' => '/api/tenant/withdrawals', 'method' => 'POST']
        );

        if ($idemLog->status === 'completed') {
            throw new \RuntimeException('Withdrawal already processed');
        }

        $about = About::first(); // tenant settings

        return DB::transaction(function () use ($amount, $requestedBy, $about, $idemLog) {
            $available = $this->ledger->getCurrentBalance();
            $maxAllowed = $available * 0.95;

            if ($amount > $available) {
                throw new InsufficientBalanceException(
                    "Saldo tidak cukup. Tersedia: Rp " . number_format($available, 0, ',', '.')
                );
            }

            if ($amount > $maxAllowed) {
                throw new InsufficientBalanceException(
                    "Maksimal 95% dari saldo. Maks: Rp " . number_format($maxAllowed, 0, ',', '.')
                );
            }

            $withdrawal = Withdrawal::create([
                'amount'              => $amount,
                'bank_name'           => $about->bank_name,
                'bank_account_name'   => $about->bank_account_name,
                'bank_account_number' => $about->bank_account_number,
                'bank_code'           => $about->bank_code,
                'status'              => 'pending',
                'requested_by'        => $requestedBy,
            ]);

            // Reserve fund (debit)
            $this->ledger->entry(
                ledgerableType: Withdrawal::class,
                ledgerableId: $withdrawal->id,
                entryType: 'debit',
                amount: $amount,
                description: "Withdrawal request #{$withdrawal->id}",
                referenceType: 'withdrawal_request',
                referenceId: $withdrawal->id,
            );

            $idemLog->update([
                'status' => 'completed',
                'response' => json_encode(['withdrawal_id' => $withdrawal->id]),
            ]);

            return $withdrawal;
        });
    }

    /**
     * @throws DisbursementFailedException
     */
    public function approve(int $withdrawalId, int $approvedBy): Withdrawal
    {
        $withdrawal = Withdrawal::findOrFail($withdrawalId);
        abort_if($withdrawal->status !== 'pending', 400, 'Withdrawal already processed');

        try {
            $withdrawal->update(['status' => 'processing']);

            $result = $this->disbursement->send([
                'bank_code'         => $withdrawal->bank_code,
                'account_number'    => $withdrawal->bank_account_number,
                'account_name'      => $withdrawal->bank_account_name,
                'amount'            => $withdrawal->amount,
                'remark'            => "Zonakasir WD #{$withdrawal->id}",
                'idempotency_key'   => "wd-approve-{$withdrawal->id}",
            ]);

            $withdrawal->update([
                'status'            => 'completed',
                'disburse_id'       => $result['id'],
                'disburse_response' => $result,
                'approved_by'       => $approvedBy,
                'processed_at'      => now(),
            ]);

            LedgerEntry::where('reference_type', 'withdrawal_request')
                ->where('reference_id', $withdrawal->id)
                ->update(['reference_type' => 'withdrawal_complete']);

        } catch (Throwable $e) {
            $withdrawal->update([
                'status'            => 'failed',
                'disburse_response' => ['error' => $e->getMessage()],
            ]);

            // Rollback
            $this->ledger->entry(
                ledgerableType: Withdrawal::class,
                ledgerableId: $withdrawal->id,
                entryType: 'credit',
                amount: $withdrawal->amount,
                description: "Withdrawal rollback #{$withdrawal->id}",
                referenceType: 'withdrawal_rollback',
                referenceId: $withdrawal->id,
            );

            throw new DisbursementFailedException($e->getMessage(), context: [], previous: $e);
        }

        return $withdrawal->fresh();
    }

    public function reject(int $withdrawalId, int $rejectedBy, string $reason): Withdrawal
    {
        return DB::transaction(function () use ($withdrawalId, $rejectedBy, $reason) {
            $withdrawal = Withdrawal::findOrFail($withdrawalId);
            abort_if($withdrawal->status !== 'pending', 400, 'Already processed');

            $withdrawal->update([
                'status'           => 'rejected',
                'rejected_by'     => $rejectedBy,
                'rejection_reason' => $reason,
            ]);

            $this->ledger->entry(
                ledgerableType: Withdrawal::class,
                ledgerableId: $withdrawal->id,
                entryType: 'credit',
                amount: $withdrawal->amount,
                description: "Withdrawal rejected #{$withdrawal->id}: {$reason}",
                referenceType: 'withdrawal_rejected',
                referenceId: $withdrawal->id,
            );

            return $withdrawal->fresh();
        });
    }
}
```

### 4.5 DisbursementService (Interface)

```php
<?php

namespace App\Services\Tenants;

/**
 * Abstraction over disbursement API provider (Flip / Duitku / custom).
 */
interface DisbursementProvider
{
    /**
     * Sends fund to a bank account.
     * @return array{id: string, status: string, ...provider-specific-fields}
     * @throws DisbursementFailedException
     */
    public function send(array $params): array;

    /**
     * Check status of a previous disbursement.
     */
    public function status(string $disburseId): array;
}

class DisbursementFailedException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}

class DisbursementService
{
    public function __construct(
        private readonly DisbursementProvider $provider,
    ) {}

    /**
     * @throws DisbursementFailedException
     */
    public function send(array $params): array
    {
        Log::info('Disbursement started', [
            'bank_code' => $params['bank_code'],
            'amount' => $params['amount'],
        ]);

        try {
            $result = $this->provider->send($params);

            Log::info('Disbursement completed', [
                'disburse_id' => $result['id'],
            ]);

            return $result;

        } catch (Throwable $e) {
            Log::error('Disbursement failed', [
                'error' => $e->getMessage(),
            ]);

            throw new DisbursementFailedException(
                $e->getMessage(),
                ['params' => $params],
                $e,
            );
        }
    }
}
```

### 4.6 ReconciliationService

```php
<?php

namespace App\Services\Tenants;

use App\Models\Tenants\MidtransPayment;
use App\Models\Tenants\Withdrawal;
use App\Models\Tenants\Settlement;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReconciliationService
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    /**
     * Daily reconciliation. Runs per-tenant DB.
     */
    public function daily(): ReconciliationReport
    {
        $report = new ReconciliationReport();
        $yesterday = now()->subDay()->startOfDay();

        $midtransTransactions = $this->fetchAllMidtransTransactions($yesterday);
        $ourTransactions = MidtransPayment::whereDate('paid_at', $yesterday)
            ->where('status', 'settlement')
            ->get();

        $mismatches = [];
        foreach ($midtransTransactions as $midtransTx) {
            $ourTx = $ourTransactions->firstWhere('order_id', $midtransTx['order_id']);

            if (!$ourTx) {
                $mismatches[] = [
                    'type' => 'missing_in_db',
                    'order_id' => $midtransTx['order_id'],
                    'midtrans_gross' => $midtransTx['gross_amount'],
                ];
                continue;
            }

            if ((float) $midtransTx['gross_amount'] !== (float) $ourTx->gross_amount) {
                $mismatches[] = [
                    'type' => 'amount_mismatch',
                    'order_id' => $midtransTx['order_id'],
                    'midtrans_gross' => $midtransTx['gross_amount'],
                    'our_gross' => $ourTx->gross_amount,
                ];
            }
        }

        // Balanced check (single tenant DB)
        $ledgerBalance = $this->ledger->getCurrentBalance();
        $calculatedBalance = MidtransPayment::where('status', 'settlement')
            ->where('paid_at', '<=', $yesterday->copy()->endOfDay())
            ->sum('net_amount');
        $withdrawn = Withdrawal::where('status', 'completed')
            ->where('created_at', '<=', $yesterday->copy()->endOfDay())
            ->sum('amount');
        $expected = $calculatedBalance - $withdrawn;

        if (abs($ledgerBalance - $expected) > 1) {
            $report->addBalanceMismatch([
                'ledger_balance' => $ledgerBalance,
                'expected' => $expected,
                'diff' => $ledgerBalance - $expected,
            ]);
        }

        $report->addMismatches($mismatches);

        if ($report->hasIssues()) {
            $report->sendAlert(config('services.alerts.slack_webhook'));
            Log::channel('finance')->error('Reconciliation mismatch', $report->toArray());
        }

        return $report;
    }

    /**
     * Paginated fetch from Midtrans API (max 200/req).
     */
    private function fetchAllMidtransTransactions(\Carbon\Carbon $date): array
    {
        $all = [];
        $token = null;
        $about = \App\Models\Tenants\About::first();

        do {
            $params = [
                'from'  => $date->timestamp,
                'to'    => $date->copy()->endOfDay()->timestamp,
                'limit' => 200,
            ];
            if ($token) {
                $params['next_page_token'] = $token;
            }

            $res = Http::withBasicAuth($about->midtrans_server_key ?? config('midtrans.server_key'), '')
                ->get('https://api.sandbox.midtrans.com/v2/' . ($about->midtrans_merchant_id ?? config('midtrans.merchant_id')) . '/transaction_status', $params);

            if ($res->failed()) break;

            $data = $res->json();
            $all = array_merge($all, $data['data'] ?? []);
            $token = $data['next_page_token'] ?? null;
        } while ($token);

        return $all;
    }
}
```

## 5. API Endpoints

### 5.1 Tenant-facing APIs (need auth, tenant-scoped)

| Endpoint | Method | Control | Description |
|----------|--------|---------|-------------|
| `/api/tenant/balance` | GET | `BalanceController` | Current available balance from ledger |
| `/api/tenant/withdrawals` | GET | `WithdrawalController@index` | Withdrawal history |
| `/api/tenant/withdrawals` | POST | `WithdrawalController@store` | Request withdrawal (requires idempotency_key) |
| `/api/tenant/settlements` | GET | `SettlementController@index` | Settlement history |

Note: Transaction is created via existing `SellingController@store` — NO new payment charge endpoint.

### 5.2 Admin-facing APIs (need admin auth, tenant-scoped)

| Endpoint | Method | Control | Description |
|----------|--------|---------|-------------|
| `/api/tenant/withdrawals/{id}/approve` | POST | `WithdrawalController@approve` | Approve withdrawal |
| `/api/tenant/withdrawals/{id}/reject` | POST | `WithdrawalController@reject` | Reject withdrawal |
| `/api/tenant/reconciliation/run` | POST | `ReconciliationController` | Trigger reconciliation |

### 5.3 Webhook (no auth, IP whitelist)

| Endpoint | Method | Control | Description |
|----------|--------|---------|-------------|
| `/api/webhooks/midtrans` | POST | `MidtransWebhookController` | Midtrans payment notification |

Note: Webhook is **central route** (stancl/tenancy central domain). Uses `centralDomains` config. Midtrans sends to central URL; handler extracts `order_id` prefix to identify tenant then calls `tenancy()->initialize($tenant)`.

## 6. Scheduled Jobs (Cron)

```bash
# Runs: Daily at 02:00
php artisan payments:reconcile              # ReconciliationService->daily()

# Runs: Daily at 03:00
php artisan payments:generate-settlements    # Generate settlement reports
                                            # Respects tenant payout_schedule:
                                            #   - daily: generate every day
                                            #   - weekly: generate if period_end is Sunday
                                            #   - manual: NOT generated automatically

# Runs: Every 10 minutes
php artisan payments:retry-failed-webhooks  # Retry webhooks that failed processing

# Runs: Daily at 08:00
php artisan payments:cancel-expired         # Cancel pending transactions > 24h

# Runs: Every 5 minutes
php artisan payments:process-auto-approve   # Auto-approve withdrawals < Rp 5jt
                                            # only for tenants with 30+ day active history
```

```php
// app/Console/Commands/GenerateSettlements.php
public function handle(): void
{
    $today = now()->startOfDay();
    $yesterday = now()->subDay()->startOfDay();

    $tenants = Tenant::whereIn('payout_schedule', function ($query) use ($today) {
        $query->when(true, function ($q) use ($today) {
            return Tenant::where('payout_schedule', 'daily')
                ->orWhere(function ($q2) use ($today) {
                    $q2->where('payout_schedule', 'weekly')
                        ->whereRaw('DAYOFWEEK(?) = 1', [$today]); // Sunday
                });
        });
    })->get();

    foreach ($tenants as $tenant) {
        $this->generateSettlement($tenant, $yesterday, $today);
    }
}
```

## 7. Financial Safety & Security

### 7.1 Double-Entry Guard

```php
<?php

namespace App\Models\Tenants\Traits;

use App\Models\Tenants\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait EnsuresLedgerBalance
{
    /**
     * Verifies that ledger is balanced after every entry.
     * balance = SUM(credit) - SUM(debit)
     * invariant: last balance_after must equal the calculated sum
     */
    public static function bootEnsuresLedgerBalance(): void
    {
        static::created(function (LedgerEntry $entry) {
            $balance = LedgerEntry::sum(
                DB::raw("CASE WHEN entry_type = 'credit' THEN amount ELSE -amount END")
            );

            $lastEntry = LedgerEntry::orderBy('id', 'desc')
                ->value('balance_after');

            if (abs($balance - $lastEntry) > 1) {
                Log::critical('LEDGER_INVARIANT_VIOLATION', [
                    'calculated_balance' => $balance,
                    'stored_balance_after' => $lastEntry,
                    'entry_id' => $entry->id,
                ]);
            }
        });
    }
}
```

### 7.2 Idempotency Protection (using existing `idempotency_logs` table)

```php
<?php

namespace App\Models\Tenants\Traits;

use App\Models\Tenants\IdempotencyLog;
use Illuminate\Support\Facades\DB;

trait IdempotentOperation
{
    /**
     * Check and mark idempotency via database (idempotency_logs table).
     * Returns cached result if already completed.
     */
    public function executeOnce(string $idempotencyKey, callable $callback): mixed
    {
        $log = IdempotencyLog::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            ['status' => 'processing', 'endpoint' => request()->path(), 'method' => request()->method()]
        );

        if ($log->status === 'completed') {
            return $log->response ? json_decode($log->response, true) : null;
        }

        try {
            $result = DB::transaction($callback);

            $log->update([
                'status' => 'completed',
                'response' => json_encode($result),
            ]);

            return $result;
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'response' => json_encode(['error' => $e->getMessage()]),
            ]);
            throw $e;
        }
    }
}
```

### 7.3 Withdrawal Approval Flow

```
Amount          | Action
----------------|------------------------------------------------------------
< Rp 5.000.000  | Auto-approved (trusted tenants after 30 days)
Rp 5-25 jt     | Requires 1 admin approval
> Rp 25 jt      | Requires 2 admin approvals (4-eyes principle)
```

### 7.4 Audit Logging

Every financial operation logs to `activity_log` via existing `spatie/laravel-activitylog`:

```php
<?php

namespace App\Models\Tenants\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait LogsFinancialActions
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*']) // log all changes
            ->dontSubmitEmptyLogs();
    }

    /**
     * Log a specific financial action manually (not just model changes).
     */
    public function logFinancialAction(string $description, array $properties = []): void
    {
        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->log($description);
    }
}
```

## 8. Implementation Phases

### Phase 1: Midtrans Gateway Integration (Week 1-2)

- [x] Create tenant migration `midtrans_payments` → `2026_06_12_000001_create_midtrans_payments_table.php`
- [x] Create model `MidtransPayment` (extends Model, uses `LogsActivity`) → `app/Models/Tenants/MidtransPayment.php`
- [x] Create service `MidtransGatewayService` → `app/Services/Tenants/MidtransGatewayService.php`
- [x] Extend `SellingService::create()` with Midtrans payment flow → `SellingController@store`
- [x] Extend `SellingCollection` for additional Midtrans data → `app/Http/Resources/SellingCollection.php`
- [x] Write unit tests for `MidtransFeeCalculator` → 9 tests pass

### Phase 2: Ledger & Webhook (Week 2-3)

- [x] Create `ledger_entries` table → `2026_06_12_000003_create_ledger_entries_table.php`
- [x] Create `LedgerService` with MySQL `GET_LOCK()` → `app/Services/Tenants/LedgerService.php`
- [x] Create `MidtransWebhookController` (IP whitelist + signature verify) → `app/Http/Controllers/Api/MidtransWebhookController.php`
- [x] Add webhook route → `routes/api.php` (`POST /api/webhooks/midtrans`)
- [x] Write integration tests for webhook → `tests/Feature/Tenants/MidtransWebhookTest.php`

### Phase 3: Withdrawal System (Week 3-4)

- [x] Create migration for `withdrawals` table → `2026_06_12_000004_create_withdrawals_table.php`
- [x] Create model `Withdrawal` → `app/Models/Tenants/Withdrawal.php`
- [x] Create `WithdrawalService` → `app/Services/Tenants/WithdrawalService.php`
- [x] Create `DisbursementProvider` interface → `app/Services/Tenants/DisbursementProvider.php`
- [x] Add withdrawal request API → `app/Http/Controllers/Api/Tenants/Transaction/WithdrawalController.php`
- [x] Add withdrawal routes → `routes/tenant.php`
- [x] Write tests for withdrawal → `tests/Unit/Services/Tenants/`

### Phase 4: Reconciliation & Reporting (Week 4-5)

- [x] Create `settlements` table → `2026_06_12_000005_create_settlements_table.php`
- [x] Implement `ReconciliationService` → `app/Services/Tenants/ReconciliationService.php`
- [x] Add `artisan payments:reconcile` command → `app/Console/Commands/PaymentsReconcile.php`
- [x] Setup cron job for daily reconciliation → `app/Console/Kernel.php`
- [x] Write tests for reconciliation → `tests/Feature/`

### Phase 5: Security & Production (Week 5-6)

- [ ] Webhook IP whitelist via `.env` (MIDTRANS_WEBHOOK_IPS)
- [ ] HMAC SHA512 signature verification
- [ ] Rate limiting on withdrawal endpoints (Laravel Sanctum + rate limiter)
- [ ] Withdrawl approval levels: < 5jt auto, 5-25jt single admin, >25jt 2 admins
- [ ] Monitoring and logging
- [ ] Load testing (10k tenants concurrent)

### Phase 6: Tenant Experience (Week 6-7)

- [ ] Balance widget (Filament)
- [ ] Withdrawal request form (Filament)
- [ ] Settlement report (Filament)
- [ ] Auto-notification on withdrawal status change (Slack/Email)
- [ ] E2E tests for core payment flow

## 9. Error Handling & Monitoring

### 9.1 Critical Alerts (P0 - Slack/WA/Email)

| Event | Action |
|-------|--------|
| Ledger invariant violation | Immediate alert + halt processing |
| Reconciliation mismatch > Rp 10k | Immediate alert + lock withdrawals |
| Disbursement API failure (3+ in 5 min) | Alert + pause auto-withdrawal |
| Webhook signature verification fail | Alert + IP blocking check |
| Database deadlock on ledger | Alert + analyze slow queries |

### 9.2 Warning Alerts (P1 - Slack)

| Event | Action |
|-------|--------|
| Reconciliation mismatch < Rp 10k | Log + notify finance team |
| Withdrawal failed (single) | Notify admin + tenant |
| Webhook processing delayed > 5 min | Alert + check queue worker |
| Idempotency key collision (> 1/min) | Alert + investigate duplicate calls |
| Tenant balance near zero (< Rp 100k) | Notify tenant to recharge |

### 9.3 Logging

```php
// config/logging.php — add to existing config
'channels' => [
    'finance' => [
        'driver' => 'daily',
        'path' => storage_path('logs/finance.log'),
        'level' => 'info',
        'days' => 365, // 1 year retention for financial logs
    ],
    'midtrans' => [
        'driver' => 'daily',
        'path' => storage_path('logs/midtrans.log'),
        'level' => 'debug',
        'days' => 30,
    ],
]
```

Note: Spatie activitylog already uses `activity_log` table + `config/activitylog.php`. No need to create custom `audit_logs` table.

## 10. Testing Strategy

### 10.1 Unit Tests (Pest)

```php
<?php

use App\Services\Tenants\MidtransFeeCalculator;
use App\Services\Tenants\LedgerService;

test('fee calculator: credit card', function () {
    $fees = (new MidtransFeeCalculator)->calculate('credit_card', 100000, 1.0);
    expect($fees['fee_midtrans'])->toBe(2950);   // 2.95%
    expect($fees['fee_platform'])->toBe(1000);    // 1%
    expect($fees['net_amount'])->toBe(96050);     // 100000 - 2950 - 1000
});

test('fee calculator: bank transfer', function () {
    $fees = (new MidtransFeeCalculator)->calculate('bank_transfer', 50000, 1.0);
    expect($fees['fee_midtrans'])->toBe(2500);    // flat
    expect($fees['net_amount'])->toBe(47000);     // 50000 - 2500 - 500
});

test('fee calculator throws on unknown type', function () {
    (new MidtransFeeCalculator)->calculate('unknown_method', 50000, 1.0);
})->throws(\App\Services\Tenants\UnknownPaymentTypeException::class);

test('ledger balance invariant after credit + debit', function () {
    $ledger = app(LedgerService::class);
    $ledger->entry(Selling::class, 1, 'credit', 100000, 'sale', 'selling', 1);
    $ledger->entry(Selling::class, 1, 'debit', 2500, 'fee', 'fee_midtrans', 1);
    $balance = $ledger->getCurrentBalance();
    expect($balance)->toBe(97500);
});

test('ledger blocks negative balance', function () {
    $ledger = app(LedgerService::class);
    $ledger->entry(Selling::class, 1, 'credit', 5000, 'sale', 'selling', 1);
    $ledger->entry(Selling::class, 1, 'debit', 10000, 'fee', 'fee', 1); // should fail
})->throws(\App\Services\Tenants\InsufficientBalanceException::class);
```

### 10.2 Integration Tests (Pest)

```php
<?php

test('webhook handler returns 200 on valid signature', function () {
    $selling = Selling::factory()->create();
    MidtransPayment::create(['selling_id' => $selling->id, 'order_id' => 'test-order', 'gross_amount' => 100000, 'status' => 'pending']);

    $notification = getMidtransSampleNotification('settlement'); // helper test
    $this->postJson('/api/webhooks/midtrans', $notification)
        ->assertStatus(200);
});

test('webhook rejects invalid IP', function () {
    // Middleware or handler level test
    $notification = getMidtransSampleNotification('settlement');
    $this->withHeader('REMOTE_ADDR', '192.168.1.1') // not in whitelist
        ->postJson('/api/webhooks/midtrans', $notification)
        ->assertStatus(403);
});

test('withdrawal request uses idempotency_logs table', function () {
    $this->seed(); // seed PaymentMethod with 'Cash'

    $this->actingAs(User::first())
        ->postJson('/api/tenant/withdrawals', [
            'amount' => 50000,
            'idempotency_key' => 'test-wd-1',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('idempotency_logs', [
        'idempotency_key' => 'test-wd-1',
        'status' => 'completed',
    ]);
});
```

### 10.3 Codebase Conventions for Tests

```php
// Use RefreshDatabaseWithTenant for tenant-scoped tests
// See: tests/RefreshDatabaseWithTenant.php

uses(\Tests\RefreshDatabaseWithTenant::class);

test('selling creates MidtransPayment on digital payment method', function () {
    // Test the SellingService integration
});
```

## 11. Risks & Mitigations

| Risk | Severity | Mitigation |
|------|----------|------------|
| Midtrans API downtime | High | Queue webhooks, retry up to 72h. Display "payment unavailable" in POS |
| Race condition balance | Critical | MySQL `GET_LOCK()` + `DB::transaction()` + invariant check |
| Wrong fee calculation | High | Unit test 100% coverage for all payment types. Fee snapshot saved in ledger |
| Disbursement API failed | Medium | Retry with backoff. Manual fallback + auto rollback balance (refund ledger entry) |
| Fraud / fake transactions | Critical | Webhook IP whitelist + SHA512 signature verification. Reconciliation daily |
| DB corruption financial data | Critical | Hourly backup of ledger + `idempotency_logs` table. Read-only replica for reports |
| Double withdrawal | Critical | Idempotency via existing `idempotency_logs` table |
| Tenant disputes | Medium | Spatie activitylog immutable log. Reconciliation evidence. 1 year data retention |
| Wrong double conversion rounding | Medium | All double money calculations use `round(..., 0)`. Unit-test coverage |

## 12. Assumptions & Constraints

- All amounts in IDR (Indonesian Rupiah)
- Amounts stored as **`double`** (matches existing `sellings.payed_money`, `sellings.total_price`, `sellings.fee` columns)
- Midtrans production MDR rates may differ from sandbox — update config before go-live
- Flip/Duitku integration requires separate legal agreement & API key registration
- Bank account info stored in `abouts` table (tenant settings), NOT in central `tenants` table
- Settlement reconciliation has 1-day delay (Midtrans settles T+1 ~ T+2)
- Minimum withdrawal amount: Rp 50,000
- Maximum withdrawal amount: 95% of available balance (5% buffer for refunds)
- Platform fee default: 1% (configurable per tenant via `abouts.platform_fee_percent`)
- Webhook IP whitelist via `.env` `MIDTRANS_WEBHOOK_IPS` (comma-separated)
- Idempotency logs reuse existing `idempotency_logs` table (2026_05_29_143139)
- MySQL `GET_LOCK()` / `RELEASE_LOCK()` for concurrent entry protection
- All new migrations go in `database/migrations/tenant/` (stancl/tenancy per-tenant DB)
- Model namespace: `App\Models\Tenants\`
- Guarded pattern: `$guarded = ['id']` or `[]`
- Feature flags via Laravel Pennant (existing `PaymentMethod` feature)
- Activity logging via `spatie/laravel-activitylog` trait `LogsActivity`
- Queue driver: default `sync`, production switch to `database` or `redis` via `.env`
- `SellingCreated` event is extension point for payment gateway hooks

