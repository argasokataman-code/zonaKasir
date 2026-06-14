<x-filament-panels::page>
  <div class="space-y-4">

    {{-- Disk Usage Warning --}}
    @if($diskWarning)
      <div class="rounded-lg border border-danger-300 bg-danger-50 p-4 dark:border-danger-700 dark:bg-danger-950/30">
        <div class="flex items-center gap-3">
          <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-danger-500" />
          <div>
            <p class="font-semibold text-danger-700 dark:text-danger-400">Disk Space Warning</p>
            <p class="text-sm text-danger-600 dark:text-danger-300">
              Storage is {{ $diskUsedPercent }}% full ({{ $diskUsed }} / {{ $diskTotal }}).
              Consider clearing old log files.
            </p>
          </div>
        </div>
      </div>
    @endif

    {{-- Disk Usage Bar --}}
    <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
      <div class="mb-2 flex items-center justify-between text-sm">
        <span class="font-medium text-gray-700 dark:text-gray-300">Disk Usage</span>
        <span class="{{ $diskWarning ? 'text-danger-600 font-semibold' : 'text-gray-500' }}">
          {{ $diskUsed }} / {{ $diskTotal }} ({{ $diskUsedPercent }}%)
        </span>
      </div>
      <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
        <div
          class="h-full rounded-full transition-all duration-300 {{ $diskWarning ? 'bg-danger-500' : 'bg-primary-500' }}"
          style="width: {{ min($diskUsedPercent, 100) }}%"
        ></div>
      </div>
    </div>

    {{-- Controls --}}
    <div class="flex flex-wrap items-center gap-4">
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

      <x-filament::button
        icon="heroicon-m-trash"
        color="danger"
        wire:click="deleteSelected"
        wire:confirm="Delete {{ $selectedLog }}.log?"
      >
        Delete Selected
      </x-filament::button>

      <x-filament::button
        icon="heroicon-m-no-symbol"
        color="danger"
        wire:click="clearAllLogs"
        wire:confirm="Delete ALL log files? This cannot be undone."
        outlined
      >
        Clear All Logs
      </x-filament::button>
    </div>

    {{-- Log Content --}}
    <pre class="overflow-auto rounded-lg bg-gray-900 p-4 text-xs leading-relaxed text-green-400" style="max-height: 70vh; white-space: pre-wrap; word-break: break-all;">{{ $log }}</pre>
  </div>
</x-filament-panels::page>
