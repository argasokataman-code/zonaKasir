<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pendaftaran Berhasil - ZonaKasir</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #F4F4F2;
      font-family: system-ui, -apple-system, sans-serif;
      padding: 20px;
    }
    .card {
      background: white;
      border-radius: 16px;
      padding: 48px 40px;
      text-align: center;
      max-width: 400px;
      width: 100%;
      box-shadow: 0 4px 24px rgba(0,0,0,0.06);
      border: 1px solid #E5E5E1;
      animation: floatIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    .checkmark {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      background: #1A1A1A;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px;
      animation: pop 0.4s cubic-bezier(0.16, 1, 0.3, 1) 0.2s both;
    }
    .checkmark svg {
      width: 32px;
      height: 32px;
      stroke: white;
      stroke-width: 3;
      fill: none;
      stroke-dasharray: 50;
      stroke-dashoffset: 50;
      animation: draw 0.5s ease 0.5s forwards;
    }
    h1 {
      font-size: 20px;
      font-weight: 700;
      color: #1A1A1A;
      margin-bottom: 8px;
    }
    p {
      font-size: 14px;
      color: #666666;
      line-height: 1.6;
      margin-bottom: 8px;
    }
    .loader {
      margin-top: 24px;
      width: 24px;
      height: 24px;
      border: 2px solid #E5E5E1;
      border-top-color: #1A1A1A;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      display: inline-block;
    }
    @keyframes floatIn {
      from { opacity: 0; transform: translateY(20px) scale(0.98); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes pop {
      0% { transform: scale(0); }
      70% { transform: scale(1.1); }
      100% { transform: scale(1); }
    }
    @keyframes draw {
      to { stroke-dashoffset: 0; }
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="checkmark">
      <svg viewBox="0 0 24 24">
        <polyline points="4,12 10,18 20,6" />
      </svg>
    </div>
    <h1>Akun Berhasil Dibuat!</h1>
    <p>Selamat datang di ZonaKasir.</p>
    <p style="font-size: 12px; color: #888888;">Mengalihkan ke halaman login...</p>
    <div class="loader"></div>
  </div>
  <script>
    setTimeout(() => { window.location.href = '/member/login'; }, 2500);
  </script>
</body>
</html>
