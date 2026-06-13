@extends('member-portal.layouts.app')

@section('title', __('Login'))

@section('content')
<div class="max-w-md mx-auto mt-16">
    <div class="bg-white rounded-lg shadow p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6 text-center">{{ __('Member Login') }}</h2>

        <form method="POST" action="{{ route('member.portal.login') }}">
            @csrf
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Password') }}</label>
                <input id="password" name="password" type="password" required
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('password') border-red-500 @enderror">
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4 flex items-center">
                <input id="remember" name="remember" type="checkbox" class="rounded border-gray-300">
                <label for="remember" class="ml-2 text-sm text-gray-600">{{ __('Remember me') }}</label>
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-md text-sm font-medium hover:bg-indigo-700">
                {{ __('Login') }}
            </button>
        </form>
    </div>
</div>
@endsection
