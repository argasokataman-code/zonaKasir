@extends('errors::layout')

@section('title', 'Silakan Masuk')
@section('icon-bg', '#FEF3C7')
@section('content')
    <div class="error-icon">🔐</div>
    <div class="error-code">401</div>
    <div class="error-title">Silakan Masuk Dulu</div>
    <div class="error-message">Kamu perlu masuk untuk mengakses halaman ini.</div>
    <div class="error-actions">
        <a href="/member/login" class="btn btn-primary">Masuk</a>
        <a href="/" class="btn btn-secondary">← Kembali</a>
    </div>
@endsection
