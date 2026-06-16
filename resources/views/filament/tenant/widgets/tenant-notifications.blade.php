@php
  $unreadCount = $unreadCount ?? 0;
  $totalCount = $totalCount ?? 0;
  $notifications = $notifications ?? collect();
  $showAll = $showAll ?? false;
@endphp

<x-filament::section>
  <x-slot name="heading">
    <span class="flex items-center gap-2">
      <x-heroicon-o-bell class="h-5 w-5" />
      @if($totalCount > 0)
        <span class="inline-flex items-center rounded-full bg-danger-500 px-2 py-0.5 text-xs font-semibold text-white">
          {{ $unreadCount }}{{ $showAll ? '+' : '' }} unread
        </span>
      @endif
    </span>
  </x-slot>

  @if($totalCount > 0)
    <div class="flex justify-end mb-2">
      <button
        wire:click="markAllAsRead"
        wire:loading.attr="disabled"
        class="text-xs text-gray-500 hover:text-gray-700 underline"
      >
        Mark all as read
      </button>
    </div>
  @endif

  @forelse($notifications as $n)
    <div class="rounded-lg border p-3 {{ $n->read_at ? 'border-gray-100 bg-white' : 'border-danger-200 bg-danger-50' }}">
      <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold {{ $n->read_at ? 'text-gray-700' : 'text-gray-900' }} truncate">
            {{ $n->data['title'] ?? 'No title' }}
          </p>
          <p class="mt-0.5 text-sm text-gray-600 truncate">{{ $n->data['body'] ?? '' }}</p>
        </div>
        <div class="flex items-center gap-2 ml-2">
          @if(is_null($n->read_at))
            <button
              wire:click="markAsRead('{{ $n->id }}')"
              wire:loading.attr="disabled"
              class="text-xs text-gray-400 hover:text-gray-600 whitespace-nowrap"
              title="Mark as read"
            >
              <x-heroicon-o-check class="h-4 w-4" />
            </button>
          @endif
          <p class="text-xs text-gray-400 whitespace-nowrap">{{ $n->created_at->diffForHumans() }}</p>
        </div>
      </div>
    </div>
  @empty
    <div class="text-center py-8">
      <x-heroicon-o-check-circle class="h-10 w-10 text-gray-400 mx-auto mb-2" />
      <p class="text-sm text-gray-500 font-medium">All caught up!</p>
      <p class="text-xs text-gray-400 mt-1">No unread notifications</p>
    </div>
  @endforelse

  @if($showAll)
    <div class="mt-3 pt-3 border-t border-gray-200">
      <p class="text-xs text-gray-500">
        Showing 5 of {{ $totalCount }} unread notifications
      </p>
    </div>
  @endif
</x-filament::section>

@script
<script>
    Livewire.on('refreshNotifications', () => {
        $wire.$refresh();
    });
</script>
@endscript
