@extends('errors::layout')

@section('title', 'Halaman Tidak Ditemukan')
@section('content')
    <div class="error-icon">🔍</div>
    <div class="error-code">404</div>
    <div class="error-title">Halaman Tidak Ditemukan</div>
    <div class="error-message">Halaman yang kamu cari sudah dipindahkan, dihapus, atau tidak pernah ada.</div>
    <div class="error-actions">
        @php
            $isAdmin = str_starts_with(request()->path(), 'admin');
            $homeUrl = $isAdmin ? '/admin' : '/member';
        @endphp
        <a href="{{ $homeUrl }}" class="btn btn-primary">← Kembali</a>
        <a href="javascript:history.back()" class="btn btn-secondary">Halaman Sebelumnya</a>
    </div>
@endsection
