<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('Member Portal')) — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen">
    @auth('member')
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14">
                <div class="flex items-center space-x-6">
                    <a href="{{ route('member.portal.dashboard') }}" class="font-bold text-indigo-600 text-lg">
                        {{ config('app.name') }}
                    </a>
                    <a href="{{ route('member.portal.dashboard') }}" class="text-gray-700 hover:text-indigo-600 text-sm font-medium">
                        {{ __('Dashboard') }}
                    </a>
                    <a href="{{ route('member.portal.purchases') }}" class="text-gray-700 hover:text-indigo-600 text-sm font-medium">
                        {{ __('Purchases') }}
                    </a>
                    <a href="{{ route('member.portal.wallet') }}" class="text-gray-700 hover:text-indigo-600 text-sm font-medium">
                        {{ __('Wallet') }}
                    </a>
                    <a href="{{ route('member.portal.vouchers') }}" class="text-gray-700 hover:text-indigo-600 text-sm font-medium">
                        {{ __('Vouchers') }}
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('member.portal.profile') }}" class="text-gray-600 hover:text-indigo-600 text-sm">
                        {{ auth('member')->user()->name }}
                    </a>
                    <form method="POST" action="{{ route('member.portal.logout') }}">
                        @csrf
                        <button type="submit" class="text-red-500 hover:text-red-700 text-sm">
                            {{ __('Logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    @endauth

    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">
                {{ session('status') }}
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
