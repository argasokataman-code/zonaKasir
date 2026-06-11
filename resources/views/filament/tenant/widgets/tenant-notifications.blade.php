@php
  $notifications = auth()->user()?->notifications()->latest()->take(10)->get() ?? [];
  $unreadCount = auth()->user()?->unreadNotifications()->count() ?? 0;
@endphp

<x-filament::section>
  <x-slot name="heading">
    <span class="flex items-center gap-2">
      <x-heroicon-o-bell class="h-5 w-5" />
      @if($unreadCount > 0)
        <span class="inline-flex items-center rounded-full bg-primary-500 px-2 py-0.5 text-xs font-semibold text-white">{{ $unreadCount }} unread</span>
      @endif
    </span>
  </x-slot>

  @forelse($notifications as $n)
    <div class="rounded-lg border p-3 {{ $n->read_at ? 'border-gray-100 bg-white' : 'border-primary-200 bg-primary-50' }}">
      <div>
        <p class="text-sm font-semibold text-gray-900">{{ $n->data['title'] ?? 'No title' }}</p>
        <p class="mt-0.5 text-sm text-gray-600">{{ $n->data['body'] ?? '' }}</p>
      </div>
      <p class="mt-1 text-xs text-gray-400">{{ $n->created_at->diffForHumans() }}</p>
    </div>
  @empty
    <p class="text-sm text-gray-500 py-4 text-center">No notifications yet.</p>
  @endforelse
</x-filament::section>
