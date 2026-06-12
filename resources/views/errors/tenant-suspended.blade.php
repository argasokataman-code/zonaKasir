<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Akun Dinonaktifkan</title>
  @vite('resources/css/app.css')
</head>
<body class="flex min-h-screen items-center justify-center bg-gray-100">
  <div class="max-w-md text-center px-6">
    <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-red-100">
      <svg class="h-10 w-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
      </svg>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Akun Dinonaktifkan</h1>
    <p class="text-gray-600 mb-6">{{ $reason ?? 'Your account has been suspended. Please contact support.' }}</p>
    <a href="mailto:support@zonakasir.com" class="inline-flex items-center rounded-xl bg-orange-500 px-6 py-3 text-sm font-semibold text-white shadow transition-all hover:bg-orange-600">
      Hubungi Support
    </a>
  </div>
</body>
</html>
