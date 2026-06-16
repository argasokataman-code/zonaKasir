@extends('errors::layout')

@section('title', 'Terlalu Banyak Request')
@section('icon-bg', '#FEF3C7')
@section('content')
    <div class="error-icon">⏳</div>
    <div class="error-code">429</div>
    <div class="error-title">Terlalu Banyak Permintaan</div>
    <div class="error-message">Kamu melakukan terlalu banyak permintaan dalam waktu singkat. Tunggu beberapa saat lalu coba lagi.</div>
    <div class="error-actions">
        <a href="javascript:location.reload()" class="btn btn-primary">Coba Lagi</a>
        <a href="/" class="btn btn-secondary">← Kembali</a>
    </div>
@endsection
