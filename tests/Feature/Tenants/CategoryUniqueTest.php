<?php

use App\Models\Tenants\Category;
use App\Models\Tenants\User;
use Illuminate\Support\Facades\DB;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

beforeEach(function () {
    $this->user = User::first();
    $this->tenantId = $this->user->tenant_id;
});

// ─── Schema ─────────────────────────────────────────────

describe('Category table schema', function () {

    it('has unique index on (tenant_id, name)', function () {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $indexes = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'categories'");
            $names = array_map(fn ($i) => $i->indexname, $indexes);

            expect($names)->toContain('categories_tenant_id_name_unique');
        } else {
            // MySQL/SQLite: check via Schema::getIndexes
            $indexes = DB::connection()->getDoctrineSchemaManager()->listTableIndexes('categories');
            $names = array_keys($indexes);

            expect($names)->toContain('categories_tenant_id_name_unique');
        }
    });
});

// ─── CRUD ─────────────────────────────────────────────

describe('Category CRUD', function () {

    it('can create a category', function () {
        $cat = Category::create(['name' => 'KategoriTes123', 'tenant_id' => $this->tenantId]);

        expect($cat->fresh()->name)->toBe('KategoriTes123');
    });

    it('can update a category name', function () {
        $cat = Category::create(['name' => 'Lama456', 'tenant_id' => $this->tenantId]);
        $cat->update(['name' => 'Baru789']);

        expect($cat->fresh()->name)->toBe('Baru789');
    });
});

// ─── Unique Constraint ────────────────────────────────────

describe('Category unique constraint', function () {

    it('prevents duplicate name in same tenant', function () {
        Category::create(['name' => 'DupTest001', 'tenant_id' => $this->tenantId]);

        expect(fn () => Category::create(['name' => 'DupTest001', 'tenant_id' => $this->tenantId]))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('allows same name in different tenants', function () {
        Category::create(['name' => 'CrossTenant001', 'tenant_id' => 'tenant_a_test']);
        Category::create(['name' => 'CrossTenant001', 'tenant_id' => 'tenant_b_test']);

        expect(DB::table('categories')->where('name', 'CrossTenant001')->count())->toBe(2);
    });

    it('prevents renaming to an existing name', function () {
        Category::create(['name' => 'Alpha999', 'tenant_id' => $this->tenantId]);
        $beta = Category::create(['name' => 'Beta999', 'tenant_id' => $this->tenantId]);

        $beta->name = 'Alpha999';

        expect(fn () => $beta->save())->toThrow(\Illuminate\Database\QueryException::class);
    });
});
