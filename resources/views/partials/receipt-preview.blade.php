<div>
  <x-filament::modal id="receipt-preview-modal" width="md" :close-by-clicking-away="false" :close-by-escaping="false">
    <div class="flex justify-center" style="margin-top: -1px;">
      <div id="receiptPreviewContent" class="bg-white max-w-sm w-full"
        style="font-family: 'Courier New', Courier, monospace; font-size: 12px; line-height: 1.5; padding: 20px 16px 12px; box-shadow: 0 0 0 1px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.12);">
      </div>
    </div>
    <x-slot name="footer">
      <div class="flex gap-x-2 justify-end">
        <x-filament::button color="gray" x-on:click="$dispatch('close-modal', {id: 'receipt-preview-modal'})">
          {{ __('Cancel') }}
        </x-filament::button>
        <x-filament::button icon="heroicon-m-printer" id="confirmPrintButton">
          {{ __('Print Now') }}
        </x-filament::button>
      </div>
    </x-slot>
  </x-filament::modal>
</div>
