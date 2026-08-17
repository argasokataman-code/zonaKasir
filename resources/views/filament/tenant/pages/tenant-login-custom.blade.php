<div class="login-root" x-data>
    <style>
        .login-root {
            display: flex;
            min-height: 100vh;
            background: #0a0a0a;
            margin: -1.5rem;
        }

        .login-left {
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 2rem;
            position: relative;
            z-index: 10;
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .login-right {
            display: none;
            flex: 1;
            position: relative;
            overflow: hidden;
        }

        @media (min-width: 1024px) {
            .login-left { width: 520px; padding: 3rem 4rem; }
            .login-right { display: block; }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .carousel-slide {
            position: absolute; inset: 0; opacity: 0; transform: translateY(20px);
            transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1); pointer-events: none;
        }
        .carousel-slide.active { opacity: 1; transform: translateY(0); pointer-events: auto; }
        .carousel-slide.exit { opacity: 0; transform: translateY(-20px); }

        .dot-indicator {
            width: 8px; height: 8px; border-radius: 9999px;
            background: rgba(255,255,255,0.3); transition: all 0.4s ease; cursor: pointer; border: none;
        }
        .dot-indicator.active { background: white; width: 28px; }

        .feature-icon-bg {
            background: rgba(255,255,255,0.1); backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.15);
        }
        .login-form-card {
            backdrop-filter: blur(20px); background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06); border-radius: 1rem;
        }
        .login-badge {
            display: inline-flex; gap: 0.375rem; padding: 0.375rem 0.75rem;
            border-radius: 9999px; background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.8); font-size: 0.75rem; font-weight: 500;
        }
    </style>

    {{-- Left: Login Form --}}
    <div class="login-left">
        <div style="margin-bottom: 2.5rem">
            <a href="/" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none">
                <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: linear-gradient(to bottom right, #f97316, #dc2626); display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 15px -3px rgba(249, 115, 22, 0.2)">
                    <svg style="width: 1.25rem; height: 1.25rem; color: white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <span style="font-size: 1.25rem; font-weight: 700; color: white; letter-spacing: -0.025em">zona<span style="color: #f97316">Kasir</span></span>
            </a>
        </div>

        <div style="margin-bottom: 2rem">
            <h1 style="font-size: 1.875rem; font-weight: 700; color: white; letter-spacing: -0.025em; margin: 0 0 0.5rem">Selamat datang kembali</h1>
            <p style="color: #9ca3af; font-size: 0.875rem; margin: 0">Masuk ke dashboard untuk mengelola bisnis Anda</p>
        </div>

        <div class="login-form-card" style="padding: 2rem">
            {{ $this->form }}

            <div style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem">
                <button type="submit" form="login-form"
                    style="width: 100%; padding: 0.75rem 1.5rem; background: linear-gradient(to right, #f97316, #dc2626); color: white; font-weight: 600; font-size: 0.875rem; border-radius: 0.75rem; border: none; cursor: pointer; box-shadow: 0 10px 15px -3px rgba(249, 115, 22, 0.25); transition: all 0.3s"
                    onmouseover="this.style.background='linear-gradient(to right, #ea580c, #b91c1c)'"
                    onmouseout="this.style.background='linear-gradient(to right, #f97316, #dc2626)'"
                >
                    Masuk
                </button>
            </div>

            @error('data.email')
                <div style="margin-top: 0.75rem; font-size: 0.875rem; color: #f87171; text-align: center">{{ $message }}</div>
            @enderror

            @include('filament.tenant.pages.google-login-button')
        </div>

        <div style="margin-top: 2rem; display: flex; align-items: center; justify-content: space-between">
            <a href="https://zonakasir.com" target="_blank" style="color: #6b7280; font-size: 0.625rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; text-decoration: none">Powered by zonaKasir</a>
            <span style="color: #374151; font-size: 0.625rem; font-weight: 500">v{{ config('app.version', '1.0') }}</span>
        </div>
    </div>

    {{-- Right: Carousel --}}
    <div class="login-right">
        <div style="position: absolute; inset: 0">
            <img src="/images/landing/retail_hero_bg_1781378962689.jpg" alt="" style="width: 100%; height: 100%; object-fit: cover" loading="lazy">
            <div style="position: absolute; inset: 0; background: linear-gradient(to right, #0a0a0a, rgba(10,10,10,0.8), transparent)"></div>
            <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(10,10,10,0.9), transparent, rgba(10,10,10,0.4))"></div>
        </div>

        <div style="position: relative; height: 100%; display: flex; flex-direction: column; justify-content: center; padding: 0 5rem">
            <div style="position: relative; height: 320px">
                {{-- Slide 1: POS --}}
                <div class="carousel-slide active" data-slide="0">
                    <div class="feature-icon-bg" style="width: 4rem; height: 4rem; border-radius: 1rem; display: flex; align-items: center; justify-content: center; margin-bottom: 2rem">
                        <svg style="width: 2rem; height: 2rem; color: #fb923c" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25v-.008zm2.25-6.75h.008v.008H10.5v-.008zm0 2.25h.008v.008H10.5v-.008zm0 2.25h.008v.008H10.5v-.008zm0 2.25h.008v.008H10.5v-.008zm2.25-6.75h.008v.008H12.75v-.008zm0 2.25h.008v.008H12.75v-.008zm2.25-6.75h.008v.008H15v-.008zm0 2.25h.008v.008H15v-.008zm0 2.25h.008v.008H15v-.008zm0 2.25h.008v.008H15v-.008zM3.375 3h17.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125H3.375A1.125 1.125 0 012.25 19.875V4.125C2.25 3.504 2.754 3 3.375 3z"/>
                        </svg>
                    </div>
                    <h2 style="font-size: 2.25rem; font-weight: 700; color: white; letter-spacing: -0.025em; margin: 0 0 1rem">POS <span style="color: #fb923c">Cepat</span> & Mudah</h2>
                    <p style="color: #d1d5db; font-size: 1.125rem; line-height: 1.625; max-width: 28rem; margin: 0">Transaksi kilat dengan barcode scanner, multiple payment methods, dan split bill. Dirancang untuk kecepatan kasir Anda.</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1.5rem">
                        <span class="login-badge">Barcode Scanner</span>
                        <span class="login-badge">Split Bill</span>
                        <span class="login-badge">QRIS</span>
                    </div>
                </div>

                {{-- Slide 2: Inventory --}}
                <div class="carousel-slide" data-slide="1">
                    <div class="feature-icon-bg" style="width: 4rem; height: 4rem; border-radius: 1rem; display: flex; align-items: center; justify-content: center; margin-bottom: 2rem">
                        <svg style="width: 2rem; height: 2rem; color: #fb923c" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                        </svg>
                    </div>
                    <h2 style="font-size: 2.25rem; font-weight: 700; color: white; letter-spacing: -0.025em; margin: 0 0 1rem">Kelola <span style="color: #fb923c">Inventory</span> Otomatis</h2>
                    <p style="color: #d1d5db; font-size: 1.125rem; line-height: 1.625; max-width: 28rem; margin: 0">Stock opname, stock transfer, alert kedaluwarsa, dan pembelian supplier — semua terpusat dan real-time.</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1.5rem">
                        <span class="login-badge">Stock Opname</span>
                        <span class="login-badge">Purchasing</span>
                        <span class="login-badge">Expiry Alert</span>
                    </div>
                </div>

                {{-- Slide 3: Reports --}}
                <div class="carousel-slide" data-slide="2">
                    <div class="feature-icon-bg" style="width: 4rem; height: 4rem; border-radius: 1rem; display: flex; align-items: center; justify-content: center; margin-bottom: 2rem">
                        <svg style="width: 2rem; height: 2rem; color: #fb923c" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                        </svg>
                    </div>
                    <h2 style="font-size: 2.25rem; font-weight: 700; color: white; letter-spacing: -0.025em; margin: 0 0 1rem">Laporan <span style="color: #fb923c">Realtime</span></h2>
                    <p style="color: #d1d5db; font-size: 1.125rem; line-height: 1.625; max-width: 28rem; margin: 0">Sales per kategori, per brand, per produk, profit analysis — semuanya otomatis dengan PDF export.</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1.5rem">
                        <span class="login-badge">Sales Report</span>
                        <span class="login-badge">Profit</span>
                        <span class="login-badge">PDF Export</span>
                    </div>
                </div>

                {{-- Slide 4: On-Premise --}}
                <div class="carousel-slide" data-slide="3">
                    <div class="feature-icon-bg" style="width: 4rem; height: 4rem; border-radius: 1rem; display: flex; align-items: center; justify-content: center; margin-bottom: 2rem">
                        <svg style="width: 2rem; height: 2rem; color: #fb923c" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z"/>
                        </svg>
                    </div>
                    <h2 style="font-size: 2.25rem; font-weight: 700; color: white; letter-spacing: -0.025em; margin: 0 0 1rem"><span style="color: #fb923c">On-Premise</span> Self-Hosted</h2>
                    <p style="color: #d1d5db; font-size: 1.125rem; line-height: 1.625; max-width: 28rem; margin: 0">Data Anda di server Anda. Offline mode, local network, full control — tanpa bergantung pada cloud.</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1.5rem">
                        <span class="login-badge">Self-Hosted</span>
                        <span class="login-badge">Offline Mode</span>
                        <span class="login-badge">Full Control</span>
                    </div>
                </div>
            </div>

            {{-- Dots --}}
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 3rem" x-data="loginCarousel()">
                <template x-for="i in 4" :key="i">
                    <button type="button" class="dot-indicator" :class="{ 'active': current === i - 1 }" @click="goTo(i - 1)"></button>
                </template>
                <span style="margin-left: 1rem; color: rgba(255,255,255,0.4); font-size: 0.75rem; font-weight: 500" x-text="(current + 1) + ' / 4'"></span>
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
