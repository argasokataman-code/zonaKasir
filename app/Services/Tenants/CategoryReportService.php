<?php

namespace App\Services\Tenants;

use App\Models\Tenants\About;
use App\Models\Tenants\Profile;
use App\Models\Tenants\Selling;
use App\Models\Tenants\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

class CategoryReportService
{
    public function generate(array $data)
    {
        $timezone = Profile::select('timezone')->first()?->timezone ?? 'UTC';
        $about = About::select('id', 'shop_name', 'shop_location', 'business_type')->first();
        $startDate = Carbon::parse($data['start_date'], $timezone)->setTimezone('UTC');
        $endDate = Carbon::parse($data['end_date'], $timezone)->addDay()->setTimezone('UTC');

        $sellings = Selling::query()
            ->select('id', 'discount_price', 'date', 'created_at')
            ->with(
                'sellingDetails:id,selling_id,product_id,qty,price,cost,discount_price',
                'sellingDetails.product:id,name,sku,category_id',
                'sellingDetails.product.category:id,name'
            )
            ->when($data['start_date'] && $data['end_date'], function (Builder $query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $header = [
            'shop_name' => $about?->shop_name,
            'shop_location' => $about?->shop_location,
            'business_type' => $about?->business_type,
            'owner_name' => User::select('id', 'name')->owner()->first()?->name ?? '',
            'start_date' => $startDate->setTimezone($timezone)->format('d F Y'),
            'end_date' => $endDate->subDay()->setTimezone($timezone)->format('d F Y'),
        ];

        $categoryData = [];

        foreach ($sellings as $selling) {
            foreach ($selling->sellingDetails as $detail) {
                $categoryId = $detail->product->category_id;
                $categoryName = $detail->product->category->name ?? 'Unknown';

                if (!isset($categoryData[$categoryId])) {
                    $categoryData[$categoryId] = [
                        'category_id' => $categoryId,
                        'category_name' => $categoryName,
                        'total_qty' => 0,
                        'total_revenue' => 0,
                        'total_cost' => 0,
                        'total_discount' => 0,
                    ];
                }

                $categoryData[$categoryId]['total_qty'] += $detail->qty;
                $categoryData[$categoryId]['total_revenue'] += $detail->price;
                $categoryData[$categoryId]['total_cost'] += $detail->cost;
                $categoryData[$categoryId]['total_discount'] += ($detail->discount_price ?? 0);
            }
        }

        $reports = [];
        $totalQty = 0;
        $totalRevenue = 0;
        $totalCost = 0;
        $totalDiscount = 0;

        foreach ($categoryData as $cat) {
            $revenue = $cat['total_revenue'] - $cat['total_discount'];
            $profit = $revenue - $cat['total_cost'];

            $reports[] = [
                'category_name' => $cat['category_name'],
                'total_qty' => $cat['total_qty'],
                'total_revenue' => $this->formatCurrency($cat['total_revenue']),
                'total_discount' => $this->formatCurrency($cat['total_discount']),
                'total_after_discount' => $this->formatCurrency($revenue),
                'total_cost' => $this->formatCurrency($cat['total_cost']),
                'total_profit' => $this->formatCurrency($profit),
            ];

            $totalQty += $cat['total_qty'];
            $totalRevenue += $cat['total_revenue'];
            $totalCost += $cat['total_cost'];
            $totalDiscount += $cat['total_discount'];
        }

        usort($reports, fn ($a, $b) => $b['total_qty'] <=> $a['total_qty']);

        $footer = [
            'total_qty' => $totalQty,
            'total_revenue' => $this->formatCurrency($totalRevenue),
            'total_discount' => $this->formatCurrency($totalDiscount),
            'total_after_discount' => $this->formatCurrency($totalRevenue - $totalDiscount),
            'total_cost' => $this->formatCurrency($totalCost),
            'total_profit' => $this->formatCurrency($totalRevenue - $totalDiscount - $totalCost),
        ];

        return [
            'reports' => $reports,
            'footer' => $footer,
            'header' => $header,
        ];
    }

    private function formatCurrency($value)
    {
        return Number::format($value);
    }
}