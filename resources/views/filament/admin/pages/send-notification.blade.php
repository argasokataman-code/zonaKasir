<x-filament-panels::page>
  <x-filament::card>
    {{ $this->form }}

    <div class="mt-6">
      <x-filament::button
        wire:click="send"
        wire:loading.attr="disabled"
        wire:target="send"
        color="primary"
        icon="heroicon-m-megaphone"
      >
        <span wire:loading.remove wire:target="send">Send Notification</span>
        <span wire:loading wire:target="send">Sending...</span>
      </x-filament::button>
    </div>
  </x-filament::card>
</x-filament-panels::page>
