@extends('errors::layout')

@section('title', 'Tidak Diizinkan')
@section('content')
    <div class="error-icon">🔐</div>
    <div class="error-code">401</div>
    <div class="error-title">Silakan Masuk Dulu</div>
    <div class="error-message">Kamu perlu masuk untuk mengakses halaman ini.</div>
    <div class="error-actions">
        @php
            $isAdmin = str_starts_with(request()->path(), 'admin');
            $loginUrl = $isAdmin ? '/admin/login' : '/member/login';
            $homeUrl = $isAdmin ? '/admin' : '/member';
        @endphp
        <a href="{{ $loginUrl }}" class="btn btn-primary">Masuk</a>
        <a href="{{ $homeUrl }}" class="btn btn-secondary">← Kembali</a>
    </div>
@endsection
