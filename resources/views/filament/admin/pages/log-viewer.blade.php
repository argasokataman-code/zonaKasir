<x-filament-panels::page>
  <div class="space-y-4" x-data="{ autoRefresh: true }" x-init="setInterval(() => { if (autoRefresh) $wire.refresh() }, 5000)">

    {{-- Disk Usage Warning --}}
    @if($diskWarning)
      <div class="rounded-lg border border-danger-300 bg-danger-50 p-4 dark:border-danger-700 dark:bg-danger-950/30">
        <div class="flex items-center gap-3">
          <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-danger-500 flex-shrink-0" />
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
    <div class="flex flex-wrap items-center gap-3">
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

      <x-filament::button icon="heroicon-m-arrow-path" wire:click="refresh" color="gray">
        Refresh
      </x-filament::button>

      <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
        <input type="checkbox" x-model="autoRefresh" class="rounded border-gray-300" />
        Auto-refresh (5s)
      </label>

      <div class="flex-1"></div>

      <x-filament::button
        icon="heroicon-m-no-symbol"
        color="danger"
        wire:click="clearAllLogs"
        wire:confirm="Delete ALL log files? This cannot be undone."
      >
        Clear All Logs
      </x-filament::button>
    </div>

    {{-- Stats --}}
    <div class="flex gap-4 text-sm">
      <span class="text-gray-500">Entries: <strong>{{ $totalEntries }}</strong></span>
      @if($errorCount > 0)
        <span class="text-danger-600 font-semibold">Errors: {{ $errorCount }}</span>
      @endif
      @if($warningCount > 0)
        <span class="text-warning-600 font-semibold">Warnings: {{ $warningCount }}</span>
      @endif
    </div>

    {{-- Log Entries --}}
    <div class="overflow-auto rounded-lg bg-gray-900 p-0" style="max-height: 70vh;">
      @if(empty($logEntries))
        <div class="p-8 text-center text-gray-400">
          @if($logRaw === 'File not found.')
            Log file not found.
          @else
            No log entries found.
          @endif
        </div>
      @else
        <table class="w-full text-xs">
          <thead class="sticky top-0 bg-gray-800 text-gray-400">
            <tr>
              <th class="px-3 py-2 text-left font-medium w-40">Time</th>
              <th class="px-3 py-2 text-left font-medium w-20">Level</th>
              <th class="px-3 py-2 text-left font-medium">Message</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-800/50">
            @foreach($logEntries as $entry)
              @if($entry['level'] === 'error')
                <tr class="bg-danger-950/20 hover:bg-danger-950/30">
              @elseif($entry['level'] === 'warning')
                <tr class="bg-warning-950/10 hover:bg-warning-950/20">
              @elseif($entry['level'] === 'info')
                <tr class="hover:bg-gray-800/30">
              @elseif($entry['level'] === 'debug')
                <tr class="opacity-50 hover:opacity-75">
              @else
                <tr class="hover:bg-gray-800/30">
              @endif
                <td class="px-3 py-1.5 whitespace-nowrap text-gray-500">{{ $entry['time'] }}</td>
                <td class="px-3 py-1.5">
                  @if($entry['level'] === 'error')
                    <span class="inline-flex items-center rounded-full bg-danger-500/20 px-2 py-0.5 font-semibold text-danger-400">ERROR</span>
                  @elseif($entry['level'] === 'warning')
                    <span class="inline-flex items-center rounded-full bg-warning-500/20 px-2 py-0.5 font-semibold text-warning-400">WARN</span>
                  @elseif($entry['level'] === 'info')
                    <span class="inline-flex items-center rounded-full bg-info-500/20 px-2 py-0.5 font-semibold text-info-400">INFO</span>
                  @elseif($entry['level'] === 'debug')
                    <span class="inline-flex items-center rounded-full bg-gray-500/20 px-2 py-0.5 font-semibold text-gray-400">DEBUG</span>
                  @elseif($entry['level'] === 'critical')
                    <span class="inline-flex items-center rounded-full bg-danger-600/30 px-2 py-0.5 font-bold text-danger-300">CRITICAL</span>
                  @else
                    <span class="inline-flex items-center rounded-full bg-gray-500/20 px-2 py-0.5 text-gray-400">{{ strtoupper($entry['level']) }}</span>
                  @endif
                </td>
                <td class="px-3 py-1.5">
                  <div class="text-gray-300 break-all">{{ $entry['message'] }}</div>
                  @if($entry['stack'] !== '')
                    <details class="mt-1 group">
                      <summary class="cursor-pointer text-gray-500 hover:text-gray-400 text-[11px] select-none">Stack trace</summary>
                      <pre class="mt-1 overflow-x-auto whitespace-pre-wrap text-[11px] leading-relaxed text-gray-500">{{ rtrim($entry['stack']) }}</pre>
                    </details>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>
  </div>
</x-filament-panels::page>
