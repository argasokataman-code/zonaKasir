<div id="session-timeout-modal"
  style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:12px;padding:2rem;max-width:400px;width:90%;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,0.25);">
    <div style="font-size:2.5rem;margin-bottom:0.5rem;">&#9200;</div>
    <h2 style="margin:0 0 0.5rem;font-size:1.25rem;color:#1f2937;">
      {{ __('Session Expiring Soon') }}
    </h2>
    <p style="margin:0 0 1rem;color:#6b7280;font-size:0.9rem;">
      {{ __('Your session will expire in') }}
    </p>
    <div id="session-countdown"
      style="font-size:2rem;font-weight:bold;color:#dc2626;margin-bottom:1.5rem;font-variant-numeric:tabular-nums;">
      5:00
    </div>
    <div style="display:flex;gap:0.75rem;justify-content:center;">
      <button id="session-logout-now"
        style="padding:0.5rem 1.25rem;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#374151;cursor:pointer;font-size:0.9rem;">
        {{ __('Logout') }}
      </button>
      <button id="session-stay-logged-in"
        style="padding:0.5rem 1.25rem;border-radius:8px;border:none;background:#2563eb;color:#fff;cursor:pointer;font-size:0.9rem;">
        {{ __('Stay Logged In') }}
      </button>
    </div>
  </div>
</div>
