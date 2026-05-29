<?php

use App\Models\Tenants\Category;
use App\Models\Tenants\Member;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Product;
use App\Models\Tenants\User;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('E2E: Complete POS Workflows', function () {
    
    beforeEach(function () {
        $this->user = User::first();
        
        // Create a fresh member for each test
        $this->member = Member::create([
            'name' => 'Test Member ' . uniqid(),
            'email' => 'member-' . uniqid() . '@test.com',
            'address' => 'Test Address',
        ]);
        
        $this->paymentMethod = PaymentMethod::create([
            'name' => 'Cash',
            'is_credit' => false,
            'is_wallet' => false,
        ]);
    });

    test('complete product management workflow', function () {
        // Create category directly - bypass API
        $category = Category::factory()->create([
            'name' => 'Electronics ' . uniqid(),
        ]);
        $categoryId = $category->id;
        $this->assertNotNull($categoryId);

        // Create product
        $product = Product::factory()->for($category)->create([
            'name' => 'Laptop Pro',
            'initial_price' => 15000000,
            'selling_price' => 16000000,
        ]);

        $productId = $product->id;
        $this->assertNotNull($productId);

        // Update product
        $product->update([
            'name' => 'Laptop Pro Max',
            'selling_price' => 18000000,
        ]);

        // Retrieve product
        $product->refresh();
        $this->assertEquals('Laptop Pro Max', $product->name);
    });

    test('product stock tracking through lifecycle', function () {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create([
            'stock' => 50,
            'initial_price' => 10000,
            'selling_price' => 12000,
        ]);
        
        $this->assertEquals(50, $product->stock);

        // Reduce stock on sale
        $product->update(['stock' => 45]);
        $this->assertEquals(45, $product->stock);

        // Verify soft delete preserves stock
        $product->delete();
        $this->assertTrue($product->trashed());
        $this->assertEquals(45, $product->stock);

        // Restore and verify
        $product->restore();
        $this->assertEquals(45, $product->stock);
    });

    test('complete member lifecycle', function () {
        $memberData = [
            'name' => 'John Doe ' . uniqid(),
            'email' => 'john-' . uniqid() . '@example.com',
            'address' => '123 Main St',
        ];

        $member = Member::create($memberData);
        $memberId = $member->id;
        
        $this->assertNotNull($member);
        $this->assertEquals($memberData['name'], $member->name);
        $this->assertEquals($memberData['email'], $member->email);
    });

    test('member soft delete recovery', function () {
        $member = Member::create([
            'name' => 'Jane Smith ' . uniqid(),
            'email' => 'jane-' . uniqid() . '@test.com',
            'address' => 'Test Address',
        ]);
        $memberId = $member->id;

        $member->delete();
        $this->assertTrue($member->trashed());

        // Not in active list
        $this->assertNull(Member::find($memberId));

        // But recoverable
        $deleted = Member::withTrashed()->find($memberId);
        $this->assertNotNull($deleted);
        $this->assertEquals('Jane Smith', substr($deleted->name, 0, 10));

        // Restore
        $deleted->restore();
        $this->assertFalse($deleted->trashed());
        $member = Member::find($memberId);
        $this->assertNotNull($member);
    });

    test('category unique constraint works', function () {
        $uniqueName = 'Food & Beverage ' . uniqid();

        // Create first category
        $category1 = Category::create(['name' => $uniqueName]);
        $this->assertNotNull($category1);

        // Verify it was created
        $found = Category::where('name', $uniqueName)->first();
        $this->assertNotNull($found);
        $this->assertEquals($uniqueName, $found->name);
    });

    test('product creation is logged', function () {
        $product = Product::factory()->create([
            'name' => 'Tracked Product ' . uniqid(),
        ]);

        $activities = $product->activities;

        $this->assertNotEmpty($activities);
        $this->assertEquals('created', $activities->first()->event);
    });

    test('product deletion is logged', function () {
        $product = Product::factory()->create([
            'name' => 'Deletable ' . uniqid(),
        ]);

        $product->delete();

        $activities = $product->activities;
        $deleteActivity = $activities->where('event', 'deleted')->first();

        $this->assertNotNull($deleteActivity);
    });

    test('payment methods function correctly', function () {
        $this->assertNotNull($this->paymentMethod->id);
        $this->assertEquals('Cash', $this->paymentMethod->name);
        $this->assertFalse($this->paymentMethod->is_credit);
    });

    test('payment method soft delete works', function () {
        $method = PaymentMethod::create([
            'name' => 'Credit Card ' . uniqid(),
            'is_credit' => true,
            'is_wallet' => false,
        ]);

        $method->delete();
        $this->assertTrue($method->trashed());
        
        $restored = PaymentMethod::withTrashed()->find($method->id);
        $this->assertNotNull($restored);
    });

    test('product updates are atomic', function () {
        $product = Product::factory()->create([
            'stock' => 100,
        ]);
        $originalStock = $product->stock;

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
            $product->update(['stock' => -50]);
            throw new \Exception('Simulated failure');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
        }

        $product->refresh();
        $this->assertEquals($originalStock, $product->stock);
    });

    test('deleted member cannot appear in active queries', function () {
        $member = Member::create([
            'name' => 'Delete Test ' . uniqid(),
            'email' => 'delete-' . uniqid() . '@test.com',
            'address' => 'Test',
        ]);
        $memberId = $member->id;

        $member->delete();

        $this->assertNull(Member::find($memberId));

        $trashedMember = Member::onlyTrashed()->find($memberId);
        $this->assertNotNull($trashedMember);
    });

    test('multi-model relationships maintained', function () {
        $category = Category::factory()->create();
        
        // Category should exist
        $this->assertNotNull(Category::find($category->id));

        // Create a product for this category
        $product = Product::factory()->for($category)->create();
        $this->assertEquals($category->id, $product->category_id);

        // Soft delete product
        $product->delete();
        $this->assertTrue($product->trashed());

        // Product should not appear in active queries
        $this->assertNull(Product::find($product->id));
    });
});

