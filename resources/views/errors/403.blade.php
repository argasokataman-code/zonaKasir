@extends('errors::layout')

@section('title', 'Akses Ditolak')
@section('content')
    <div class="error-icon" style="background: #FEE2E2;">🚫</div>
    <div class="error-code" style="color: #DC2626;">403</div>
    <div class="error-title">Akses Ditolak</div>
    <div class="error-message">Kamu tidak memiliki izin untuk mengakses halaman ini. Hubungi admin jika kamu merasa ini adalah kesalahan.</div>
    <div class="error-actions">
        @php
            $isAdmin = str_starts_with(request()->path(), 'admin');
            $homeUrl = $isAdmin ? '/admin' : '/member';
        @endphp
        <a href="{{ $homeUrl }}" class="btn btn-primary">← Kembali</a>
    </div>
@endsection
