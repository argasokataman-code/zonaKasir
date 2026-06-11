<x-filament-panels::page>
  <x-filament::card>
    <div class="mb-4">
      <x-filament::button wire:click="check" color="gray" icon="heroicon-m-magnifying-glass" class="mb-4">
        Refresh Data
      </x-filament::button>
    </div>

    @if(count($results))
      <div class="rounded-lg bg-gray-900 p-4 text-xs font-mono text-green-400 space-y-1">
        @foreach($results as $line)
          <div>{{ $line }}</div>
        @endforeach
      </div>
    @else
      <p class="text-sm text-gray-500">Click "Refresh Data" to check notification status.</p>
    @endif
  </x-filament::card>
</x-filament-panels::page>
