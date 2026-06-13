@extends('member-portal.layouts.app')

@section('title', __('My Vouchers'))

@section('content')
<h2 class="text-xl font-bold text-gray-800 mb-4">{{ __('My Vouchers') }}</h2>

<div class="bg-white rounded-lg shadow">
    <div class="p-5">
        @if ($vouchers->isEmpty())
            <p class="text-gray-500 text-sm">{{ __('No vouchers available.') }}</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($vouchers as $voucher)
                <div class="border border-gray-200 rounded-lg p-4 @if($voucher->member_id) border-indigo-300 bg-indigo-50 @endif">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-gray-400 uppercase">{{ $voucher->member_id ? __('Personal') : __('Global') }}</span>
                        <span class="text-xs @if($voucher->expired->isPast()) text-red-500 @else text-green-600 @endif">
                            {{ __('Expires') }} {{ $voucher->expired->format('d M Y') }}
                        </span>
                    </div>
                    <h3 class="font-semibold text-gray-800">{{ $voucher->name }}</h3>
                    <p class="text-sm text-gray-500 font-mono">{{ $voucher->code }}</p>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-lg font-bold text-indigo-600">
                            @if($voucher->type === 'percentage')
                                {{ $voucher->nominal }}%
                            @else
                                {{ number_format($voucher->nominal) }}
                            @endif
                        </span>
                        <span class="text-xs text-gray-500">
                            {{ $voucher->type === 'percentage' ? '' : 'off' }}
                        </span>
                    </div>
                    @if($voucher->minimal_buying > 0)
                        <p class="text-xs text-gray-400 mt-1">Min. purchase: {{ number_format($voucher->minimal_buying) }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            <div class="mt-4">{{ $vouchers->links() }}</div>
        @endif
    </div>
</div>
@endsection
