<div class="login-root" x-data>
    <style>
        html, body { height: 100% !important; overflow: hidden !important; margin: 0 !important; padding: 0 !important; }
        .fi-layout, .fi-main, .fi-content, [class*="fi-"] { margin: 0 !important; padding: 0 !important; }

        .login-root {
            display: flex;
            height: 100vh;
            width: 100vw;
            background: #0a0a0a;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 9999;
            margin: 0;
            padding: 0;
        }

        .login-left {
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            background: #0a0a0a;
            z-index: 10;
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .login-right {
            display: none;
            flex: 1;
            position: relative;
            overflow: hidden;
        }

        .login-right-bg {
            position: absolute;
            inset: 0;
        }
        .login-right-bg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .login-right-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, #0a0a0a 0%, rgba(10,10,10,0.98) 10%, rgba(10,10,10,0.85) 25%, rgba(10,10,10,0.5) 50%, rgba(10,10,10,0.3) 75%, rgba(10,10,10,0.5) 100%),
                        linear-gradient(to top, rgba(10,10,10,0.8) 0%, transparent 25%, transparent 75%, rgba(10,10,10,0.4) 100%);
        }

        .login-right-inner {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            transform: translateY(-50%);
            padding: 0 4rem 0 8rem;
        }

        @media (min-width: 1024px) {
            .login-left { width: 50%; min-width: 500px; padding: 3rem; }
            .login-right { display: block; }
        }
        @media (min-width: 1280px) {
            .login-left { width: 45%; min-width: 500px; padding: 3rem 4rem; }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .carousel-slide {
            position: absolute; inset: 0; opacity: 0; transform: translateY(20px);
            transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1); pointer-events: none;
        }
        .carousel-slide.active { opacity: 1; transform: translateY(0); pointer-events: auto; }
        .carousel-slide.exit { opacity: 0; transform: translateY(-20px); }

        .dot-indicator {
            width: 8px; height: 8px; border-radius: 9999px;
            background: rgba(255,255,255,0.25); transition: all 0.4s ease; cursor: pointer; border: none;
        }
        .dot-indicator.active { background: white; width: 24px; }

        .feature-icon-bg {
            background: rgba(255,255,255,0.08); backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .login-form-card {
            width: 100%; max-width: 380px;
            backdrop-filter: blur(24px); background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06); border-radius: 1rem;
            padding: 1.75rem;
        }
        .login-badge {
            display: inline-flex; gap: 0.375rem; padding: 0.375rem 0.75rem;
            border-radius: 9999px; background: rgba(255,255,255,0.07);
            color: rgba(255,255,255,0.65); font-size: 0.75rem; font-weight: 500;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .login-form-card .fi-fo-field-wrp { margin-bottom: 1.125rem; }
        .login-form-card .fi-fo-field-wrp label { color: #9ca3af !important; font-size: 0.8125rem; font-weight: 500; margin-bottom: 0.375rem; display: block; }
        .login-form-card input[type="email"],
        .login-form-card input[type="password"],
        .login-form-card input[type="text"] {
            width: 100%; padding: 0.6875rem 0.875rem; background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1); border-radius: 0.5rem;
            color: white; font-size: 0.875rem; transition: all 0.2s; outline: none;
        }
        .login-form-card input:focus { border-color: #f97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1); background: rgba(255,255,255,0.07); }
        .login-form-card input::placeholder { color: #4b5563; }
        .login-form-card .fi-fo-field-wrp-error-message { color: #f87171; font-size: 0.8125rem; margin-top: 0.25rem; }
        .login-form-card .fi-fo-checkbox-input-label { color: #9ca3af !important; font-size: 0.875rem; }

        .login-or { display: flex; align-items: center; gap: 0.75rem; margin: 1rem 0; }
        .login-or::before, .login-or::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.08); }
        .login-or span { color: #4b5563; font-size: 0.6875rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.08em; }
    </style>

    {{-- Left: Login Form --}}
    <div class="login-left">
        {{-- Logo --}}
        <div style="margin-bottom: 2rem">
            <a href="/" style="display: inline-flex; align-items: center; gap: 0.625rem; text-decoration: none">
                <img src="{{ asset('assets/logo/zonaqasir-icon.png') }}" alt="ZonaQasir" style="height: 2.25rem; width: 2.25rem; object-fit: contain; border-radius: 0.375rem">
                <span style="font-size: 1.125rem; font-weight: 700; color: white; letter-spacing: -0.02em">Zona<span style="color: #f97316">Qasir</span></span>
            </a>
        </div>

        {{-- Heading --}}
        <div style="margin-bottom: 1.75rem; text-align: center">
            <h1 style="font-size: 1.375rem; font-weight: 700; color: white; letter-spacing: -0.02em; margin: 0 0 0.25rem">Selamat datang kembali</h1>
            <p style="color: #6b7280; font-size: 0.8125rem; margin: 0">Masuk ke dashboard untuk mengelola bisnis Anda</p>
        </div>

        {{-- Form Card --}}
        <div class="login-form-card">
            {{ $this->form }}

            <div style="margin-top: 1rem">
                <button type="button" wire:click="authenticate"
                    style="width: 100%; padding: 0.6875rem 1.5rem; background: linear-gradient(135deg, #f97316, #dc2626); color: white; font-weight: 600; font-size: 0.875rem; border-radius: 0.5rem; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(249, 115, 22, 0.25); transition: all 0.2s"
                    onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 16px rgba(249, 115, 22, 0.35)'"
                    onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 12px rgba(249, 115, 22, 0.25)'"
                    wire:loading.attr="disabled"
                    wire:loading.style="opacity:0.7;cursor:not-allowed;transform:none"
                >
                    <span wire:loading.remove>Masuk</span>
                    <span wire:loading class="flex items-center justify-center gap-2">
                        <svg style="width:1rem;height:1rem;animation:spin 1s linear infinite" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity:0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity:0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Memproses...
                    </span>
                </button>
            </div>

            @error('data.email')
                <div style="margin-top: 0.625rem; font-size: 0.8125rem; color: #f87171; text-align: center">{{ $message }}</div>
            @enderror

            <div class="login-or">
                <span>atau</span>
            </div>

            @include('filament.tenant.pages.google-login-button')
        </div>

        {{-- Footer --}}
        <div style="margin-top: 1.25rem; display: flex; align-items: center; justify-content: space-between; width: 100%; max-width: 380px">
            <a href="https://zonakasir.com" target="_blank" style="color: #4b5563; font-size: 0.6875rem; font-weight: 500; text-decoration: none; transition: color 0.2s" onmouseover="this.style.color='#6b7280'" onmouseout="this.style.color='#4b5563'">Powered by ZonaQasir</a>
            <span style="color: #374151; font-size: 0.6875rem; font-weight: 500">v{{ config('app.version', '1.0') }}</span>
        </div>
    </div>

    {{-- Right: Carousel --}}
    <div class="login-right">
        <div class="login-right-bg">
            <img src="/images/landing/retail_hero_bg_1781378962689.jpg" alt="" loading="lazy">
        </div>

        <div class="login-right-inner">
            <div style="position: relative; height: 280px; width: 100%; max-width: 440px">
                {{-- Slide 1: POS --}}
                <div class="carousel-slide active" data-slide="0">
                    <div class="feature-icon-bg" style="width: 3rem; height: 3rem; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem">
                        <svg style="width: 1.5rem; height: 1.5rem; color: #fb923c" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25v-.008zm2.25-6.75h.008v.008H10.5v-.008zm0 2.25h.008v.008H10.5v-.008zm0 2.25h.008v.008H10.5v-.008zm0 2.25h.008v.008H10.5v-.008zm2.25-6.75h.008v.008H12.75v-.008zm0 2.25h.008v.008H12.75v-.008zm2.25-6.75h.008v.008H15v-.008zm0 2.25h.008v.008H15v-.008zm0 2.25h.008v.008H15v-.008zm0 2.25h.008v.008H15v-.008zM3.375 3h17.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125H3.375A1.125 1.125 0 012.25 19.875V4.125C2.25 3.504 2.754 3 3.375 3z"/>
                        </svg>
                    </div>
                    <h2 style="font-size: 1.75rem; font-weight: 700; color: white; letter-spacing: -0.025em; margin: 0 0 0.625rem">POS <span style="color: #fb923c">Cepat</span> & Mudah</h2>
                    <p style="color: rgba(255,255,255,0.55); font-size: 0.9375rem; line-height: 1.6; max-width: 24rem; margin: 0">Transaksi kilat dengan barcode scanner, multiple payment methods, dan split bill.</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem">
                        <span class="login-badge">Barcode Scanner</span>
                        <span class="login-badge">Split Bill</span>
                        <span class="login-badge">QRIS</span>
                    </div>
                </div>

                {{-- Slide 2: Inventory --}}
                <div class="carousel-slide" data-slide="1">
                    <div class="feature-icon-bg" style="width: 3rem; height: 3rem; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem">
                        <svg style="width: 1.5rem; height: 1.5rem; color: #fb923c" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                        </svg>
                    </div>
                    <h2 style="font-size: 1.75rem; font-weight: 700; color: white; letter-spacing: -0.025em; margin: 0 0 0.625rem">Kelola <span style="color: #fb923c">Inventory</span> Otomatis</h2>
                    <p style="color: rgba(255,255,255,0.55); font-size: 0.9375rem; line-height: 1.6; max-width: 24rem; margin: 0">Stock opname, stock transfer, alert kedaluwarsa, dan pembelian supplier.</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem">
                        <span class="login-badge">Stock Opname</span>
                        <span class="login-badge">Purchasing</span>
                        <span class="login-badge">Expiry Alert</span>
                    </div>
                </div>

                {{-- Slide 3: Reports --}}
                <div class="carousel-slide" data-slide="2">
                    <div class="feature-icon-bg" style="width: 3rem; height: 3rem; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem">
                        <svg style="width: 1.5rem; height: 1.5rem; color: #fb923c" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                        </svg>
                    </div>
                    <h2 style="font-size: 1.75rem; font-weight: 700; color: white; letter-spacing: -0.025em; margin: 0 0 0.625rem">Laporan <span style="color: #fb923c">Realtime</span></h2>
                    <p style="color: rgba(255,255,255,0.55); font-size: 0.9375rem; line-height: 1.6; max-width: 24rem; margin: 0">Sales per kategori, per brand, per produk, profit analysis — otomatis.</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem">
                        <span class="login-badge">Sales Report</span>
                        <span class="login-badge">Profit</span>
                        <span class="login-badge">PDF Export</span>
                    </div>
                </div>

                {{-- Slide 4: On-Premise --}}
                <div class="carousel-slide" data-slide="3">
                    <div class="feature-icon-bg" style="width: 3rem; height: 3rem; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem">
                        <svg style="width: 1.5rem; height: 1.5rem; color: #fb923c" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z"/>
                        </svg>
                    </div>
                    <h2 style="font-size: 1.75rem; font-weight: 700; color: white; letter-spacing: -0.025em; margin: 0 0 0.625rem"><span style="color: #fb923c">On-Premise</span> Self-Hosted</h2>
                    <p style="color: rgba(255,255,255,0.55); font-size: 0.9375rem; line-height: 1.6; max-width: 24rem; margin: 0">Data Anda di server Anda. Offline mode, local network, full control.</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem">
                        <span class="login-badge">Self-Hosted</span>
                        <span class="login-badge">Offline Mode</span>
                        <span class="login-badge">Full Control</span>
                    </div>
                </div>
            </div>

            {{-- Dots --}}
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 2rem" x-data="loginCarousel()">
                <template x-for="i in 4" :key="i">
                    <button type="button" class="dot-indicator" :class="{ 'active': current === i - 1 }" @click="goTo(i - 1)"></button>
                </template>
                <span style="margin-left: 0.75rem; color: rgba(255,255,255,0.25); font-size: 0.6875rem; font-weight: 500" x-text="(current + 1) + ' / 4'"></span>
            </div>
        </div>
    </div>

    <script>
        function loginCarousel() {
            return {
                current: 0, total: 4, interval: null,
                init() { this.startAutoplay(); },
                startAutoplay() { this.interval = setInterval(() => this.next(), 5000); },
                stopAutoplay() { clearInterval(this.interval); },
                next() {
                    const slides = document.querySelectorAll('.carousel-slide');
                    const old = this.current;
                    slides[old].classList.remove('active');
                    slides[old].classList.add('exit');
                    setTimeout(() => slides[old].classList.remove('exit'), 700);
                    this.current = (this.current + 1) % this.total;
                    slides[this.current].classList.add('active');
                },
                goTo(index) {
                    if (index === this.current) return;
                    this.stopAutoplay();
                    const slides = document.querySelectorAll('.carousel-slide');
                    const old = this.current;
                    slides[old].classList.remove('active');
                    slides[old].classList.add('exit');
                    setTimeout(() => slides[old].classList.remove('exit'), 700);
                    this.current = index;
                    slides[this.current].classList.add('active');
                    this.startAutoplay();
                },
            }
        }
    </script>
</div>
