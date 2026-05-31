<?php

namespace Database\Seeders;

use App\Models\Tenants\Member;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Product;
use App\Models\Tenants\User;
use App\Services\Tenants\SellingService;
use Illuminate\Database\Seeder;

class E2ESeeder extends Seeder
{
    public function run(): void
    {
        // Ensure there's a credit payment method
        $credit = PaymentMethod::firstOrCreate([
            'name' => 'Credit',
        ], [
            'is_cash' => false,
            'is_debit' => false,
            'is_credit' => true,
            'is_wallet' => false,
            'icon' => null,
        ]);

        // Create members
        $members = Member::factory(10)->create();

        // Ensure there are products
        $products = Product::all();
        if ($products->count() === 0) {
            Product::factory(10)->create();
            $products = Product::all();
        }

        $userId = optional(User::first())->id ?? null;
        $sellingService = new SellingService();

        foreach ($members as $member) {
            // create 1-2 sellings per member
            $times = rand(1, 2);
            for ($i = 0; $i < $times; $i++) {
                $product = $products->random();
                $qty = rand(1, 5);

                $payload = [
                    'member_id' => $member->id,
                    'user_id' => $userId,
                    'date' => now()->subDays(rand(0, 30)),
                    'friend_price' => false,
                    'products' => [
                        [
                            'product_id' => $product->id,
                            'qty' => $qty,
                        ],
                    ],
                ];

                // Randomly make some sales credit (30% chance)
                if (rand(1, 100) <= 30) {
                    $payload['payment_method_id'] = $credit->id;
                    $payload['payed_money'] = 0;
                } else {
                    // leave payment method null so default 'Cash' is used
                    $payload['payed_money'] = 0;
                }

                $calculated = $sellingService->mapProductRequest($payload);
                $data = array_merge($payload, $calculated);

                // Ensure each product entry has a price (so ReceivableService can use it)
                foreach ($data['products'] as &$p) {
                    if (! isset($p['price'])) {
                        $prodModel = Product::find($p['product_id']);
                        $p['price'] = ($prodModel->selling_price ?? 0) * ($p['qty'] ?? 1);
                    }
                }
                unset($p);

                if (! isset($payload['payment_method_id'])) {
                    // cash: mark as fully paid
                    $data['payed_money'] = $data['total_price'];
                }

                // Create selling record without 'products' field, then dispatch events
                $createData = $data;
                unset($createData['products']);

                \DB::beginTransaction();
                $selling = \App\Models\Tenants\Selling::create($createData);
                // dispatch event listeners that handle selling details and receivable creation
                \App\Events\SellingCreated::dispatch($selling, $data);
                $productIds = collect($data['products'])->pluck('product_id')->toArray();
                $productsCollection = Product::whereIn('id', $productIds)->get();
                \App\Events\RecalculateEvent::dispatch($productsCollection, $data);
                \DB::commit();
            }
        }
    }
}
