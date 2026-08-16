<div>
  @if($showWarning)
    <div class="mb-4">
      <div class="flex items-center justify-between p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
        <div class="flex items-center gap-3">
          <div class="flex-shrink-0">
            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
          </div>
          <div>
            <p class="text-sm font-medium text-red-800 dark:text-red-200">
              {{ __('License warning: :reason', ['reason' => $reason]) }}
            </p>
            <p class="text-xs text-red-600 dark:text-red-400">
              @if($expiresAt)
                {{ __('License expired on :date. Contact your vendor to renew.', ['date' => $expiresAt]) }}
              @else
                {{ __('Contact your vendor to renew. Kasir keeps working.') }}
              @endif
            </p>
          </div>
        </div>
      </div>
    </div>
  @endif
</div>
