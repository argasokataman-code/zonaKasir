@extends('errors::layout')

@section('title', 'Server Error')
@section('content')
    <div class="error-icon" style="background: #FEE2E2;">⚠️</div>
    <div class="error-code" style="color: #DC2626;">500</div>
    <div class="error-title">Ups! Ada Masalah di Server</div>
    <div class="error-message">Server sedang mengalami gangguan. Tim kami sudah diberitahu. Coba beberapa saat lagi.</div>
    <div class="error-actions">
        <a href="javascript:location.reload()" class="btn btn-primary">Muat Ulang</a>
        @php
            $isAdmin = str_starts_with(request()->path(), 'admin');
            $homeUrl = $isAdmin ? '/admin' : '/member';
        @endphp
        <a href="{{ $homeUrl }}" class="btn btn-secondary">← Kembali</a>
    </div>
@endsection
