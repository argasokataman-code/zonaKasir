@extends('errors::layout')

@section('title', 'Tidak Diizinkan')
@section('icon-bg', '#FEF3C7')
@section('content')
    <div class="error-icon">🚫</div>
    <div class="error-code">403</div>
    <div class="error-title">Akses Ditolak</div>
    <div class="error-message">Kamu tidak memiliki izin untuk mengakses halaman ini. Hubungi admin jika kamu merasa ini adalah kesalahan.</div>
    <div class="error-actions">
        <a href="/" class="btn btn-primary">← Kembali</a>
    </div>
@endsection
