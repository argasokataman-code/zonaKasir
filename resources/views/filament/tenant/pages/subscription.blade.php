<x-filament-panels::page>
    <div class="relative overflow-hidden h-full">
    <div class="absolute inset-0 parallax-bg"></div>
    <div class="relative z-10">
    {{-- Back navigation --}}
    <div class="mb-4">
        <a href="{{ url('/member') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Back
        </a>
    </div>

    {{-- On-Premise Dashboard --}}
    @if(config('app.on_premise'))
    @php
        $subscription = app(\App\Filament\Tenant\Pages\ManageSubscription::class);
        $license = $subscription->getOnpremLicense();
        $server = $subscription->getServerHealth();
        $service = $subscription->getServiceStatus();
        $support = $subscription->getSupportInfo();
    @endphp

    {{-- License Card --}}
    <div class="mb-6">
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4 text-center">{{ __('License') }}</h2>
        <div class="bg-white rounded-[6px] shadow-md flex flex-col relative border-2 border-gray-900 w-full sm:w-[320px] sm:min-w-[320px] mx-auto">
            <div class="absolute top-0 left-0 bg-gray-900 text-white text-[8px] font-mono font-bold uppercase tracking-widest px-3.5 py-1.5 rounded-bl-[4px] rounded-tr-[5px]">
                {{ __('On-Premise') }}
            </div>
            <div class="p-5 pt-10 flex flex-col h-full">
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">
                        {{ ($license['type'] ?? 'managed') === 'pro' ? __('Full Access') : __('Managed') }}
                    </span>
                    <h3 class="font-sans font-bold text-base text-gray-900">{{ $license['customer'] ?? 'Licensed' }}</h3>
                </div>
                <div class="py-3 my-3 border-y border-gray-100">
                    <div class="flex items-center gap-2">
                        @if(($license['status'] ?? '') === 'active')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                {{ __('Active') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                {{ __('Invalid') }}
                            </span>
                        @endif
                        <span class="text-[9px] text-gray-500 font-bold uppercase tracking-wider">{{ __('Lifetime') }}</span>
                    </div>
                </div>
                <div class="text-[10px] text-gray-400 font-semibold mb-2 space-y-1">
                    <div class="flex justify-between">
                        <span>{{ __('Domain') }}</span>
                        <span class="text-gray-600">{{ $license['domain'] ?? 'localhost' }}</span>
                    </div>
                    @if($license['issued_at'] ?? null)
                    <div class="flex justify-between">
                        <span>{{ __('Issued') }}</span>
                        <span class="text-gray-600">{{ $license['issued_at'] }}</span>
                    </div>
                    @endif
                    @if($license['expires_at'] ?? null)
                    <div class="flex justify-between">
                        <span>{{ __('Expires') }}</span>
                        <span class="text-gray-600">{{ $license['expires_at'] }}</span>
                    </div>
                    @endif
                </div>
                <div class="text-[10px] text-gray-400 font-semibold border-t border-gray-100 pt-2">
                    @foreach($license['features'] ?? [] as $feature)
                    <div class="flex items-center gap-2 py-0.5">
                        <svg class="w-3 h-3 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-gray-600">{{ $feature }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Server Health + Service Status Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        {{-- Server Health --}}
        <div class="bg-white rounded-[6px] shadow-sm border border-[#E5E5E1] p-5" wire:poll.30s>
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">{{ __('Server Health') }}</h3>

            {{-- Disk Usage --}}
            <div class="mb-3">
                <div class="flex justify-between text-[10px] font-semibold mb-1">
                    <span class="text-gray-500">{{ __('Disk') }}</span>
                    <span class="{{ $server['disk_percent'] > 80 ? 'text-red-600' : 'text-gray-600' }}">{{ $server['disk_used'] }}GB / {{ $server['disk_total'] }}GB ({{ $server['disk_percent'] }}%)</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="{{ $server['disk_percent'] > 80 ? 'bg-red-500' : ($server['disk_percent'] > 60 ? 'bg-yellow-500' : 'bg-emerald-500') }} h-1.5 rounded-full transition-all" style="width: {{ $server['disk_percent'] }}%"></div>
                </div>
            </div>

            {{-- Memory Usage --}}
            <div class="mb-3">
                <div class="flex justify-between text-[10px] font-semibold mb-1">
                    <span class="text-gray-500">{{ __('Memory') }}</span>
                    <span class="{{ $server['memory_percent'] > 80 ? 'text-red-600' : 'text-gray-600' }}">{{ $server['memory_used'] }}MB / {{ $server['memory_limit'] }}MB ({{ $server['memory_percent'] }}%)</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="{{ $server['memory_percent'] > 80 ? 'bg-red-500' : ($server['memory_percent'] > 60 ? 'bg-yellow-500' : 'bg-emerald-500') }} h-1.5 rounded-full transition-all" style="width: {{ $server['memory_percent'] }}%"></div>
                </div>
            </div>

            {{-- Load Average --}}
            <div class="flex justify-between text-[10px] font-semibold mb-2">
                <span class="text-gray-500">{{ __('Load Average') }}</span>
                <span class="text-gray-600">{{ number_format($server['load_avg']['1min'], 1) }} / {{ number_format($server['load_avg']['5min'], 1) }} / {{ number_format($server['load_avg']['15min'], 1) }}</span>
            </div>

            {{-- Uptime + PHP --}}
            <div class="flex justify-between text-[10px] font-semibold mb-2">
                <span class="text-gray-500">{{ __('Uptime') }}</span>
                <span class="text-gray-600">{{ $server['uptime_hours'] ? number_format($server['uptime_hours'], 0) . 'h' : '-' }}</span>
            </div>
            <div class="flex justify-between text-[10px] font-semibold mb-2">
                <span class="text-gray-500">{{ __('PHP') }}</span>
                <span class="text-gray-600">{{ $server['php_version'] }}</span>
            </div>

            {{-- Database --}}
            <div class="flex justify-between text-[10px] font-semibold">
                <span class="text-gray-500">{{ __('Database') }}</span>
                @if($server['database'] === 'connected')
                    <span class="inline-flex items-center gap-1 text-emerald-600">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        {{ __('Connected') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-red-600">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                        {{ __('Error') }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Service Status --}}
        <div class="bg-white rounded-[6px] shadow-sm border border-[#E5E5E1] p-5">
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">{{ __('Service Status') }}</h3>

            <div class="space-y-3">
                <div class="flex justify-between text-[10px] font-semibold">
                    <span class="text-gray-500">{{ __('App Version') }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700">v{{ $service['app_version'] }}</span>
                </div>

                <div class="border-t border-gray-100 pt-3">
                    <div class="flex justify-between text-[10px] font-semibold mb-1">
                        <span class="text-gray-500">{{ __('Last Update') }}</span>
                        <span class="text-gray-600">{{ $service['last_update'] ?? '-' }}</span>
                    </div>
                    @if($service['last_update_at'] ?? null)
                    <div class="text-right text-[9px] text-gray-400">{{ $service['last_update_at'] }}</div>
                    @endif
                </div>

                <div class="border-t border-gray-100 pt-3">
                    <div class="flex justify-between text-[10px] font-semibold mb-1">
                        <span class="text-gray-500">{{ __('Last Backup') }}</span>
                        @if($service['last_backup'] ?? null)
                            <span class="text-gray-600">{{ $service['last_backup'] }}</span>
                        @else
                            <span class="text-yellow-600">{{ __('Never') }}</span>
                        @endif
                    </div>
                    @if($service['last_backup_at'] ?? null)
                    <div class="text-right text-[9px] text-gray-400">{{ $service['last_backup_at'] }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Support Info --}}
    <div class="bg-white rounded-[6px] shadow-sm border border-[#E5E5E1] p-5 mb-6">
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">{{ __('Support') }}</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">{{ __('Email') }}</span>
                <a href="mailto:{{ $support['email'] }}" class="text-xs font-semibold text-gray-900 hover:text-gray-600">{{ $support['email'] }}</a>
            </div>
            <div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">{{ __('Phone') }}</span>
                <span class="text-xs font-semibold text-gray-900">{{ $support['phone'] }}</span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">{{ __('SLA') }}</span>
                <span class="text-xs font-semibold text-gray-900">{{ $support['sla'] }}</span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">{{ __('Docs') }}</span>
                <a href="{{ $support['docs_url'] }}" target="_blank" class="text-xs font-semibold text-gray-900 hover:text-gray-600">{{ __('Documentation') }}</a>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <button
            type="button"
            wire:click="requestSupport"
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-900 text-white text-[10px] font-bold uppercase tracking-widest rounded-[4px] hover:bg-gray-700 transition-colors cursor-pointer"
        >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
            {{ __('Request Support') }}
        </button>
        <a
            href="{{ $support['docs_url'] }}"
            target="_blank"
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 text-gray-700 text-[10px] font-bold uppercase tracking-widest rounded-[4px] hover:bg-gray-50 transition-colors"
        >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
            {{ __('Documentation') }}
        </a>
    </div>

    @else

    @php
        $current = app(\App\Filament\Tenant\Pages\ManageSubscription::class)->getCurrentPlan();
        $plans = app(\App\Filament\Tenant\Pages\ManageSubscription::class)->getAvailablePlans();
    @endphp

    @if($snapRedirectUrl && !$showPaymentSuccess)
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
          {{ __('Pay Now') }}
        </a>
        <p class="text-sm text-blue-600 mt-3">{{ __('If not redirected, click the button above.') }}</p>
    </div>
    @endif

    @if($showPaymentSuccess)
    <div
      x-data
      x-init="$nextTick(() => $dispatch('open-modal', { id: 'payment-success-modal' }))"
    ></div>

    <x-filament::modal id="payment-success-modal" :close-by-clicking-away="false" :close-by-escaping="false" width="md">
        <div class="flex flex-col items-center py-4">
            <x-heroicon-o-check-circle style="color: rgb(34 197 94); width: 100px" />
            <h3 class="text-xl font-bold text-gray-900 mt-4">{{ __('Payment Successful') }}</h3>
            <p class="text-sm text-gray-500 mt-2 text-center">{{ __('Your subscription is now active. You can start using the application.') }}</p>
        </div>
        <x-slot name="footer">
            <x-filament::button tag="a" href="{{ url('/member') }}" class="w-full">
                {{ __('Go to Dashboard') }}
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
    @endif

    @if($current)
    <div class="mb-6">
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4 text-center">{{ __('Current Plan') }}</h2>
        <div class="bg-white rounded-[6px] shadow-md flex flex-col relative border-2 border-gray-900 w-full sm:w-[280px] sm:min-w-[280px] mx-auto">
            <div class="absolute top-0 left-0 bg-gray-900 text-white text-[8px] font-mono font-bold uppercase tracking-widest px-3.5 py-1.5 rounded-bl-[4px] rounded-tr-[5px]">
                @if(config('app.on_premise'))
                    {{ __('Licensed') }}
                @else
                    {{ __('Active') }}
                    @if($current['is_on_trial'])
                        {{ __('Trial') }}
                    @endif
                @endif
            </div>
            <div class="p-5 pt-10 flex flex-col h-full">
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">
                        {{ $current['max_stores'] > 10 ? __('Enterprise') : ($current['max_stores'] > 1 ? __('Business') : __('Starter')) }}
                    </span>
                    <h3 class="font-sans font-bold text-base text-gray-900">{{ $current['name'] }}</h3>
                </div>

                <div class="py-3 my-3 border-y border-gray-100">
                    @if($current['price_monthly'] ?? 0 > 0)
                        @if($current['billing_cycle'] === 'yearly')
                            <span class="font-mono text-2xl font-black text-gray-900">Rp {{ number_format($current['price_yearly'] ?? $current['price_monthly'], 0, ',', '.') }}</span>
                            <span class="text-[9px] text-gray-500 font-bold block uppercase tracking-wider mt-0.5">{{ __('Per Year') }}</span>
                            <span class="text-[9px] text-gray-400 block mt-0.5">Rp {{ number_format($current['price_monthly'], 0, ',', '.') }}/{{ __('month') }}</span>
                        @else
                            <span class="font-mono text-2xl font-black text-gray-900">Rp {{ number_format($current['price_monthly'], 0, ',', '.') }}</span>
                            <span class="text-[9px] text-gray-500 font-bold block uppercase tracking-wider mt-0.5">{{ __('Per Month') }}</span>
                            @if($current['price_yearly'] ?? false)
                            <span class="text-[9px] text-gray-400 block mt-0.5">Rp {{ number_format($current['price_yearly'], 0, ',', '.') }}/{{ __('year') }}</span>
                            @endif
                        @endif
                    @else
                        <span class="font-mono text-2xl font-black text-gray-900">{{ __('Free') }}</span>
                        <span class="text-[9px] text-gray-500 font-bold block uppercase tracking-wider mt-0.5">{{ __('Forever') }}</span>
                    @endif
                </div>

                <div class="text-[10px] text-gray-400 font-semibold mb-2">
                    {{ $current['max_stores'] }} {{ __('outlets') }} / {{ $current['max_users'] }} {{ __('users') }}
                </div>

                @if(!empty($current['features']))
                <div class="text-[10px] text-gray-400 font-semibold mb-2 border-t border-gray-100 pt-2">
                    @foreach($current['features'] as $feature)
                    <div class="flex items-center gap-2 py-0.5">
                        <svg class="w-3 h-3 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-gray-600">{{ is_string($feature) ? $feature : (is_string(array_key_first((array) $feature)) ? array_key_first((array) $feature) : $feature) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if(!config('app.on_premise'))
    <div class="mb-6 flex flex-col items-center">
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">{{ __('Available Plans') }}</h2>
        <div class="flex flex-col gap-4 pb-2 sm:flex-row sm:flex-nowrap sm:overflow-x-auto sm:scrollbar-thin sm:justify-center sm:gap-6">
            @foreach($plans as $plan)
            <div
                class="bg-white rounded-[6px] shadow-sm flex flex-col relative border @if($current && $current['id'] === $plan['id']) border-2 border-gray-900 shadow-md @else border-[#E5E5E1] @endif w-full sm:flex-shrink-0 sm:w-[280px] sm:min-w-[280px]"
            >
                    @if(($plan['is_popular'] ?? false) && $plan['price_monthly'] > 0)
                    <div class="absolute top-0 right-0 bg-gray-900 text-white text-[8px] font-mono font-bold uppercase tracking-widest px-3.5 py-1.5 rounded-bl-[4px] rounded-tr-[5px]">
                        {{ __('Popular') }}
                    </div>
                    @endif

                    <div class="p-5 flex flex-col h-full">
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">
                                {{ $plan['max_stores'] > 10 ? __('Enterprise') : ($plan['max_stores'] > 1 ? __('Business') : __('Starter')) }}
                            </span>
                            <h3 class="font-sans font-bold text-base text-gray-900">
                                {{ $plan['name'] }}
                            </h3>
                        </div>

                        <div class="py-3 my-3 border-y border-gray-100">
                            @if(($plan['is_on_premise'] ?? false))
                                <span class="font-mono text-xl font-black text-gray-900">{{ __('Custom') }}</span>
                                <span class="text-[9px] text-gray-500 font-bold block uppercase tracking-wider mt-0.5">{{ __('Self-Hosted') }}</span>
                            @elseif(($plan['price_monthly'] ?? 0) === 0)
                                <span class="font-mono text-2xl font-black text-gray-900">{{ __('Free') }}</span>
                                <span class="text-[9px] text-gray-500 font-bold block uppercase tracking-wider mt-0.5">{{ __('Forever') }}</span>
                            @else
                                <span class="font-mono text-2xl font-black text-gray-900">Rp {{ number_format($plan['price_monthly'], 0, ',', '.') }}</span>
                                <span class="text-[9px] text-gray-500 font-bold block uppercase tracking-wider mt-0.5">{{ __('Per Month') }}</span>
                                @if($plan['price_yearly'])
                                <span class="text-[9px] text-gray-400 block mt-0.5">Rp {{ number_format($plan['price_yearly'], 0, ',', '.') }}/{{ __('year') }}</span>
                                @endif
                            @endif
                        </div>

                        <div class="text-[10px] text-gray-400 font-semibold mb-2">
                            {{ $plan['max_stores'] }} {{ __('outlets') }} / {{ $plan['max_users'] }} {{ __('users') }}
                        </div>

                        @if(!empty($plan['features']))
                        <div x-data="{ open: false }">
                            <button
                                type="button"
                                x-on:click="open = !open"
                                class="w-full flex items-center justify-between text-[10px] font-bold text-gray-900 uppercase tracking-wider py-1.5 border-t border-gray-100 cursor-pointer hover:text-gray-600 transition-colors"
                            >
                                <span>{{ __('Features') }} ({{ count($plan['features']) }})</span>
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
                        </div>
                        @endif

                            <div class="mt-auto pt-3 border-t border-gray-100 relative" x-data="{ showBilling: false, showConfirm: false, confirmPlan: null, confirmBilling: '', confirmPlanName: '', selectedBilling: '' }">
                                @if($current && $current['id'] === $plan['id'])
                                    @if($current['is_on_trial'])
                                        <button
                                            type="button"
                                            x-on:click="if (selectedBilling) { $wire.subscribePlan({{ $plan['id'] }}, selectedBilling) } else { showBilling = !showBilling }"
                                            class="block w-full text-center text-[10px] font-bold uppercase tracking-widest py-2 bg-green-600 text-white rounded-[4px] hover:bg-green-500 transition-colors cursor-pointer"
                                        >
                                            <span x-show="!selectedBilling">{{ __('Pay Now') }}</span>
                                            <span x-show="selectedBilling === 'monthly'">{{ __('Pay Now') }} - {{ __('Monthly') }}</span>
                                            <span x-show="selectedBilling === 'yearly'">{{ __('Pay Now') }} - {{ __('Yearly') }}</span>
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
                                                <span>{{ __('Monthly') }}</span>
                                                <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                            @if(($plan['price_yearly'] ?? 0) > 0)
                                            <button
                                                type="button"
                                                x-on:click="selectedBilling = 'yearly'; showBilling = false"
                                                class="w-full text-left px-3 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 border-t border-gray-100 transition-colors cursor-pointer flex items-center justify-between"
                                            >
                                                <span>{{ __('Yearly') }}</span>
                                                <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                            @endif
                                        </div>
                                    @else
                                        <span class="block w-full text-center text-[10px] font-bold uppercase tracking-widest py-2 bg-gray-900 text-white rounded-[4px]">{{ __('Active Plan') }}</span>
                                    @endif
                            @elseif(($plan['price_monthly'] ?? 0) === 0)
                                <span class="block w-full text-center text-[10px] font-bold uppercase tracking-widest py-2 bg-gray-100 text-gray-500 rounded-[4px]">{{ __('Free') }}</span>
                            @else
                                <button
                                    type="button"
                                    x-on:click="showBilling = !showBilling"
                                    class="block w-full text-center text-[10px] font-bold uppercase tracking-widest py-2 bg-gray-900 text-white rounded-[4px] hover:bg-gray-700 transition-colors cursor-pointer"
                                >
                                    {{ $current ? __('Switch Plan') : __('Upgrade Now') }}
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
                                        x-on:click="showConfirm = true; confirmPlan = {{ $plan['id'] }}; confirmBilling = 'monthly'; confirmPlanName = '{{ $plan['name'] }}'; showBilling = false"
                                        class="w-full text-left px-3 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer flex items-center justify-between"
                                    >
                                        <span>{{ __('Monthly') }}</span>
                                        <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                    @if(($plan['price_yearly'] ?? 0) > 0)
                                    <button
                                        type="button"
                                        x-on:click="showConfirm = true; confirmPlan = {{ $plan['id'] }}; confirmBilling = 'yearly'; confirmPlanName = '{{ $plan['name'] }}'; showBilling = false"
                                        class="w-full text-left px-3 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 border-t border-gray-100 transition-colors cursor-pointer flex items-center justify-between"
                                    >
                                        <span>{{ __('Yearly') }}</span>
                                        <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                    @endif
                                    @else
                                    <button
                                        type="button"
                                        wire:click="subscribePlan({{ $plan['id'] }}, 'monthly')"
                                        class="w-full text-left px-3 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer flex items-center justify-between"
                                    >
                                        <span>{{ __('Monthly') }}</span>
                                        <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                    @if(($plan['price_yearly'] ?? 0) > 0)
                                    <button
                                        type="button"
                                        wire:click="subscribePlan({{ $plan['id'] }}, 'yearly')"
                                        class="w-full text-left px-3 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 border-t border-gray-100 transition-colors cursor-pointer flex items-center justify-between"
                                    >
                                        <span>{{ __('Yearly') }}</span>
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
                                        <h3 class="text-sm font-bold text-gray-900 mb-1">{{ __('Confirm Plan Change') }}</h3>
                                        <p class="text-xs text-gray-500 mb-1">
                                            {{ __('Your current plan is') }} <span class="font-bold text-gray-900">{{ $current['name'] ?? '—' }}</span>
                                            ({{ __(($current['billing_cycle'] ?? 'monthly') === 'yearly' ? 'Yearly' : 'Monthly') }}).
                                        </p>
                                        <p class="text-xs text-gray-500 mb-4">
                                            {{ __('Switch to') }} <span class="font-bold text-gray-900" x-text="confirmPlanName"></span>
                                            (<span x-text="confirmBilling === 'yearly' ? '{{ __("Yearly") }}' : '{{ __("Monthly") }}'"></span>)?
                                        </p>
                                        <div class="flex gap-2">
                                            <button
                                                type="button"
                                                x-on:click="showConfirm = false"
                                                class="flex-1 text-center text-[10px] font-bold uppercase tracking-widest py-2 bg-gray-100 text-gray-600 rounded-[4px] hover:bg-gray-200 transition-colors cursor-pointer"
                                            >
                                                {{ __('Cancel') }}
                                            </button>
                                            <button
                                                type="button"
                                                x-on:click="$wire.subscribePlan(confirmPlan, confirmBilling); showConfirm = false"
                                                class="flex-1 text-center text-[10px] font-bold uppercase tracking-widest py-2 bg-gray-900 text-white rounded-[4px] hover:bg-gray-700 transition-colors cursor-pointer"
                                            >
                                                {{ __('Yes, Switch') }}
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
    @endif
    </div>

    @if(!config('app.on_premise'))
    @php $invoices = app(\App\Filament\Tenant\Pages\ManageSubscription::class)->getInvoices(); @endphp
    @if(count($invoices) > 0)
    <div class="bg-white border border-[#E5E5E1] rounded-[6px] p-6 shadow-sm">
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">{{ __('Invoice History') }}</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left">
                        <th class="py-2 pr-4 font-bold text-gray-500 text-[10px] uppercase tracking-widest">{{ __('Invoice') }}</th>
                        <th class="py-2 pr-4 font-bold text-gray-500 text-[10px] uppercase tracking-widest">{{ __('Amount') }}</th>
                        <th class="py-2 pr-4 font-bold text-gray-500 text-[10px] uppercase tracking-widest">{{ __('Status') }}</th>
                        <th class="py-2 pr-4 font-bold text-gray-500 text-[10px] uppercase tracking-widest">{{ __('Date') }}</th>
                        <th class="py-2 font-bold text-gray-500 text-[10px] uppercase tracking-widest">{{ __('Payment') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $inv)
                    <tr class="border-b border-gray-100">
                        <td class="py-3 pr-4 font-mono text-xs text-gray-900">{{ $inv['number'] }}</td>
                        <td class="py-3 pr-4 font-medium text-sm">Rp {{ number_format($inv['amount'], 0, ',', '.') }}</td>
                        <td class="py-3 pr-4">
                            @if($inv['status'] === 'paid')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800">{{ __('Paid') }}</span>
                            @elseif($inv['status'] === 'pending')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-yellow-100 text-yellow-800">{{ __('Pending') }}</span>
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
    @endif
    @endif
    </div>

    <style>
        .parallax-bg {
            background: linear-gradient(to bottom, transparent 0%, rgba(244,244,242,0.6) 50%, #F4F4F2 100%), url('/images/landing/retail_hero_bg_1781378962689.jpg') center/cover no-repeat;
            opacity: 0.4;
            filter: grayscale(1) contrast(1.25);
            animation: slowZoom 20s ease-in-out infinite alternate;
            transform-origin: center;
        }
        @keyframes slowZoom {
            from { transform: scale(1) translate(0, 0); }
            to { transform: scale(1.08) translate(-2%, -1%); }
        }
    </style>
    </div>
</x-filament-panels::page>
