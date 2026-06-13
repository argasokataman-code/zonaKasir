@extends('member-portal.layouts.app')

@section('title', __('Dashboard'))

@section('content')
<h2 class="text-xl font-bold text-gray-800 mb-6">{{ __('Welcome, :name', ['name' => $member->name]) }}</h2>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-gray-500 text-sm">{{ __('Loyalty Points') }}</p>
        <p class="text-2xl font-bold text-indigo-600">{{ number_format($member->available_points) }}</p>
        <p class="text-xs text-gray-400 mt-1">1 point = 1 {{ $currency }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-gray-500 text-sm">{{ __('Wallet Balance') }}</p>
        <p class="text-2xl font-bold text-green-600">{{ number_format($member->wallet_balance) }} {{ $currency }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-gray-500 text-sm">{{ __('Total Purchases') }}</p>
        <p class="text-2xl font-bold text-gray-800">{{ $member->sellings()->count() }}</p>
    </div>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="px-5 py-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">{{ __('Recent Purchases') }}</h3>
    </div>
    <div class="p-5">
        @if ($recentPurchases->isEmpty())
            <p class="text-gray-500 text-sm">{{ __('No purchases yet.') }}</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500">
                        <th class="pb-2">{{ __('Invoice') }}</th>
                        <th class="pb-2">{{ __('Date') }}</th>
                        <th class="pb-2 text-right">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($recentPurchases as $selling)
                    <tr>
                        <td class="py-2 font-mono text-gray-700">#{{ $selling->code }}</td>
                        <td class="py-2 text-gray-600">{{ $selling->created_at->format('d M Y H:i') }}</td>
                        <td class="py-2 text-right font-medium">{{ number_format($selling->grand_total_price) }} {{ $currency }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
