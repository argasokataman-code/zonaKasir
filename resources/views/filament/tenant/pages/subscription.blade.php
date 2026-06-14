<x-filament-panels::page>
    @php
        $current = app(\App\Filament\Tenant\Pages\ManageSubscription::class)->getCurrentPlan();
        $plans = app(\App\Filament\Tenant\Pages\ManageSubscription::class)->getAvailablePlans();
    @endphp

    @if($snapRedirectUrl)
    <div class="bg-blue-50 border border-blue-200 rounded-[6px] p-6 text-center mb-6">
        <h3 class="text-lg font-bold text-blue-900 mb-2">Payment Required</h3>
        <p class="text-blue-700 mb-4">Click below to complete your payment via Midtrans.</p>
        <x-filament::button
            tag="a"
            href="{{ $snapRedirectUrl }}"
            target="_blank"
            color="success"
            icon="heroicon-o-credit-card"
            class="text-lg px-8 py-4"
        >
            Pay Now
        </x-filament::button>
        <p class="text-sm text-blue-600 mt-3">New window will open. Complete payment there.</p>
    </div>
    @endif

    @if($current)
    <div class="bg-white border border-[#E5E5E1] rounded-[6px] p-6 mb-6 shadow-sm">
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">Current Plan</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Plan</p>
                <p class="text-lg font-bold text-gray-900 mt-1">{{ $current['name'] }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</p>
                <p class="mt-1">
                    @if($current['is_on_trial'])
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-yellow-100 text-yellow-800">Trial</span>
                    @elseif($current['status'] === 'active')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800">Active</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">{{ ucfirst($current['status']) }}</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Billing</p>
                <p class="text-lg font-bold text-gray-900 mt-1">{{ ucfirst($current['billing_cycle']) }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Limits</p>
                <p class="text-lg font-bold text-gray-900 mt-1">{{ $current['max_stores'] }} stores / {{ $current['max_users'] }} users</p>
            </div>
        </div>
        @if(count($current['features']) > 0)
        <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Features</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach($current['features'] as $feature)
                <span class="px-2 py-0.5 bg-green-50 text-green-700 text-[10px] font-medium rounded-full border border-green-200">{{ $feature }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    <div class="mb-6">
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">Available Plans</h2>
        <div class="overflow-x-auto -mx-6 px-6 pb-2 scrollbar-thin">
            <div class="flex gap-4" style="min-width: min-content;">
                @foreach($plans as $plan)
                <div
                    x-data="{ open: false }"
                    class="bg-white rounded-[6px] shadow-sm flex flex-col relative border @if($current && $current['id'] === $plan['id']) border-2 border-gray-900 shadow-md @else border-[#E5E5E1] @endif"
                    style="width: 280px; min-width: 280px; flex-shrink: 0;"
                >
                    @if(($plan['is_popular'] ?? false) && $plan['price_monthly'] > 0)
                    <div class="absolute top-0 right-0 bg-gray-900 text-white text-[8px] font-mono font-bold uppercase tracking-widest px-3.5 py-1.5 rounded-bl-[4px] rounded-tr-[5px]">
                        Popular
                    </div>
                    @endif

                    <div class="p-5 flex flex-col h-full">
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">
                                {{ $plan['max_stores'] > 10 ? 'Enterprise' : ($plan['max_stores'] > 1 ? 'Bisnis' : 'Pemula') }}
                            </span>
                            <h3 class="font-sans font-bold text-base text-gray-900">
                                {{ $plan['name'] }}
                            </h3>
                        </div>

                        <div class="py-3 my-3 border-y border-gray-100">
                            @if(($plan['is_on_premise'] ?? false))
                                <span class="font-mono text-xl font-black text-gray-900">Custom</span>
                                <span class="text-[9px] text-gray-500 font-bold block uppercase tracking-wider mt-0.5">Self-Hosted</span>
                            @elseif(($plan['price_monthly'] ?? 0) === 0)
                                <span class="font-mono text-2xl font-black text-gray-900">Gratis</span>
                                <span class="text-[9px] text-gray-500 font-bold block uppercase tracking-wider mt-0.5">Selamanya</span>
                            @else
                                <span class="font-mono text-2xl font-black text-gray-900">Rp {{ number_format($plan['price_monthly'], 0, ',', '.') }}</span>
                                <span class="text-[9px] text-gray-500 font-bold block uppercase tracking-wider mt-0.5">Per Bulan</span>
                                @if($plan['price_yearly'])
                                <span class="text-[9px] text-gray-400 block mt-0.5">Rp {{ number_format($plan['price_yearly'], 0, ',', '.') }}/tahun</span>
                                @endif
                            @endif
                        </div>

                        <div class="text-[10px] text-gray-400 font-semibold mb-2">
                            {{ $plan['max_stores'] }} outlet / {{ $plan['max_users'] }} user
                        </div>

                        @if(!empty($plan['features']))
                        <button
                            type="button"
                            x-on:click="open = !open"
                            class="w-full flex items-center justify-between text-[10px] font-bold text-gray-900 uppercase tracking-wider py-1.5 border-t border-gray-100 cursor-pointer hover:text-gray-600 transition-colors"
                        >
                            <span>Fitur ({{ count($plan['features']) }})</span>
                            <svg class="w-3 h-3 transition-transform duration-200" x-bind:class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div
                            x-show="open"
                            x-collapse
                            x-cloak
                            class="overflow-hidden"
                        >
                            <ul class="space-y-1.5 text-[11px] text-gray-600 font-medium py-2">
                                @foreach($plan['features'] as $key => $label)
                                <li class="flex items-start gap-2">
                                    <span class="w-3.5 h-3.5 rounded-full bg-emerald-100 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-2 h-2 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <span>{{ is_string($label) ? $label : (is_string($key) ? $key : $label) }}</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    @if(!$snapRedirectUrl)
    <div class="bg-white border border-[#E5E5E1] rounded-[6px] p-6 shadow-sm mb-6">
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">Upgrade / Change Plan</h2>
        <div class="max-w-lg">
            {{ $this->form }}
            <div class="mt-4">
                <x-filament::button wire:click="subscribe" color="primary">
                    Subscribe & Pay
                </x-filament::button>
            </div>
        </div>
    </div>
    @endif

    @php $invoices = app(\App\Filament\Tenant\Pages\ManageSubscription::class)->getInvoices(); @endphp
    @if(count($invoices) > 0)
    <div class="bg-white border border-[#E5E5E1] rounded-[6px] p-6 shadow-sm">
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">Invoice History</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left">
                        <th class="py-2 pr-4 font-bold text-gray-500 text-[10px] uppercase tracking-widest">Invoice</th>
                        <th class="py-2 pr-4 font-bold text-gray-500 text-[10px] uppercase tracking-widest">Amount</th>
                        <th class="py-2 pr-4 font-bold text-gray-500 text-[10px] uppercase tracking-widest">Status</th>
                        <th class="py-2 pr-4 font-bold text-gray-500 text-[10px] uppercase tracking-widest">Date</th>
                        <th class="py-2 font-bold text-gray-500 text-[10px] uppercase tracking-widest">Payment</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $inv)
                    <tr class="border-b border-gray-100">
                        <td class="py-3 pr-4 font-mono text-xs text-gray-900">{{ $inv['number'] }}</td>
                        <td class="py-3 pr-4 font-medium text-sm">Rp {{ number_format($inv['amount'], 0, ',', '.') }}</td>
                        <td class="py-3 pr-4">
                            @if($inv['status'] === 'paid')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800">Paid</span>
                            @elseif($inv['status'] === 'pending')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-yellow-100 text-yellow-800">Pending</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">{{ ucfirst($inv['status']) }}</span>
                            @endif
                        </td>
                        <td class="py-3 pr-4 text-sm text-gray-600">{{ \Carbon\Carbon::parse($inv['created_at'])->format('d M Y') }}</td>
                        <td class="py-3 text-sm text-gray-600">{{ $inv['payment_method'] ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</x-filament-panels::page>
