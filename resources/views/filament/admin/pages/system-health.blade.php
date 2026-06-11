<x-filament-panels::page>
  <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
    @foreach($checks as $key => $check)
      <div class="rounded-2xl border p-5 {{ $check['ok'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-500">{{ $check['label'] }}</span>
          @if($check['ok'])
            <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          @else
            <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          @endif
        </div>
        <p class="text-lg font-semibold {{ $check['ok'] ? 'text-green-800' : 'text-red-800' }}">{{ $check['value'] }}</p>
      </div>
    @endforeach
  </div>
</x-filament-panels::page>
