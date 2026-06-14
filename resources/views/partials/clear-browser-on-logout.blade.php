@if(!auth()->check())
<script>
  localStorage.clear();
  sessionStorage.clear();
  // Clear all cookies for this domain
  document.cookie.split(';').forEach(function(c) {
    document.cookie = c.replace(/^ +/, '').replace(/=.*/, '=;expires=' + new Date().toUTCString() + ';path=/');
  });
</script>
@endif
