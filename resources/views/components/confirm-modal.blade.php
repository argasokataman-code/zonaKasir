@props([
  'id' => 'confirm',
])

<div
  x-data="{
    open: false,
    title: '',
    message: '',
    confirmText: '{{ __("Delete") }}',
    confirmClass: 'bg-red-600 hover:bg-red-700',
    pendingAction: null,
    pendingParams: null,
    show(title, message, opts = {}) {
      this.title = title;
      this.message = message;
      this.confirmText = opts.confirmText || '{{ __("Delete") }}';
      this.confirmClass = opts.confirmClass || 'bg-red-600 hover:bg-red-700';
      this.pendingAction = opts.action || null;
      this.pendingParams = opts.params || null;
      this.open = true;
    },
    confirm() {
      if (this.pendingAction) {
        $dispatch(this.pendingAction, this.pendingParams);
      }
      this.open = false;
    }
  }"
  x-on:confirm-modal.window="
    if ($event.detail.id === '{{ $id }}') {
      show($event.detail.title, $event.detail.message, $event.detail.opts || {});
    }
  "
  x-show="open" x-cloak
  class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
  x-transition:enter="transition ease-out duration-200"
  x-transition:enter-start="opacity-0"
  x-transition:enter-end="opacity-100"
  x-transition:leave="transition ease-in duration-150"
  x-transition:leave-start="opacity-100"
  x-transition:leave-end="opacity-0"
>
  <div class="mx-4 w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    @click.away="open = false"
    @keydown.escape.window="open = false"
  >
    <div class="flex items-center gap-3 mb-4">
      <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
        </svg>
      </div>
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="title"></h3>
        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="message"></p>
      </div>
    </div>
    <div class="flex gap-3 mt-6">
      <button x-on:click="open = false"
        class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
        {{ __('Cancel') }}
      </button>
      <button x-on:click="confirm()"
        class="flex-1 rounded-lg px-4 py-2.5 text-sm font-medium text-white" x-bind:class="confirmClass" x-text="confirmText">
      </button>
    </div>
  </div>
</div>
