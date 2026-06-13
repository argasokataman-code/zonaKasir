<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Environment Status --}}
        <x-filament::section heading="Environment Status">
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="p-4 rounded-lg border">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Environment</div>
                        @php $env = config('midtrans.environment', 'sandbox'); @endphp
                        <div class="mt-1 flex items-center">
                            <span class="w-2 h-2 rounded-full {{ $env === 'production' ? 'bg-green-500' : 'bg-yellow-500' }} mr-2"></span>
                            <span class="font-semibold text-lg {{ $env === 'production' ? 'text-green-700' : 'text-yellow-700' }}">
                                {{ $env === 'production' ? 'Production' : 'Sandbox' }}
                            </span>
                        </div>
                    </div>
                    <div class="p-4 rounded-lg border">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Merchant ID</div>
                        <div class="mt-1 font-mono text-lg font-semibold">
                            {{ config('midtrans.merchant_id') ?? '—' }}
                        </div>
                    </div>
                    <div class="p-4 rounded-lg border">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Server Key</div>
                        <div class="mt-1 font-mono text-lg">
                            @if (config('midtrans.server_key'))
                                <span class="text-green-600">✓ Configured</span>
                            @else
                                <span class="text-red-500">✗ Not set</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Webhook URL --}}
        <x-filament::section heading="Webhook Configuration">
            <div class="p-4 space-y-4">
                <p class="text-sm text-gray-600">
                    Set these URLs in your Midtrans Dashboard: 
                    <a href="https://dashboard.sandbox.midtrans.com" target="_blank" rel="noopener noreferrer" class="text-primary-600 underline">
                        Settings → Configuration
                    </a>
                </p>
                <div class="bg-gray-50 p-4 rounded border">
                    <div class="text-sm font-medium text-gray-700">Payment Notification URL</div>
                    <div class="mt-1 flex items-center gap-2">
                        <code class="flex-1 text-xs bg-white p-2 rounded border font-mono break-all">
                            {{ url('/api/webhooks/midtrans') }}
                        </code>
                        <button onclick="navigator.clipboard.writeText('{{ url('/api/webhooks/midtrans') }}')"
                                class="text-xs px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">
                            Copy
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 p-3 rounded border">
                        <div class="text-xs font-medium text-gray-700">Finish Redirect URL</div>
                        <code class="mt-1 block text-xs font-mono break-all">{{ url('/member') }}</code>
                    </div>
                    <div class="bg-gray-50 p-3 rounded border">
                        <div class="text-xs font-medium text-gray-700">Unfinish Redirect URL</div>
                        <code class="mt-1 block text-xs font-mono break-all">{{ url('/member') }}</code>
                    </div>
                    <div class="bg-gray-50 p-3 rounded border">
                        <div class="text-xs font-medium text-gray-700">Error Redirect URL</div>
                        <code class="mt-1 block text-xs font-mono break-all">{{ url('/member') }}</code>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- API Keys --}}
        <x-filament::section heading="API Keys">
            <div class="p-4 space-y-4">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-800">
                    ⚠️ These keys grant access to your Midtrans account. Keep them confidential.
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <div class="text-sm font-medium text-gray-700">Merchant ID</div>
                        <div class="mt-1 font-mono text-sm bg-gray-50 p-2 rounded border">
                            {{ config('midtrans.merchant_id') ?? '(not set)' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-700">Client Key</div>
                        <div class="mt-1 font-mono text-sm bg-gray-50 p-2 rounded border">
                            {{ config('midtrans.client_key') ? substr(config('midtrans.client_key'), 0, 10) . '...' : '(not set)' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-700">Server Key</div>
                        <div class="mt-1 font-mono text-sm bg-gray-50 p-2 rounded border">
                            {{ config('midtrans.server_key') ? substr(config('midtrans.server_key'), 0, 10) . '...' : '(not set)' }}
                        </div>
                    </div>
                </div>
                <div class="border-t pt-4">
                    <p class="text-sm text-gray-600">
                        To update keys, SSH into the server and edit <code class="bg-gray-100 px-1 rounded">.env</code>,
                        then run <code class="bg-gray-100 px-1 rounded">php artisan config:cache</code>.
                    </p>
                </div>
            </div>
        </x-filament::section>

        {{-- Webhook IPs --}}
        <x-filament::section heading="Webhook IP Whitelist">
            <div class="p-4">
                <p class="text-sm text-gray-600 mb-3">
                    Midtrans sends payment notifications from these IPs. Ensure they are whitelisted on your server firewall.
                </p>
                <div class="bg-gray-50 p-3 rounded border">
                    @php $ips = config('midtrans.webhook_ip_whitelist', []); @endphp
                    @if (!empty($ips) && is_array($ips))
                        @foreach ($ips as $ip)
                            <code class="inline-block bg-white px-2 py-1 rounded border text-xs font-mono mr-2 mb-1">{{ $ip }}</code>
                        @endforeach
                    @else
                        <span class="text-gray-500 text-sm">No IPs configured</span>
                    @endif
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Divider --}}
    <div class="relative">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300"></div>
        </div>
        <div class="relative flex justify-center">
            <span class="bg-white px-3 text-sm font-medium text-gray-500">Payment Out (Disbursement)</span>
        </div>
    </div>

    <div class="space-y-6">
        {{-- Flip Status --}}
        <x-filament::section heading="Flip Status">
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="p-4 rounded-lg border">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Base URL</div>
                        <div class="mt-1 font-mono text-sm font-semibold">
                            {{ config('flip.base_url') ?? '—' }}
                        </div>
                    </div>
                    <div class="p-4 rounded-lg border">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Secret Key</div>
                        <div class="mt-1 font-mono text-lg">
                            @if (config('flip.secret_key'))
                                <span class="text-green-600">✓ Configured</span>
                            @else
                                <span class="text-red-500">✗ Not set</span>
                            @endif
                        </div>
                    </div>
                    <div class="p-4 rounded-lg border">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Webhook Token</div>
                        <div class="mt-1 font-mono text-lg">
                            @if (config('flip.webhook_token'))
                                <span class="text-green-600">✓ Configured</span>
                            @else
                                <span class="text-red-500">✗ Not set</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Flip Webhook --}}
        <x-filament::section heading="Flip Webhook Configuration">
            <div class="p-4 space-y-4">
                <p class="text-sm text-gray-600">
                    Set this URL in your Flip Dashboard:
                    <a href="https://big.flip.id" target="_blank" rel="noopener noreferrer" class="text-primary-600 underline">
                        Settings → Webhook
                    </a>
                </p>
                <div class="bg-gray-50 p-4 rounded border">
                    <div class="text-sm font-medium text-gray-700">Disbursement Webhook URL</div>
                    <div class="mt-1 flex items-center gap-2">
                        <code class="flex-1 text-xs bg-white p-2 rounded border font-mono break-all">
                            {{ url('/api/webhooks/flip') }}
                        </code>
                        <button onclick="navigator.clipboard.writeText('{{ url('/api/webhooks/flip') }}')"
                                class="text-xs px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">
                            Copy
                        </button>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Flip API Keys --}}
        <x-filament::section heading="Flip API Keys">
            <div class="p-4 space-y-4">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-800">
                    ⚠️ These keys grant access to your Flip account and can disburse funds. Keep them confidential.
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm font-medium text-gray-700">Secret Key</div>
                        <div class="mt-1 font-mono text-sm bg-gray-50 p-2 rounded border">
                            {{ config('flip.secret_key') ? substr(config('flip.secret_key'), 0, 10) . '...' : '(not set)' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-700">Webhook Token</div>
                        <div class="mt-1 font-mono text-sm bg-gray-50 p-2 rounded border">
                            {{ config('flip.webhook_token') ? substr(config('flip.webhook_token'), 0, 10) . '...' : '(not set)' }}
                        </div>
                    </div>
                </div>
                <div class="border-t pt-4">
                    <p class="text-sm text-gray-600">
                        To update keys, SSH into the server and edit <code class="bg-gray-100 px-1 rounded">.env</code>,
                        then run <code class="bg-gray-100 px-1 rounded">php artisan config:cache</code>.
                    </p>
                </div>
            </div>
        </x-filament::section>

        {{-- Withdrawal Thresholds --}}
        <x-filament::section heading="Withdrawal Approval Thresholds">
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 rounded-lg border bg-gray-50">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Auto Approve</div>
                        <div class="mt-1 font-semibold text-lg text-green-700">
                            &lt; Rp {{ number_format(config('flip.withdrawal_approval.auto_approve_max', 5000000), 0, ',', '.') }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">No approval required</div>
                    </div>
                    <div class="p-4 rounded-lg border bg-gray-50">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Single Admin</div>
                        <div class="mt-1 font-semibold text-lg text-yellow-700">
                            Rp {{ number_format(config('flip.withdrawal_approval.auto_approve_max', 5000000) + 1, 0, ',', '.') }}
                            &ndash; Rp {{ number_format(config('flip.withdrawal_approval.single_admin_max', 25000000), 0, ',', '.') }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Requires 1 admin approval</div>
                    </div>
                    <div class="p-4 rounded-lg border bg-gray-50">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Multi Admin</div>
                        <div class="mt-1 font-semibold text-lg text-red-700">
                            &gt; Rp {{ number_format(config('flip.withdrawal_approval.single_admin_max', 25000000), 0, ',', '.') }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Requires 2 admin approvals</div>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-4">
                    Thresholds configured via
                    <code class="bg-gray-100 px-1 rounded">FLIP_WITHDRAWAL_AUTO_APPROVE_MAX</code> and
                    <code class="bg-gray-100 px-1 rounded">FLIP_WITHDRAWAL_SINGLE_ADMIN_MAX</code>
                    in <code class="bg-gray-100 px-1 rounded">.env</code>.
                </p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
