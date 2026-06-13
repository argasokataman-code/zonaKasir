@extends('member-portal.layouts.app')

@section('title', __('Wallet'))

@section('content')
<div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-bold text-gray-800">{{ __('Wallet') }}</h2>
    <div class="bg-green-50 border border-green-200 rounded-lg px-4 py-2">
        <span class="text-sm text-green-600">{{ __('Balance') }}:</span>
        <span class="font-bold text-green-700 ml-1">{{ number_format($member->wallet_balance) }} {{ $currency }}</span>
    </div>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="px-5 py-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">{{ __('Transaction History') }}</h3>
    </div>
    <div class="p-5">
        @if ($transactions->isEmpty())
            <p class="text-gray-500 text-sm">{{ __('No transactions yet.') }}</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500">
                        <th class="pb-2">{{ __('Date') }}</th>
                        <th class="pb-2">{{ __('Type') }}</th>
                        <th class="pb-2 text-right">{{ __('Amount') }}</th>
                        <th class="pb-2 text-right">{{ __('Balance After') }}</th>
                        <th class="pb-2">{{ __('Note') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($transactions as $tx)
                    <tr>
                        <td class="py-2 text-gray-600">{{ $tx->created_at->format('d M Y H:i') }}</td>
                        <td class="py-2">
                            <span class="px-2 py-0.5 rounded text-xs font-medium
                                @if($tx->type === 'top_up') bg-green-100 text-green-700
                                @elseif($tx->type === 'payment') bg-red-100 text-red-700
                                @else bg-blue-100 text-blue-700 @endif">
                                {{ ucfirst(str_replace('_', ' ', $tx->type)) }}
                            </span>
                        </td>
                        <td class="py-2 text-right font-medium">{{ number_format($tx->amount) }} {{ $currency }}</td>
                        <td class="py-2 text-right">{{ number_format($tx->balance_after) }} {{ $currency }}</td>
                        <td class="py-2 text-gray-500">{{ $tx->note }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4">{{ $transactions->links() }}</div>
        @endif
    </div>
</div>
@endsection
