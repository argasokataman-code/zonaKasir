<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />

        <meta name="application-name" content="{{ config('app.name') }}" />
        <meta name="description" content="POS (Point of Sale) application — {{ config('app.name') }}" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="theme-color" content="#FF6600" />
        <meta name="mobile-web-app-capable" content="yes" />
        <link rel="manifest" href="{{ route('laravelpwa.manifest') }}" />

        <title>{{ config('app.name') }}</title>

        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>

        @filamentStyles
        @vite('resources/css/app.css')
    </head>

    <body class="antialiased">
        {{ $slot }}

        @filamentScripts

        {{-- Global helpers for Alpine — blocking, available before deferred module scripts --}}
        <script>
        window.moneyFormat = function(number, currency) {
            var activeCurrency = currency || window.zonakasirCurrency || 'IDR';
            var activeLocale = window.zonakasirLocale || 'en';
            var options = { style: 'currency', currency: activeCurrency };
            if (activeCurrency === 'IDR') { options.minimumFractionDigits = 0; }
            return new Intl.NumberFormat(activeLocale, options).format(number);
        };
        </script>

        @vite('resources/js/app.js')
    </body>
</html>
