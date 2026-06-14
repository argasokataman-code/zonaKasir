@if($isTrial && $daysRemaining !== null)
  @php
    if ($daysRemaining <= 2) {
      $bgClass = 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800';
      $textClass = 'text-red-800 dark:text-red-200';
      $subTextClass = 'text-red-600 dark:text-red-400';
      $btnClass = 'text-red-700 bg-red-100 hover:bg-red-200 dark:text-red-200 dark:bg-red-800 dark:hover:bg-red-700';
    } elseif ($daysRemaining <= 5) {
      $bgClass = 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800';
      $textClass = 'text-yellow-800 dark:text-yellow-200';
      $subTextClass = 'text-yellow-600 dark:text-yellow-400';
      $btnClass = 'text-yellow-700 bg-yellow-100 hover:bg-yellow-200 dark:text-yellow-200 dark:bg-yellow-800 dark:hover:bg-yellow-700';
    } else {
      $bgClass = 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800';
      $textClass = 'text-blue-800 dark:text-blue-200';
      $subTextClass = 'text-blue-600 dark:text-blue-400';
      $btnClass = 'text-blue-700 bg-blue-100 hover:bg-blue-200 dark:text-blue-200 dark:bg-blue-800 dark:hover:bg-blue-700';
    }
  @endphp

  <div class="mb-4">
    <div class="flex items-center justify-between p-4 rounded-lg {{ $bgClass }}">

      <div class="flex items-center gap-3">
        <div class="flex-shrink-0">
          @if($daysRemaining <= 2)
            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
          @elseif($daysRemaining <= 5)
            <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
          @else
            <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
          @endif
        </div>

        <div>
          <p class="text-sm font-medium {{ $textClass }}">
            @if($daysRemaining <= 0)
              Trial sudah berakhir! Silakan berlangganan.
            @elseif($daysRemaining == 1)
              Trial berakhir besok! Silakan berlangganan.
            @else
              Trial versi — {{ $daysRemaining }} hari lagi
            @endif
          </p>
          <p class="text-xs {{ $subTextClass }}">
            Upgrade ke paket berbayar untuk menggunakan fitur lengkap.
          </p>
        </div>
      </div>

      <a href="{{ \Filament\Facades\Filament::getPanel('tenant')->getPages()['subscription']::getUrl() }}"
         class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg {{ $btnClass }}">
        {{ $daysRemaining <= 0 ? 'Berlangganan Sekarang' : 'Lihat Paket' }}
      </a>
    </div>
  </div>
@endif
