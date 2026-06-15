<x-filament-panels::page>
    @php
        $current = app(\App\Filament\Tenant\Pages\ManageSubscription::class)->getCurrentPlan();
        $plans = app(\App\Filament\Tenant\Pages\ManageSubscription::class)->getAvailablePlans();
    @endphp

    @if($snapRedirectUrl)
    <div
      x-data="{ url: '{{ $snapRedirectUrl }}' }"
      x-init="$nextTick(() => window.location.href = url)"
      class="bg-blue-50 border border-blue-200 rounded-[6px] p-6 text-center mb-6"
    >
        <h3 class="text-lg font-bold text-blue-900 mb-2">Payment Required</h3>
        <p class="text-blue-700 mb-4">Mengarahkan ke Midtrans...</p>
        <a
          href="{{ $snapRedirectUrl }}"
          target="_blank"
          class="inline-flex items-center gap-1.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-500 px-6 py-3 rounded-lg transition-colors"
        >
          Pay Now
        </a>
        <p class="text-sm text-blue-600 mt-3">Jika tidak teralihkan, klik tombol di atas.</p>
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
        <div class="grid gap-4 pb-2 sm:overflow-x-auto sm:-mx-6 sm:px-6 sm:flex sm:flex-nowrap sm:scrollbar-thin" style="grid-template-columns: 1fr;">
            @foreach($plans as $plan)
            <div
                x-data="{ open: false }"
                class="bg-white rounded-[6px] shadow-sm flex flex-col relative border @if($current && $current['id'] === $plan['id']) border-2 border-gray-900 shadow-md @else border-[#E5E5E1] @endif w-full sm:flex-shrink-0 sm:w-[280px] sm:min-w-[280px]"
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

                            <div class="mt-auto pt-3 border-t border-gray-100 relative" x-data="{ showBilling: false, showConfirm: false, confirmPlan: null, confirmBilling: '', selectedBilling: '' }">
                                @if($current && $current['id'] === $plan['id'])
                                    @if($current['is_on_trial'])
                                        <button
                                            type="button"
                                            x-on:click="if (selectedBilling) { $wire.subscribePlan({{ $plan['id'] }}, selectedBilling) } else { showBilling = !showBilling }"
                                            class="block w-full text-center text-[10px] font-bold uppercase tracking-widest py-2 bg-green-600 text-white rounded-[4px] hover:bg-green-500 transition-colors cursor-pointer"
                                        >
                                            <span x-show="!selectedBilling">Bayar Sekarang</span>
                                            <span x-show="selectedBilling === 'monthly'">Bayar Sekarang - Bulanan</span>
                                            <span x-show="selectedBilling === 'yearly'">Bayar Sekarang - Tahunan</span>
                                        </button>

                                        <div
                                            x-show="showBilling && !selectedBilling"
                                            x-cloak
                                            x-on:click.away="showBilling = false"
                                            class="absolute bottom-full left-0 right-0 mb-1 bg-white border border-gray-200 rounded-[6px] shadow-lg overflow-hidden z-10"
                                        >
                                            <button
                                                type="button"
                                                x-on:click="selectedBilling = 'monthly'; showBilling = false"
                                                class="w-full text-left px-3 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer flex items-center justify-between"
                                            >
                                                <span>Bulanan</span>
                                                <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                            @if(($plan['price_yearly'] ?? 0) > 0)
                                            <button
                                                type="button"
                                                x-on:click="selectedBilling = 'yearly'; showBilling = false"
                                                class="w-full text-left px-3 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 border-t border-gray-100 transition-colors cursor-pointer flex items-center justify-between"
                                            >
                                                <span>Tahunan</span>
                                                <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                            @endif
                                        </div>
                                    @else
                                        <span class="block w-full text-center text-[10px] font-bold uppercase tracking-widest py-2 bg-gray-900 text-white rounded-[4px]">Paket Aktif</span>
                                    @endif
                            @elseif(($plan['price_monthly'] ?? 0) === 0)
                                <span class="block w-full text-center text-[10px] font-bold uppercase tracking-widest py-2 bg-gray-100 text-gray-500 rounded-[4px]">Gratis</span>
                            @else
                                <button
                                    type="button"
                                    x-on:click="showBilling = !showBilling"
                                    class="block w-full text-center text-[10px] font-bold uppercase tracking-widest py-2 bg-gray-900 text-white rounded-[4px] hover:bg-gray-700 transition-colors cursor-pointer"
                                >
                                    {{ $current ? 'Ganti Paket' : 'Upgrade Sekarang' }}
                                </button>

                                <div
                                    x-show="showBilling"
                                    x-cloak
                                    x-on:click.away="showBilling = false"
                                    class="absolute bottom-full left-0 right-0 mb-1 bg-white border border-gray-200 rounded-[6px] shadow-lg overflow-hidden z-10"
                                >
                                    @if($current)
                                    <button
                                        type="button"
                                        x-on:click="showConfirm = true; confirmPlan = {{ $plan['id'] }}; confirmBilling = 'monthly'; showBilling = false"
                                        class="w-full text-left px-3 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer flex items-center justify-between"
                                    >
                                        <span>Bulanan</span>
                                        <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                    @if(($plan['price_yearly'] ?? 0) > 0)
                                    <button
                                        type="button"
                                        x-on:click="showConfirm = true; confirmPlan = {{ $plan['id'] }}; confirmBilling = 'yearly'; showBilling = false"
                                        class="w-full text-left px-3 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 border-t border-gray-100 transition-colors cursor-pointer flex items-center justify-between"
                                    >
                                        <span>Tahunan</span>
                                        <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                    @endif
                                    @else
                                    <button
                                        type="button"
                                        wire:click="subscribePlan({{ $plan['id'] }}, 'monthly')"
                                        class="w-full text-left px-3 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer flex items-center justify-between"
                                    >
                                        <span>Bulanan</span>
                                        <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                    @if(($plan['price_yearly'] ?? 0) > 0)
                                    <button
                                        type="button"
                                        wire:click="subscribePlan({{ $plan['id'] }}, 'yearly')"
                                        class="w-full text-left px-3 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 border-t border-gray-100 transition-colors cursor-pointer flex items-center justify-between"
                                    >
                                        <span>Tahunan</span>
                                        <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                    @endif
                                    @endif
                                </div>

                                <div
                                    x-show="showConfirm"
                                    x-cloak
                                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                                >
                                    <div class="absolute inset-0 bg-black/50" x-on:click="showConfirm = false"></div>
                                    <div class="relative bg-white rounded-[8px] shadow-xl w-full max-w-sm p-6">
                                        <h3 class="text-sm font-bold text-gray-900 mb-1">Konfirmasi Ganti Paket</h3>
                                        <p class="text-xs text-gray-500 mb-4">Anda akan mengganti paket ke <span class="font-bold" x-text="confirmBilling === 'yearly' ? 'Tahunan' : 'Bulanan'"></span>. Lanjutkan?</p>
                                        <div class="flex gap-2">
                                            <button
                                                type="button"
                                                x-on:click="showConfirm = false"
                                                class="flex-1 text-center text-[10px] font-bold uppercase tracking-widest py-2 bg-gray-100 text-gray-600 rounded-[4px] hover:bg-gray-200 transition-colors cursor-pointer"
                                            >
                                                Batal
                                            </button>
                                            <button
                                                type="button"
                                                x-on:click="$wire.subscribePlan(confirmPlan, confirmBilling); showConfirm = false"
                                                class="flex-1 text-center text-[10px] font-bold uppercase tracking-widest py-2 bg-gray-900 text-white rounded-[4px] hover:bg-gray-700 transition-colors cursor-pointer"
                                            >
                                                Ya, Ganti
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

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
