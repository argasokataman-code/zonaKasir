<x-filament-panels::page>
  <x-filament::card>
    {{ $this->form }}

    <div class="mt-6">
      <x-filament::button wire:click="send" color="primary" icon="heroicon-m-megaphone">
        Send Notification
      </x-filament::button>
    </div>
  </x-filament::card>
</x-filament-panels::page>
