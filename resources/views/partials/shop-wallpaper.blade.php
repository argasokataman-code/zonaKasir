@php
  $about = App\Models\Tenants\About::first();
  $uploadDisk = config('filesystems.upload_disk');
  $wallpaper = null;

  if ($about?->photo) {
    try {
      $wallpaper = \App\Models\Tenants\UploadedFile::urlFromPath($about->photo, $uploadDisk);
    } catch (\Throwable) {}
  }
@endphp

@if($wallpaper)
<style>
  .fi-main {
    position: relative;
  }
  .fi-main::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image: url('{{ $wallpaper }}');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    opacity: 0.06;
    pointer-events: none;
    z-index: 0;
  }
  .dark .fi-main::before {
    opacity: 0.04;
    filter: brightness(0.5);
  }
</style>
@endif
