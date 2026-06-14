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
     */
    public function data(): JsonResponse
    {
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
            ->where('show', true)
            ->get()
            ->append(['selling_price_calculate', 'stock_calculate']);

        $categories = Category::all();

        $members = Member::query()
            ->select('id', 'name', 'code', 'phone')
            ->get();

        $paymentMethods = PaymentMethod::query()
            ->select('id', 'name', 'is_credit', 'payment_type')
            ->get();

        $about = About::first();

        $settings = [
            'currency' => Setting::get('currency', 'IDR'),
            'locale' => Profile::get()->locale ?? 'en',
            'default_tax' => (float) Setting::get('default_tax', 0),
        ];

        $tables = Table::select('id', 'number')->get();

        return $this->buildResponse()
            ->setData([
                'products' => $products,
                'categories' => $categories,
                'members' => $members,
                'payment_methods' => $paymentMethods,
                'about' => $about,
                'settings' => $settings,
                'tables' => $tables,
            ])
            ->setMessage('success sync data')
            ->present();
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
