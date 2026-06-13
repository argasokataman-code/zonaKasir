@extends('member-portal.layouts.app')

@section('title', __('Edit Profile'))

@section('content')
<h2 class="text-xl font-bold text-gray-800 mb-4">{{ __('Edit Profile') }}</h2>

<div class="bg-white rounded-lg shadow p-6 max-w-lg">
    <form method="POST" action="{{ route('member.portal.profile.update') }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Name') }}</label>
            <input name="name" value="{{ old('name', $member->name) }}" required
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('name') border-red-500 @enderror">
            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Email') }}</label>
            <input type="email" value="{{ $member->email }}" disabled
                class="w-full border border-gray-200 bg-gray-50 rounded-md px-3 py-2 text-sm text-gray-500">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Address') }}</label>
            <input name="address" value="{{ old('address', $member->address) }}"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('address') border-red-500 @enderror">
            @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <hr class="my-6">

        <p class="text-sm font-medium text-gray-700 mb-3">{{ __('Change Password (leave empty to keep current)') }}</p>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Current Password') }}</label>
            <input name="current_password" type="password"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('current_password') border-red-500 @enderror">
            @error('current_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('New Password') }}</label>
            <input name="password" type="password"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('password') border-red-500 @enderror">
            @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Confirm Password') }}</label>
            <input name="password_confirmation" type="password"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
        </div>

        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700">
            {{ __('Update Profile') }}
        </button>
    </form>
</div>
@endsection
