<x-filament-panels::page>
    @php
        $current = app(\App\Filament\Tenant\Pages\ManageSubscription::class)->getCurrentPlan();
        $plans = app(\App\Filament\Tenant\Pages\ManageSubscription::class)->getAvailablePlans();
    @endphp

    @if($snapRedirectUrl)
    <x-filament::section>
        <x-slot name="heading">
            Payment Required
        </x-slot>
        <div class="text-center p-6">
            <p class="text-lg mb-4">Click below to complete your payment via Midtrans.</p>
            <x-filament::button
                tag="a"
                href="{{ $snapRedirectUrl }}"
                target="_blank"
                color="success"
                icon="heroicon-o-credit-card"
                class="text-lg px-8 py-4"
            >
                Pay Now (Rp {{ number_format($plans[0]['price_monthly'] ?? 0, 0, ',', '.') }})
            </x-filament::button>
            <p class="text-sm text-gray-500 mt-3">New window will open. Complete payment there.</p>
        </div>
    </x-filament::section>
    @endif

    @if($current)
    <x-filament::section>
        <x-slot name="heading">
            Current Plan
        </x-slot>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <p class="text-sm text-gray-500">Plan</p>
                <p class="text-lg font-semibold">{{ $current['name'] }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Status</p>
                <p class="text-lg font-semibold">
                    @if($current['is_on_trial'])
                        <span class="text-yellow-600">Trial</span>
                    @elseif($current['status'] === 'active')
                        <span class="text-green-600">Active</span>
                    @else
                        <span class="text-red-600">{{ ucfirst($current['status']) }}</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Billing</p>
                <p class="text-lg font-semibold">{{ ucfirst($current['billing_cycle']) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Limits</p>
                <p class="text-lg font-semibold">{{ $current['max_stores'] }} stores / {{ $current['max_users'] }} users</p>
            </div>
        </div>
        @if(count($current['features']) > 0)
        <div class="mt-4">
            <p class="text-sm text-gray-500 mb-2">Features:</p>
            <div class="flex flex-wrap gap-2">
                @foreach($current['features'] as $feature)
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">{{ $feature }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </x-filament::section>
    @endif

    <x-filament::section>
        <x-slot name="heading">
            Available Plans
        </x-slot>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($plans as $plan)
            <div class="border rounded-lg p-6 @if($current && $current['id'] === $plan['id']) border-blue-500 bg-blue-50 @else border-gray-200 @endif">
                <h3 class="text-xl font-bold">{{ $plan['name'] }}</h3>
                <p class="text-2xl font-extrabold mt-2">Rp {{ number_format($plan['price_monthly'], 0, ',', '.') }}<span class="text-sm font-normal text-gray-500">/bln</span></p>
                @if($plan['price_yearly'])
                <p class="text-sm text-gray-500">Rp {{ number_format($plan['price_yearly'], 0, ',', '.') }}/thn</p>
                @endif
                <div class="mt-4 text-sm text-gray-600">
                    <p>Max stores: {{ $plan['max_stores'] }}</p>
                    <p>Max users: {{ $plan['max_users'] }}</p>
                </div>
                @if($plan['features'])
                <div class="mt-3 flex flex-wrap gap-1">
                    @foreach($plan['features'] as $feature)
                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">{{ $feature }}</span>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </x-filament::section>

    @if(!$snapRedirectUrl)
    <x-filament::section>
        <x-slot name="heading">
            Upgrade / Change Plan
        </x-slot>
        <div class="max-w-lg">
            {{ $this->form }}
            <div class="mt-4">
                <x-filament::button wire:click="subscribe" color="primary">
                    Subscribe & Pay
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
    @endif

    @php $invoices = app(\App\Filament\Tenant\Pages\ManageSubscription::class)->getInvoices(); @endphp
    @if(count($invoices) > 0)
    <x-filament::section>
        <x-slot name="heading">
            Invoice History
        </x-slot>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="py-2 pr-4 font-semibold">Invoice</th>
                        <th class="py-2 pr-4 font-semibold">Amount</th>
                        <th class="py-2 pr-4 font-semibold">Status</th>
                        <th class="py-2 pr-4 font-semibold">Date</th>
                        <th class="py-2 font-semibold">Payment</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $inv)
                    <tr class="border-b border-gray-100">
                        <td class="py-2 pr-4 font-mono text-xs">{{ $inv['number'] }}</td>
                        <td class="py-2 pr-4">Rp {{ number_format($inv['amount'], 0, ',', '.') }}</td>
                        <td class="py-2 pr-4">
                            @if($inv['status'] === 'paid')
                                <span class="text-green-600 font-semibold">Paid</span>
                            @elseif($inv['status'] === 'pending')
                                <span class="text-yellow-600">Pending</span>
                            @else
                                <span class="text-red-600">{{ ucfirst($inv['status']) }}</span>
                            @endif
                        </td>
                        <td class="py-2 pr-4">{{ \Carbon\Carbon::parse($inv['created_at'])->format('d M Y') }}</td>
                        <td class="py-2">{{ $inv['payment_method'] ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
    @endif
</x-filament-panels::page>
