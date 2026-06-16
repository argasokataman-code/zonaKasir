@extends('errors::layout')

@section('title', 'Sedang Dalam Pemeliharaan')
@section('icon-bg', '#FEF3C7')
@section('content')
    <div class="error-icon">🔧</div>
    <div class="error-code">503</div>
    <div class="error-title">Sedang Dalam Pemeliharaan</div>
    <div class="error-message">Sistem sedang dalam pemeliharaan untuk perbaikan. Silakan coba lagi beberapa saat.</div>
    <div class="error-actions">
        <a href="javascript:location.reload()" class="btn btn-primary">Coba Lagi</a>
    </div>
@endsection
