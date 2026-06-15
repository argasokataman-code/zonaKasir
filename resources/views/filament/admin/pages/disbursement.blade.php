<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-end">
            <x-filament::button wire:click="refresh" icon="heroicon-o-arrow-path" color="gray">
                Refresh
            </x-filament::button>
            <x-filament::button wire:click="openTransferForm" icon="heroicon-o-arrow-up-tray" class="ml-2">
                Transfer to Tenant
            </x-filament::button>
        </div>

        {{-- Flip Balance --}}
        <x-filament::section heading="Flip Balance" icon="heroicon-o-banknotes">
            <div class="p-4">
                @if ($balanceError)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-800">
                        ⚠️ {{ $balanceError }}
                    </div>
                @elseif ($flipBalance)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 rounded-lg border">
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Balance</div>
                            <div class="mt-1 font-semibold text-2xl">
                                Rp {{ number_format(($flipBalance['balance'] ?? 0), 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="p-4 rounded-lg border">
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Pending</div>
                            <div class="mt-1 font-semibold text-lg text-yellow-600">
                                Rp {{ number_format(($flipBalance['pending_balance'] ?? 0), 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="p-4 rounded-lg border">
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Currency</div>
                            <div class="mt-1 font-semibold text-lg">
                                {{ strtoupper($flipBalance['currency'] ?? 'IDR') }}
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-gray-500">Loading balance...</div>
                @endif
            </div>
        </x-filament::section>

        {{-- Transfer Form Modal --}}
        @if ($showTransferForm)
            <x-filament::section heading="Transfer to Tenant" icon="heroicon-o-arrow-up-tray">
                <div class="p-4">
                    @if ($showConfirmation)
                        {{-- Confirmation Dialog --}}
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <h3 class="font-semibold text-blue-800 mb-2">⚠️ Konfirmasi Transfer</h3>
                            <div class="space-y-2 text-sm">
                                <p><strong>Total Debit:</strong> Rp {{ number_format($transferAmount, 0, ',', '.') }}</p>
                                <p><strong>Fee:</strong> Rp {{ number_format($calculatedFee, 0, ',', '.') }}</p>
                                <p><strong>Yang Diterima Tenant:</strong> Rp {{ number_format($calculatedNet, 0, ',', '.') }}</p>
                                <p><strong>Ke:</strong> {{ $selectedTenantInfo['bank_account_name'] }}</p>
                                <p><strong>Bank:</strong> {{ $selectedTenantInfo['bank_name'] }} - {{ $selectedTenantInfo['bank_account_number'] }}</p>
                                <p class="text-red-600 font-medium mt-2">⚠️ Transfer ini TIDAK BISA dibatalkan setelah dikirim.</p>
                            </div>
                            <div class="flex gap-2 mt-4">
                                <x-filament::button wire:click="closeTransferForm" color="gray">
                                    Batal
                                </x-filament::button>
                                <x-filament::button wire:click="executeTransfer" color="danger">
                                    Ya, Transfer Sekarang
                                </x-filament::button>
                            </div>
                        </div>
                    @else
                        {{-- Transfer Form --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Tenant Select --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tenant</label>
                                <select wire:model="selectedTenantId" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Pilih tenant...</option>
                                    @foreach ($tenants as $t)
                                        <option value="{{ $t['id'] }}" {{ $t['has_bank'] ? '' : 'disabled' }}>
                                            {{ $t['name'] }} ({{ $t['shop_name'] }})
                                            @if (! $t['has_bank']) - Tidak ada bank@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Amount Input --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nominal (Total Debit dari Flip)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-gray-500">Rp</span>
                                    <input type="number" wire:model="transferAmount" min="50000" step="1000"
                                        class="w-full pl-10 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Min. Rp 50.000">
                                </div>
                            </div>
                        </div>

                        {{-- Bank Info (auto-filled) --}}
                        @if ($selectedTenantInfo && $selectedTenantInfo['has_bank'])
                            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                                <h4 class="font-medium text-gray-700 mb-2">Informasi Bank (Auto-filled)</h4>
                                <div class="grid grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Bank:</span>
                                        <span class="font-medium">{{ $selectedTenantInfo['bank_name'] }} ({{ $selectedTenantInfo['bank_code'] }})</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Rekening:</span>
                                        <span class="font-medium font-mono">{{ $selectedTenantInfo['bank_account_number'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Atas Nama:</span>
                                        <span class="font-medium">{{ $selectedTenantInfo['bank_account_name'] }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Fee Breakdown --}}
                        @if ($calculatedFee && $calculatedNet)
                            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <h4 class="font-medium text-yellow-800 mb-2">Rincian Biaya</h4>
                                <div class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <span>Total Debit dari Flip:</span>
                                        <span class="font-medium">Rp {{ number_format($transferAmount, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Biaya Transfer (Fee):</span>
                                        <span class="font-medium text-red-600">- Rp {{ number_format($calculatedFee, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between border-t pt-1 font-semibold">
                                        <span>Yang Diterima Tenant:</span>
                                        <span class="text-green-600">Rp {{ number_format($calculatedNet, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Internal Notes --}}
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Internal (opsional)</label>
                            <textarea wire:model="transferNotes" rows="2"
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Catatan untuk admin..."></textarea>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-2 mt-4">
                            <x-filament::button wire:click="closeTransferForm" color="gray">
                                Batal
                            </x-filament::button>
                            <x-filament::button wire:click="showConfirmationDialog" color="primary">
                                Konfirmasi Transfer
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif

        {{-- Tenant Bank Info --}}
        <x-filament::section heading="Tenant Bank Accounts">
            <div class="p-4">
                @if (empty($tenants))
                    <div class="text-center text-gray-500">No tenants found</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="p-2">Tenant</th>
                                    <th class="p-2">Shop</th>
                                    <th class="p-2">Bank</th>
                                    <th class="p-2">Account Name</th>
                                    <th class="p-2">Account Number</th>
                                    <th class="p-2">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tenants as $t)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-2 font-medium">{{ $t['name'] }}</td>
                                        <td class="p-2">{{ $t['shop_name'] }}</td>
                                        <td class="p-2">{{ $t['bank_name'] }} ({{ $t['bank_code'] }})</td>
                                        <td class="p-2">{{ $t['bank_account_name'] }}</td>
                                        <td class="p-2 font-mono">{{ $t['bank_account_number'] }}</td>
                                        <td class="p-2">
                                            @if ($t['has_bank'])
                                                <x-filament::badge color="success">Configured</x-filament::badge>
                                            @else
                                                <x-filament::badge color="danger">Missing</x-filament::badge>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Withdrawal & Disbursement History --}}
        <x-filament::section heading="Withdrawal History" icon="heroicon-o-clock">
            <div class="p-4">
                @if (empty($withdrawals))
                    <div class="text-center text-gray-500">No withdrawals found</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="p-2">Type</th>
                                    <th class="p-2">Tenant</th>
                                    <th class="p-2">Amount (Net)</th>
                                    <th class="p-2">Fee</th>
                                    <th class="p-2">Bank</th>
                                    <th class="p-2">Account</th>
                                    <th class="p-2">Status</th>
                                    <th class="p-2">Flip ID</th>
                                    <th class="p-2">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($withdrawals as $w)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-2">
                                            @if ($w['type'] === 'admin_direct')
                                                <x-filament::badge color="info">Direct</x-filament::badge>
                                            @else
                                                <x-filament::badge color="gray">Request</x-filament::badge>
                                            @endif
                                        </td>
                                        <td class="p-2 font-medium">{{ $w['tenant_name'] }}</td>
                                        <td class="p-2">Rp {{ number_format($w['amount'], 0, ',', '.') }}</td>
                                        <td class="p-2 text-red-600">
                                            @if ($w['fee_amount'] > 0)
                                                - Rp {{ number_format($w['fee_amount'], 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="p-2">{{ $w['bank_name'] }} ({{ $w['bank_code'] ?? '-' }})</td>
                                        <td class="p-2">{{ $w['bank_account_name'] }}<br><span class="text-xs text-gray-400">{{ $w['bank_account_number'] }}</span></td>
                                        <td class="p-2">
                                            @php
                                                $colors = [
                                                    'completed' => 'success',
                                                    'processing' => 'info',
                                                    'approved' => 'primary',
                                                    'pending' => 'warning',
                                                    'rejected' => 'danger',
                                                    'failed' => 'danger',
                                                ];
                                                $color = $colors[$w['status']] ?? 'gray';
                                            @endphp
                                            <x-filament::badge :color="$color">
                                                {{ $w['status'] }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="p-2 font-mono text-xs">{{ $w['disburse_id'] ?? '-' }}</td>
                                        <td class="p-2 whitespace-nowrap">{{ $w['created_at'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Flip Disbursement History --}}
        <x-filament::section heading="Flip Disbursement Data">
            <div class="p-4">
                @if (empty($flipDisbursements))
                    <div class="text-center text-gray-500">No data from Flip API</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="p-2">Flip ID</th>
                                    <th class="p-2">Bank</th>
                                    <th class="p-2">Account</th>
                                    <th class="p-2">Amount</th>
                                    <th class="p-2">Status</th>
                                    <th class="p-2">Remark</th>
                                    <th class="p-2">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($flipDisbursements as $d)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-2 font-mono text-xs">{{ $d['id'] ?? '-' }}</td>
                                        <td class="p-2">{{ $d['bank_code'] ?? '-' }}</td>
                                        <td class="p-2 font-mono">{{ $d['account_number'] ?? '-' }}</td>
                                        <td class="p-2">Rp {{ number_format(($d['amount'] ?? 0), 0, ',', '.') }}</td>
                                        <td class="p-2">
                                            @php
                                                $colors = ['DONE' => 'success', 'PENDING' => 'warning', 'CANCELLED' => 'danger', 'FAILED' => 'danger'];
                                                $color = $colors[$d['status']] ?? 'gray';
                                            @endphp
                                            <x-filament::badge :color="$color">{{ $d['status'] ?? '-' }}</x-filament::badge>
                                        </td>
                                        <td class="p-2 max-w-xs truncate">{{ $d['remark'] ?? '-' }}</td>
                                        <td class="p-2 whitespace-nowrap">{{ $d['created_at'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
