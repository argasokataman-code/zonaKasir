/**
 * Session Timeout Manager
 *
 * - Keep-alive: pings server every 15 min to prevent session expiry
 * - Idle detection: tracks user activity, shows warning at 115 min
 * - Auto-logout: at 120 min, calls logout endpoint + redirects to login
 * - Injects modal HTML via JS (works in both Filament panels)
 */
(function () {
  var SESSION_LIFETIME_MS = 120 * 60 * 1000; // 120 min from config/session.php
  var WARNING_AT_MS = 115 * 60 * 1000; // show warning 5 min before expiry
  var KEEPALIVE_INTERVAL_MS = 15 * 60 * 1000; // ping every 15 min
  var COUNTDOWN_TICK_MS = 1000;

  var lastActivity = Date.now();
  var warningShown = false;
  var countdownInterval = null;
  var countdownSeconds = 0;
  var keepAliveTimer = null;
  var checkTimer = null;

  // --- Inject modal HTML ---
  function injectModal() {
    if (document.getElementById('session-timeout-modal')) return;
    var div = document.createElement('div');
    div.id = 'session-timeout-modal';
    div.style.cssText =
      'display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;';
    div.innerHTML =
      '<div style="background:#fff;border-radius:12px;padding:2rem;max-width:400px;width:90%;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,0.25);">' +
      '<div style="font-size:2.5rem;margin-bottom:0.5rem;">&#9200;</div>' +
      '<h2 style="margin:0 0 0.5rem;font-size:1.25rem;color:#1f2937;">Session Expiring Soon</h2>' +
      '<p style="margin:0 0 1rem;color:#6b7280;font-size:0.9rem;">Your session will expire in</p>' +
      '<div id="session-countdown" style="font-size:2rem;font-weight:bold;color:#dc2626;margin-bottom:1.5rem;font-variant-numeric:tabular-nums;">5:00</div>' +
      '<div style="display:flex;gap:0.75rem;justify-content:center;">' +
      '<button id="session-logout-now" style="padding:0.5rem 1.25rem;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#374151;cursor:pointer;font-size:0.9rem;">Logout</button>' +
      '<button id="session-stay-logged-in" style="padding:0.5rem 1.25rem;border-radius:8px;border:none;background:#2563eb;color:#fff;cursor:pointer;font-size:0.9rem;">Stay Logged In</button>' +
      '</div></div>';
    document.body.appendChild(div);
  }

  // --- Activity tracking ---
  var ACTIVITY_EVENTS = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];

  function resetActivity() {
    lastActivity = Date.now();
    if (warningShown) {
      dismissWarning();
    }
  }

  function attachActivityListeners() {
    ACTIVITY_EVENTS.forEach(function (event) {
      document.addEventListener(event, resetActivity, { passive: true });
    });
  }

  // --- Keep-alive ---
  function sendKeepAlive() {
    fetch(window.location.pathname, {
      method: 'HEAD',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).catch(function () {});
  }

  function startKeepAlive() {
    keepAliveTimer = setInterval(sendKeepAlive, KEEPALIVE_INTERVAL_MS);
  }

  // --- Warning modal ---
  function showWarning() {
    if (warningShown) return;
    warningShown = true;

    countdownSeconds = Math.ceil(
      (SESSION_LIFETIME_MS - (Date.now() - lastActivity)) / 1000
    );
    if (countdownSeconds <= 0) {
      doLogout();
      return;
    }

    var modal = document.getElementById('session-timeout-modal');
    if (modal) {
      modal.style.display = 'flex';
      updateCountdownDisplay();
    }

    countdownInterval = setInterval(function () {
      countdownSeconds--;
      updateCountdownDisplay();
      if (countdownSeconds <= 0) {
        doLogout();
      }
    }, COUNTDOWN_TICK_MS);
  }

  function updateCountdownDisplay() {
    var el = document.getElementById('session-countdown');
    if (el) {
      var min = Math.floor(countdownSeconds / 60);
      var sec = countdownSeconds % 60;
      el.textContent = min + ':' + (sec < 10 ? '0' : '') + sec;
    }
  }

  function dismissWarning() {
    warningShown = false;
    if (countdownInterval) {
      clearInterval(countdownInterval);
      countdownInterval = null;
    }
    var modal = document.getElementById('session-timeout-modal');
    if (modal) {
      modal.style.display = 'none';
    }
  }

  // --- Logout ---
  function doLogout() {
    dismissWarning();
    if (keepAliveTimer) clearInterval(keepAliveTimer);
    if (checkTimer) clearInterval(checkTimer);

    fetch('/api/auth/logout', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken(),
        Accept: 'application/json',
      },
    }).finally(function () {
      // Determine redirect path based on current panel
      var path = window.location.pathname;
      var loginUrl = '/member/login';
      if (path.indexOf('/admin') === 0) {
        loginUrl = '/admin/login';
      }
      window.location.href = loginUrl;
    });
  }

  function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  // --- Stay Logged In button ---
  function handleStayLoggedIn() {
    resetActivity();
    sendKeepAlive();
  }

  // --- Idle check ---
  function startIdleCheck() {
    checkTimer = setInterval(function () {
      var elapsed = Date.now() - lastActivity;
      if (elapsed >= WARNING_AT_MS && !warningShown) {
        showWarning();
      }
    }, 10 * 1000); // check every 10 sec
  }

  // --- Init ---
  function init() {
    // Don't run on login page
    if (window.location.pathname.indexOf('/login') !== -1) return;

    injectModal();
    attachActivityListeners();
    startKeepAlive();
    startIdleCheck();

    // Wire up buttons
    var stayBtn = document.getElementById('session-stay-logged-in');
    var logoutBtn = document.getElementById('session-logout-now');
    if (stayBtn) stayBtn.addEventListener('click', handleStayLoggedIn);
    if (logoutBtn) logoutBtn.addEventListener('click', doLogout);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
