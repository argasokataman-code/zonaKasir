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

  @forelse($notifications as $n)
    <div class="rounded-lg border p-3 {{ $n->read_at ? 'border-gray-100 bg-white' : 'border-danger-200 bg-danger-50' }}">
      <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold {{ $n->read_at ? 'text-gray-700' : 'text-gray-900' }} truncate">
            {{ $n->data['title'] ?? 'No title' }}
          </p>
          <p class="mt-0.5 text-sm text-gray-600 truncate">{{ $n->data['body'] ?? '' }}</p>
        </div>
        <p class="text-xs text-gray-400 ml-2 whitespace-nowrap">{{ $n->created_at->diffForHumans() }}</p>
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
      <p class="text-xs text-gray-500 mb-2">
        Showing 5 of {{ $totalCount }} unread notifications
      </p>
      <a href="{{ route('notification.index') }}"
         class="text-sm text-primary-600 hover:text-primary-800 font-medium flex items-center gap-1">
        View all notifications
        <x-heroicon-o-arrow-right class="h-4 w-4" />
      </a>
    </div>
  @endif
</x-filament::section>
