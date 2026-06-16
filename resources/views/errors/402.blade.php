@extends('errors::layout')

@section('title', 'Pembayaran Diperlukan')
@section('content')
    <div class="error-icon">💰</div>
    <div class="error-code">402</div>
    <div class="error-title">Pembayaran Diperlukan</div>
    <div class="error-message">Langganan kamu belum aktif atau sudah berakhir. Hubungi admin untuk mengaktifkan.</div>
    <div class="error-actions">
        <a href="/" class="btn btn-primary">← Kembali</a>
    </div>
@endsection
