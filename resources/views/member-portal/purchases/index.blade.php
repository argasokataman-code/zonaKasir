@extends('member-portal.layouts.app')

@section('title', __('Purchase History'))

@section('content')
<h2 class="text-xl font-bold text-gray-800 mb-4">{{ __('Purchase History') }}</h2>

<div class="bg-white rounded-lg shadow">
    <div class="p-5">
        @if ($sellings->isEmpty())
            <p class="text-gray-500 text-sm">{{ __('No purchases yet.') }}</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500">
                        <th class="pb-2">{{ __('Invoice') }}</th>
                        <th class="pb-2">{{ __('Date') }}</th>
                        <th class="pb-2">{{ __('Items') }}</th>
                        <th class="pb-2 text-right">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($sellings as $selling)
                    <tr>
                        <td class="py-2 font-mono text-gray-700">#{{ $selling->code }}</td>
                        <td class="py-2 text-gray-600">{{ $selling->created_at->format('d M Y H:i') }}</td>
                        <td class="py-2 text-gray-600">{{ $selling->total_qty }}</td>
                        <td class="py-2 text-right font-medium">{{ number_format($selling->grand_total_price) }} {{ $currency }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4">
                {{ $sellings->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
