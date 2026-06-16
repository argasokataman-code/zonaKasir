<?php

namespace App\Http\Controllers\Api\Tenants;

use App\Http\Controllers\Controller;
use App\Models\Tenants\About;
use App\Models\Tenants\Category;
use App\Models\Tenants\Member;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Product;
use App\Models\Tenants\Profile;
use App\Models\Tenants\Setting;
use App\Models\Tenants\Table;
use App\Models\Tenants\Voucher;
use App\Services\Tenants\SellingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    public function __construct(
        private SellingService $sellingService,
    ) {}

    /**
     * Bulk sync — returns all master data for offline caching.
     * Supports delta sync via ?since=ISO8601_timestamp.
     * Delta also returns deleted_ids for records removed since last sync.
     */
    public function data(Request $request): JsonResponse
    {
        $since = $request->query('since');
        $isDelta = ! empty($since);

        $products = Product::query()
            ->where(function ($query) {
                $query->where('type', 'product')
                    ->where(function ($query) {
                        $query->whereHas('stocks', function ($query) {
                            $query->where('is_ready', 1)
                                ->where('type', 'in')
                                ->where('stock', '>', 0);
                        })
                        ->orWhere('is_non_stock', true);
                    })
                ->orWhere('type', 'service');
            })
            ->where('show', true);

        if ($isDelta) {
            $products->where('updated_at', '>', $since);
        }

        $products = $products->get()->append(['selling_price_calculate', 'stock_calculate']);

        $categories = Category::query();
        $members = Member::query()->select('id', 'name', 'code', 'phone');
        $paymentMethods = PaymentMethod::query()->select('id', 'name', 'is_credit', 'payment_type')->where('is_active', true);

        if ($isDelta) {
            $categories->where('updated_at', '>', $since);
            $members->where('updated_at', '>', $since);
            $paymentMethods->where('updated_at', '>', $since);
        }

        $settings = [
            'currency' => Setting::get('currency', 'IDR'),
            'locale' => Profile::get()->locale ?? 'en',
            'default_tax' => (float) Setting::get('default_tax', 0),
        ];

        $tables = Table::select('id', 'number')->get();

        $vouchers = Voucher::select('id', 'name', 'code', 'type', 'nominal', 'kuota', 'start_date', 'expired', 'minimal_buying')
            ->where('expired', '>=', now())
            ->whereColumn('kuota', '>', DB::raw('used'))
            ->get();

        $response = [
            'products' => $products,
            'categories' => $categories->get(),
            'members' => $members->get(),
            'payment_methods' => $paymentMethods->get(),
            'vouchers' => $vouchers,
            'about' => About::first(),
            'settings' => $settings,
            'tables' => $tables,
            'is_delta' => $isDelta,
            'server_time' => now()->toIso8601String(),
        ];

        // Track deleted records for delta sync
        if ($isDelta) {
            $response['deleted_ids'] = $this->getDeletedIds($since);
        }

        return $this->buildResponse()
            ->setData($response)
            ->setMessage($isDelta ? 'success delta sync' : 'success full sync')
            ->present();
    }

    /**
     * Get IDs of records deleted since the given timestamp.
     * This requires soft deletes or a deleted_records log table.
     * If not available, returns empty arrays (full sync on next refresh).
     */
    private function getDeletedIds(string $since): array
    {
        $deleted = [];

        // Check for soft-deleted records
        if ($this->usesSoftDeletes(Product::class)) {
            $deleted['products'] = Product::onlyTrashed()
                ->where('deleted_at', '>', $since)
                ->pluck('id');
        }

        if ($this->usesSoftDeletes(Category::class)) {
            $deleted['categories'] = Category::onlyTrashed()
                ->where('deleted_at', '>', $since)
                ->pluck('id');
        }

        if ($this->usesSoftDeletes(Member::class)) {
            $deleted['members'] = Member::onlyTrashed()
                ->where('deleted_at', '>', $since)
                ->pluck('id');
        }

        return $deleted;
    }

    /**
     * Check if a model uses the SoftDeletes trait.
     */
    private function usesSoftDeletes(string $modelClass): bool
    {
        if (! class_exists($modelClass)) return false;

        try {
            $instance = new $modelClass;

            return method_exists($instance, 'runSoftDelete');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Submit a pending sale from offline queue.
     * Same payload as POST /api/transaction/selling.
     */
    public function submit(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->all();

            // Map selling_details to products if needed
            if (! isset($data['products']) && isset($data['selling_details'])) {
                $data['products'] = array_map(function ($item) {
                    return [
                        'product_id' => $item['product_id'] ?? null,
                        'qty' => $item['quantity'] ?? $item['qty'] ?? 1,
                        'price' => $item['price'] ?? null,
                        'discount_price' => $item['discount_price'] ?? 0,
                        'price_unit_id' => $item['price_unit_id'] ?? null,
                    ];
                }, $data['selling_details']);
            }

            if (! isset($data['payed_money']) && isset($data['total_price'])) {
                $data['payed_money'] = $data['total_price'];
            }

            $mapped = $this->sellingService->mapProductRequest($data);
            $data = array_merge($data, $mapped);

            $selling = $this->sellingService->create($data);
            $selling->load(['member', 'paymentMethod', 'sellingDetails.product', 'user']);

            DB::commit();

            return $this->buildResponse()
                ->setCode(201)
                ->setData($selling)
                ->setMessage('success sync selling')
                ->present();

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Sync submit failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->buildResponse()
                ->setCode(422)
                ->setMessage('Sync failed: '.$e->getMessage())
                ->present();
        }
    }
}
