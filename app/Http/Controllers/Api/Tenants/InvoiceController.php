<?php

namespace App\Http\Controllers\Api\Tenants;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\InvoiceService;
use App\Services\PlanAccessService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stancl\Tenancy\Facades\Tenancy;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
        private PlanAccessService $planAccessService
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = tenant('id');

        $invoices = Tenancy::central(function () use ($tenantId) {
            return Invoice::where('tenant_id', $tenantId)
                ->latest()
                ->get();
        });

        return $this->buildResponse()
            ->setData($invoices)
            ->present();
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'payment_method' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $tenantId = tenant('id');

            $subscription = Tenancy::central(function () use ($tenantId) {
                return Subscription::with('plan')
                    ->where('tenant_id', $tenantId)
                    ->whereIn('status', ['trialing', 'active'])
                    ->latest()
                    ->first();
            });

            if (! $subscription) {
                return $this->buildResponse()
                    ->setCode(400)
                    ->setMessage('No active subscription found')
                    ->present();
            }

            if (! $subscription->plan) {
                return $this->buildResponse()
                    ->setCode(400)
                    ->setMessage('Current subscription has no plan assigned. Please subscribe to a plan first.')
                    ->present();
            }

            $invoice = $this->invoiceService->createInvoice(
                $subscription,
                $request->payment_method ?? 'manual'
            );

            return $this->buildResponse()
                ->setData($invoice)
                ->setMessage('Invoice created successfully')
                ->setCode(201)
                ->present();
        } catch (Exception $e) {
            return $this->buildResponse()
                ->setCode(400)
                ->setMessage($e->getMessage())
                ->present();
        }
    }

    public function show(string $id): JsonResponse
    {
        $tenantId = tenant('id');

        $invoice = Tenancy::central(function () use ($tenantId, $id) {
            return Invoice::where('tenant_id', $tenantId)
                ->where('id', $id)
                ->first();
        });

        if (! $invoice) {
            return $this->buildResponse()
                ->setCode(404)
                ->setMessage('Invoice not found')
                ->present();
        }

        return $this->buildResponse()
            ->setData($invoice)
            ->present();
    }

    public function pay(string $id): JsonResponse
    {
        try {
            $tenantId = tenant('id');

            $invoice = Tenancy::central(function () use ($tenantId, $id) {
                return Invoice::where('tenant_id', $tenantId)
                    ->where('id', $id)
                    ->first();
            });

            if (! $invoice) {
                return $this->buildResponse()
                    ->setCode(404)
                    ->setMessage('Invoice not found')
                    ->present();
            }

            $invoice = $this->invoiceService->processPayment($invoice);

            return $this->buildResponse()
                ->setData($invoice)
                ->setMessage('Payment successful')
                ->present();
        } catch (Exception $e) {
            return $this->buildResponse()
                ->setCode(400)
                ->setMessage($e->getMessage())
                ->present();
        }
    }

    public function features(): JsonResponse
    {
        $tenantId = tenant('id');

        $features = $this->planAccessService->getCurrentPlanFeatures($tenantId);
        $isActive = $this->planAccessService->isSubscriptionActive($tenantId);
        $maxStores = $this->planAccessService->getMaxStores($tenantId);
        $maxUsers = $this->planAccessService->getMaxUsers($tenantId);
        $onTrial = $this->planAccessService->isOnTrial($tenantId);
        $plan = $this->planAccessService->getPlan($tenantId);

        return $this->buildResponse()
            ->setData([
                'features' => $features,
                'is_subscription_active' => $isActive,
                'is_on_trial' => $onTrial,
                'limits' => [
                    'max_stores' => $maxStores,
                    'max_users' => $maxUsers,
                ],
                'plan' => $plan ? [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                ] : null,
            ])
            ->present();
    }
}