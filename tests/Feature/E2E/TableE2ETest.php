<?php

use App\Filament\Tenant\Pages\Cashier;
use App\Models\Tenants\Category;
use App\Models\Tenants\Member;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Product;
use App\Models\Tenants\Selling;
use App\Models\Tenants\Table;
use App\Models\Tenants\User;
use Illuminate\Support\Facades\Schema;
use Tests\RefreshDatabaseWithTenant;

use function Pest\Laravel\actingAs;

uses(RefreshDatabaseWithTenant::class);

beforeEach(function () {
    $this->user = User::first();
    $this->category = Category::factory()->create();
    $this->product = Product::factory()->create([
        'name' => 'Test Product',
        'initial_price' => 10000,
        'selling_price' => 25000,
        'stock' => 10,
        'category_id' => $this->category->id,
    ]);
});

// ─── Database Schema ─────────────────────────────────────

describe('Table Database Schema', function () {

    it('has tables with correct structure', function () {
        expect(Schema::hasTable('tables'))->toBeTrue();
        expect(Schema::hasColumn('tables', 'number'))->toBeTrue();
        expect(Schema::hasColumn('tables', 'created_at'))->toBeTrue();
    });

    it('has table_id foreign key on sellings table', function () {
        expect(Schema::hasColumn('sellings', 'table_id'))->toBeTrue();
    });

});

// ─── Table CRUD (Model Level) ────────────────────────────

describe('Table CRUD', function () {

    it('can create a table', function () {
        $table = Table::create(['number' => 'T-01']);

        expect($table->id)->not->toBeNull();
        expect($table->number)->toBe('T-01');
    });

    it('can create multiple tables', function () {
        Table::create(['number' => 'T-01']);
        Table::create(['number' => 'T-02']);
        Table::create(['number' => 'VIP-01']);

        expect(Table::count())->toBe(3);
    });

    it('can read a table', function () {
        $table = Table::create(['number' => 'T-05']);

        $found = Table::find($table->id);
        expect($found->number)->toBe('T-05');
    });

    it('can update a table number', function () {
        $table = Table::create(['number' => 'T-01']);
        $table->update(['number' => 'T-01-VIP']);

        expect($table->fresh()->number)->toBe('T-01-VIP');
    });

    it('can soft delete and restore a table', function () {
        $table = Table::create(['number' => 'T-01']);
        $table->delete();

        expect(Table::withTrashed()->find($table->id))->not->toBeNull();
        expect(Table::find($table->id))->toBeNull();

        $table->restore();
        expect(Table::find($table->id))->not->toBeNull();
    });

});

// ─── Table → Selling Relationship ────────────────────────

describe('Table and Selling Relationship', function () {

    it('selling belongs to table when table_id is set', function () {
        $table = Table::create(['number' => 'T-01']);
        $selling = Selling::create([
            'user_id' => $this->user->id,
            'table_id' => $table->id,
            'total_price' => 25000,
            'total_qty' => 1,
            'payed_money' => 50000,
            'money_changes' => 25000,
            'friend_price' => false,
        ]);

        expect($selling->table)->not->toBeNull();
        expect($selling->table->number)->toBe('T-01');
    });

    it('selling has null table when no table_id', function () {
        $selling = Selling::create([
            'user_id' => $this->user->id,
            'total_price' => 25000,
            'total_qty' => 1,
            'payed_money' => 50000,
            'money_changes' => 25000,
            'friend_price' => false,
        ]);

        expect($selling->table)->toBeNull();
        expect($selling->table_id)->toBeNull();
    });

    it('table has many sellings', function () {
        $table = Table::create(['number' => 'T-01']);
        Selling::create([
            'user_id' => $this->user->id,
            'table_id' => $table->id,
            'total_price' => 25000,
            'total_qty' => 1,
            'payed_money' => 50000,
            'money_changes' => 25000,
            'friend_price' => false,
        ]);
        Selling::create([
            'user_id' => $this->user->id,
            'table_id' => $table->id,
            'total_price' => 50000,
            'total_qty' => 2,
            'payed_money' => 50000,
            'money_changes' => 0,
            'friend_price' => false,
        ]);

        expect($table->sellings)->toHaveCount(2);
    });

    it('selling with table_id persists correctly in database', function () {
        $table = Table::create(['number' => 'T-03']);
        $selling = Selling::create([
            'user_id' => $this->user->id,
            'table_id' => $table->id,
            'total_price' => 75000,
            'total_qty' => 3,
            'payed_money' => 100000,
            'money_changes' => 25000,
            'friend_price' => false,
        ]);

        $this->assertDatabaseHas('sellings', [
            'id' => $selling->id,
            'table_id' => $table->id,
        ]);
    });

});

// ─── Cashier Component Loads Tables ──────────────────────

describe('Cashier Table Integration', function () {

    it('cashier loads table options from database', function () {
        Table::create(['number' => 'T-01']);
        Table::create(['number' => 'T-02']);
        Table::create(['number' => 'VIP-01']);

        $page = new Cashier();
        $page->mount();

        expect($page->tableOption)->not->toBeNull();
        expect($page->tableOption)->toHaveCount(3);
        expect($page->tableOption->pluck('number')->toArray())
            ->toEqual(['T-01', 'T-02', 'VIP-01']);
    });

    it('cashier table option is empty when no tables exist', function () {
        $page = new Cashier();
        $page->mount();

        expect($page->tableOption)->toHaveCount(0);
    });

    it('cashier table option has id and number fields', function () {
        Table::create(['number' => 'T-01']);

        $page = new Cashier();
        $page->mount();

        $table = $page->tableOption->first();
        expect($table->id)->not->toBeNull();
        expect($table->number)->toBe('T-01');
    });

});

// ─── Complete Sale with Table ────────────────────────────

describe('Complete Sale with Table', function () {

    it('can create selling with table_id via API', function () {
        $table = Table::create(['number' => 'T-01']);
        $member = Member::factory()->create();
        $paymentMethod = PaymentMethod::first();

        $response = actingAs($this->user, 'sanctum')
            ->postJson('/api/transaction/selling', [
                'payed_money' => 50000,
                'friend_price' => false,
                'member_id' => $member->getKey(),
                'payment_method_id' => $paymentMethod->id,
                'table_id' => $table->id,
                'products' => [
                    [
                        'product_id' => $this->product->id,
                        'qty' => 1,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $selling = Selling::latest()->first();
        expect($selling->table_id)->toBe($table->id);
        expect($selling->table->number)->toBe('T-01');
    });

    it('can create selling without table_id', function () {
        $member = Member::factory()->create();
        $paymentMethod = PaymentMethod::first();

        $response = actingAs($this->user, 'sanctum')
            ->postJson('/api/transaction/selling', [
                'payed_money' => 50000,
                'friend_price' => false,
                'member_id' => $member->getKey(),
                'payment_method_id' => $paymentMethod->id,
                'products' => [
                    [
                        'product_id' => $this->product->id,
                        'qty' => 1,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $selling = Selling::latest()->first();
        expect($selling->table_id)->toBeNull();
    });

});
