@extends('errors::layout')

@section('title', 'Sesi Telah Habis')
@section('content')
    <div class="error-icon">🔒</div>
    <div class="error-code">419</div>
    <div class="error-title">Sesi Telah Habis</div>
    <div class="error-message">Sesi kamu sudah berakhir karena terlalu lama tidak aktif. Silakan masuk kembali.</div>
    <div class="error-actions">
        @php
            $isAdmin = str_starts_with(request()->path(), 'admin');
            $loginUrl = $isAdmin ? '/admin/login' : '/member/login';
        @endphp
        <a href="{{ $loginUrl }}" class="btn btn-primary">Masuk Kembali</a>
    </div>
@endsection
