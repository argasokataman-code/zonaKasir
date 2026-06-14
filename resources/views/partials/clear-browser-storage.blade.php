@push('scripts')
<script>
  // Clear browser storage on login page load (after logout)
  localStorage.clear();
  sessionStorage.clear();
</script>
@endpush
