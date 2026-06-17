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
            $loginUrl = $isAdmin ? '/admin/login' : '/member/login';
        @endphp
        <a href="{{ $homeUrl }}" class="btn btn-primary">← Kembali</a>
        <a href="{{ $loginUrl }}" class="btn btn-secondary">Masuk Akun Lain</a>
    </div>
    <p style="margin-top:1.5rem;font-size:12px;color:#9ca3af;">Redirect otomatis dalam <span id="auto-redirect-countdown">8</span> detik...</p>
    <script>
    (function(){
        var seconds = 8;
        var countdownEl = document.getElementById('auto-redirect-countdown');
        var timer = setInterval(function(){
            seconds--;
            if (countdownEl) countdownEl.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.href = '{{ $homeUrl }}';
            }
        }, 1000);
    })();
    </script>
@endsection
