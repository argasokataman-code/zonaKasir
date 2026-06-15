<x-filament-panels::page>
    <div class="space-y-6">
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
                            <option value="tenant_request">Tenant Request</option>
                        </select>
                    </div>
                </div>

                @if (empty($withdrawals))
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
                                @foreach ($withdrawals as $w)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-2">
                                            <x-filament::badge color="gray">Request</x-filament::badge>
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
