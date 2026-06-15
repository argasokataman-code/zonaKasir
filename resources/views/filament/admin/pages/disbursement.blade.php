<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Actions --}}
        <div class="flex justify-end gap-2">
            <x-filament::button wire:click="refresh" icon="heroicon-o-arrow-path" color="gray" wire:loading.attr="disabled">
                Refresh
            </x-filament::button>
            <x-filament::button wire:click="openTransferForm" icon="heroicon-o-arrow-up-tray">
                Transfer to Tenant
            </x-filament::button>
        </div>

        {{-- Last Transfer Status --}}
        @if ($lastTransferStatus)
            <div class="{{ $lastTransferStatus === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' }} border rounded-lg p-4 flex items-center gap-3">
                <x-filament::icon
                    :icon="$lastTransferStatus === 'success' ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'"
                    class="w-5 h-5 {{ $lastTransferStatus === 'success' ? 'text-green-600' : 'text-red-600' }}"
                />
                <div>
                    <div class="font-semibold">{{ $lastTransferStatus === 'success' ? 'Transfer Berhasil' : 'Transfer Gagal' }}</div>
                    <div class="text-sm">{{ $lastTransferMessage }}</div>
                </div>
                <button wire:click="$set('lastTransferStatus', null)" class="ml-auto text-gray-400 hover:text-gray-600">
                    <x-filament::icon icon="heroicon-o-x-mark" class="w-4 h-4" />
                </button>
            </div>
        @endif

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
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    {{-- Backdrop --}}
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeTransferForm"></div>

                    {{-- Modal Panel --}}
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-semibold text-gray-900" id="modal-title">
                                        {{ $showConfirmation ? 'Konfirmasi Transfer' : 'Transfer to Tenant' }}
                                    </h3>

                                    @if ($showConfirmation)
                                        {{-- Confirmation Content --}}
                                        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                                            <div class="space-y-2 text-sm">
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">Total Debit:</span>
                                                    <span class="font-semibold">Rp {{ number_format($transferAmount, 0, ',', '.') }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">Fee:</span>
                                                    <span class="font-semibold text-red-600">- Rp {{ number_format($calculatedFee, 0, ',', '.') }}</span>
                                                </div>
                                                <div class="flex justify-between border-t pt-2">
                                                    <span class="text-gray-600">Yang Diterima Tenant:</span>
                                                    <span class="font-bold text-green-600">Rp {{ number_format($calculatedNet, 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                            <div class="mt-4 text-sm">
                                                <div class="text-gray-600">Ke:</div>
                                                <div class="font-semibold">{{ $selectedTenantInfo['bank_account_name'] }}</div>
                                                <div class="text-gray-500">{{ $selectedTenantInfo['bank_name'] }} - {{ $selectedTenantInfo['bank_account_number'] }}</div>
                                            </div>
                                            <div class="mt-3 text-xs text-red-600 font-medium">
                                                ⚠️ Transfer ini TIDAK BISA dibatalkan setelah dikirim.
                                            </div>
                                        </div>
                                    @else
                                        {{-- Transfer Form Content --}}
                                        <div class="mt-4 space-y-4">
                                            {{-- Tenant Select --}}
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Tenant</label>
                                                <select wire:model="selectedTenantId" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    <option value="">Pilih tenant...</option>
                                                    @foreach ($tenants as $t)
                                                        @if ($t['has_bank'])
                                                            <option value="{{ $t['id'] }}">
                                                                {{ $t['name'] }} ({{ $t['shop_name'] }})
                                                            </option>
                                                        @else
                                                            <option value="{{ $t['id'] }}" disabled>
                                                                {{ $t['name'] }} ({{ $t['shop_name'] }}) - Tidak ada bank
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>

                                            {{-- Bank Info (ALWAYS visible, auto-filled) --}}
                                            <div class="p-3 bg-gray-50 rounded-lg text-sm">
                                                <div class="font-medium text-gray-700 mb-1">Bank Information (auto-filled)</div>
                                                @if ($selectedTenantInfo && $selectedTenantInfo['has_bank'])
                                                    <div class="grid grid-cols-3 gap-4">
                                                        <div>
                                                            <span class="text-gray-500">Bank:</span>
                                                            <span class="font-medium">{{ $selectedTenantInfo['bank_name'] }} ({{ $selectedTenantInfo['bank_code'] }})</span>
                                                        </div>
                                                        <div>
                                                            <span class="text-gray-500">Account:</span>
                                                            <span class="font-medium font-mono">{{ $selectedTenantInfo['bank_account_number'] }}</span>
                                                        </div>
                                                        <div>
                                                            <span class="text-gray-500">Name:</span>
                                                            <span class="font-medium">{{ $selectedTenantInfo['bank_account_name'] }}</span>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="text-gray-400 italic">Pilih tenant untuk melihat informasi bank</div>
                                                @endif
                                            </div>

                                            {{-- Amount Input --}}
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Nominal (total debit dari Flip)</label>
                                                <div class="flex items-center border border-gray-300 rounded-lg shadow-sm focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500">
                                                    <span class="pl-3 pr-1 py-2 text-gray-500 text-sm bg-gray-50 border-r border-gray-300 rounded-l-lg">Rp</span>
                                                    <input type="number" wire:model="transferAmount" min="50000" step="1000"
                                                        class="flex-1 border-0 py-2 px-3 text-sm focus:ring-0 focus:outline-none"
                                                        placeholder="100.000">
                                                </div>
                                                <div class="text-xs text-gray-400 mt-1">Min. Rp 50.000</div>
                                            </div>

                                            {{-- Fee Breakdown (ALWAYS visible) --}}
                                            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm">
                                                <div class="font-medium text-yellow-800 mb-1">Fee Breakdown</div>
                                                @if ($calculatedFee && $calculatedNet)
                                                    <div class="space-y-1">
                                                        <div class="flex justify-between">
                                                            <span>Total debit dari Flip:</span>
                                                            <span class="font-medium">Rp {{ number_format($transferAmount, 0, ',', '.') }}</span>
                                                        </div>
                                                        <div class="flex justify-between text-red-600">
                                                            <span>Fee Flip:</span>
                                                            <span class="font-medium">- Rp {{ number_format($calculatedFee, 0, ',', '.') }}</span>
                                                        </div>
                                                        <div class="flex justify-between border-t pt-1 mt-1 font-bold">
                                                            <span>Tenant terima (net):</span>
                                                            <span class="text-green-600">Rp {{ number_format($calculatedNet, 0, ',', '.') }}</span>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="text-gray-400 italic">Masukkan nominal untuk melihat rincian biaya</div>
                                                @endif
                                            </div>

                                            {{-- Internal Notes --}}
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Internal Notes (optional)</label>
                                                <textarea wire:model="transferNotes" rows="2"
                                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                    placeholder="Catatan untuk admin..."></textarea>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Modal Actions --}}
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                            @if ($showConfirmation)
                                <button type="button" wire:click="executeTransfer" wire:loading.attr="disabled"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                                    <span wire:loading.remove wire:target="executeTransfer">Ya, Transfer Sekarang</span>
                                    <span wire:loading wire:target="executeTransfer">Mengirim...</span>
                                </button>
                                <button type="button" wire:click="$set('showConfirmation', false)"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                    Kembali
                                </button>
                            @else
                                <button type="button" wire:click="showConfirmationDialog"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                                    {{ !$selectedTenantId || !$transferAmount || $transferAmount < 50000 ? 'disabled' : '' }}>
                                    @if ($calculatedNet)
                                        Transfer Rp {{ number_format($calculatedNet, 0, ',', '.') }}
                                    @else
                                        Konfirmasi Transfer
                                    @endif
                                </button>
                                <button type="button" wire:click="closeTransferForm"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                    Cancel
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tenant Bank Info --}}
        <x-filament::section heading="Tenant Bank Accounts" icon="heroicon-o-building-library">
            <div class="p-4">
                {{-- Search --}}
                <div class="mb-4">
                    <input type="text" wire:model="tenantSearch" placeholder="Search tenant, shop, bank name, or account number..."
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>

                @if (empty($filteredTenants))
                    <div class="text-center text-gray-500 py-4">
                        @if ($tenantSearch)
                            Tidak ada tenant yang cocok dengan pencarian "{{ $tenantSearch }}"
                        @else
                            No tenants found
                        @endif
                    </div>
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
                                @foreach ($filteredTenants as $t)
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

                    {{-- Pagination --}}
                    <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
                        <div>
                            Menampilkan {{ ($tenantPage - 1) * $tenantPerPage + 1 }} - {{ min($tenantPage * $tenantPerPage, $tenantTotalCount) }} dari {{ $tenantTotalCount }} tenant
                        </div>
                        <div class="flex gap-2">
                            <x-filament::button wire:click="tenantPrevPage" color="gray" size="sm" :disabled="$tenantPage <= 1">
                                ← Prev
                            </x-filament::button>
                            <span class="px-3 py-1">Halaman {{ $tenantPage }} / {{ ceil($tenantTotalCount / $tenantPerPage) }}</span>
                            <x-filament::button wire:click="tenantNextPage" color="gray" size="sm" :disabled="$tenantPage >= ceil($tenantTotalCount / $tenantPerPage)">
                                Next →
                            </x-filament::button>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Withdrawal & Disbursement History --}}
        <x-filament::section heading="Withdrawal History" icon="heroicon-o-clock">
            <div class="p-4">
                {{-- Search & Filters --}}
                <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-1">
                        <input type="text" wire:model="withdrawalSearch" placeholder="Search tenant, account, or Flip ID..."
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <select wire:model="withdrawalStatusFilter" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Semua Status</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div>
                        <select wire:model="withdrawalTypeFilter" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Semua Type</option>
                            <option value="admin_direct">Direct Transfer</option>
                            <option value="tenant_request">Tenant Request</option>
                        </select>
                    </div>
                </div>

                @if (empty($filteredWithdrawals))
                    <div class="text-center text-gray-500 py-4">
                        @if ($withdrawalSearch || $withdrawalStatusFilter || $withdrawalTypeFilter)
                            Tidak ada data yang cocok dengan filter
                        @else
                            No withdrawals found
                        @endif
                    </div>
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
                                    <th class="p-2">Status</th>
                                    <th class="p-2">Flip ID</th>
                                    <th class="p-2">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($filteredWithdrawals as $w)
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

                    {{-- Pagination --}}
                    <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
                        <div>
                            Menampilkan {{ ($withdrawalPage - 1) * $withdrawalPerPage + 1 }} - {{ min($withdrawalPage * $withdrawalPerPage, $withdrawalTotalCount) }} dari {{ $withdrawalTotalCount }} data
                        </div>
                        <div class="flex gap-2">
                            <x-filament::button wire:click="withdrawalPrevPage" color="gray" size="sm" :disabled="$withdrawalPage <= 1">
                                ← Prev
                            </x-filament::button>
                            <span class="px-3 py-1">Halaman {{ $withdrawalPage }} / {{ ceil($withdrawalTotalCount / $withdrawalPerPage) }}</span>
                            <x-filament::button wire:click="withdrawalNextPage" color="gray" size="sm" :disabled="$withdrawalPage >= ceil($withdrawalTotalCount / $withdrawalPerPage)">
                                Next →
                            </x-filament::button>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Flip Disbursement History --}}
        <x-filament::section heading="Flip Disbursement Data" icon="heroicon-o-banknotes">
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
