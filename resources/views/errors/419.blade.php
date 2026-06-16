@extends('errors::layout')

@section('title', 'Session Expired')
@section('icon-bg', '#FEF3C7')
@section('content')
    <div class="error-icon">🔒</div>
    <div class="error-code">419</div>
    <div class="error-title">Sesi Telah Habis</div>
    <div class="error-message">Sesi kamu sudah berakhir karena terlalu lama tidak aktif. Silakan masuk kembali untuk melanjutkan.</div>
    <div class="error-actions">
        <a href="/member/login" class="btn btn-primary">Masuk Kembali</a>
    </div>
@endsection
