<x-filament-panels::page>
  <div class="space-y-4">
    <div class="flex items-center gap-4">
      <x-filament::input.wrapper class="w-64">
        <x-filament::input.select wire:model.live="selectedLog" wire:change="refresh">
          @foreach($logFiles as $key => $label)
            <option value="{{ $key }}">{{ $label }}</option>
          @endforeach
        </x-filament::input.select>
      </x-filament::input.wrapper>

      <x-filament::input.wrapper class="w-32">
        <x-filament::input.select wire:model.live="lines" wire:change="refresh">
          <option value="50">50 lines</option>
          <option value="100">100 lines</option>
          <option value="200">200 lines</option>
          <option value="500">500 lines</option>
        </x-filament::input.select>
      </x-filament::input.wrapper>

      <x-filament::button icon="heroicon-m-arrow-path" wire:click="refresh">
        Refresh
      </x-filament::button>
    </div>

    <pre class="overflow-auto rounded-lg bg-gray-900 p-4 text-xs leading-relaxed text-green-400" style="max-height: 70vh; white-space: pre-wrap; word-break: break-all;">
{{ $log }}
    </pre>
  </div>
</x-filament-panels::page>
