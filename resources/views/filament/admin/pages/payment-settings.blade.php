<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Midtrans Configuration">
            <div class="p-4">
                <p class="text-sm text-gray-600 mb-4">
                    Midtrans payment gateway settings are managed via the <code>.env</code> file on the server.
                    The values below are read-only and shown for reference.
                </p>
                {{ $this->form }}
            </div>
        </x-filament::section>

        <x-filament::section heading="Server Setup">
            <div class="p-4">
                <p class="text-sm text-gray-600 mb-4">
                    To update Midtrans settings, edit the <code>.env</code> file and update these values:
                </p>
                <div class="bg-gray-100 p-4 rounded text-xs font-mono">
                    MIDTRANS_MERCHANT_ID={{ config('midtrans.merchant_id') ?? '(not set)' }}<br>
                    MIDTRANS_CLIENT_KEY={{ config('midtrans.client_key') ?? '(not set)' }}<br>
                    MIDTRANS_SERVER_KEY={{ config('midtrans.server_key') ?? '(not set)' }}<br>
                    MIDTRANS_ENVIRONMENT={{ config('midtrans.environment') ?? 'sandbox' }}<br>
                    MIDTRANS_WEBHOOK_IPS={{ config('midtrans.webhook_ip_whitelist') ? implode(',', config('midtrans.webhook_ip_whitelist')) : '(not set)' }}
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
