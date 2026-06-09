<x-filament-panels::page>
  <style>
    @media print {
      @page {
        margin: 0.3in;
      }
      body * {
        visibility: hidden;
      }
      #printElement, #printElement * {
        visibility: visible;
      }
      #printElement {
        position: fixed;
        inset: 0;
        padding: 0.3in;
        background: #fff;
      }
      #printElement .grid > div {
        break-inside: avoid;
        page-break-inside: avoid;
      }
      #printElement {
        overflow: visible !important;
        max-height: none !important;
        height: auto !important;
      }
    }
  </style>

  <div class="flex flex-col gap-4 lg:flex-row">
    <x-filament::section class="w-full lg:w-1/2">
      <div class="h-96 overflow-auto lg:h-screen" id="printElement">
        <div
          style="column-gap: {{ $data['horizontal_gap'] ?? 0 }}px; row-gap: {{ $data['vertical_gap'] ?? 0 }}px;"
          class="grid grid-cols-{{ $data['items_per_row'] ?? 3 }}">
          @foreach($products as $product)
            <div class="break-inside-avoid border border-black px-2 py-2 text-center">
              <p class="text-center text-sm font-bold">{{ $product['name'] }}</p>
              <p class="text-xs">{{ $product['price'] }}</p>
              <div class="flex justify-center">
                {!!$product['barcode_html']!!}
              </div>
              <p class="text-xs">{{ $product['barcode'] }}</p>
            </div>
          @endforeach
        </div>
      </div>
    </x-filament::section>
    <div class="w-full lg:w-1/2">
      {{ $this->form }}
    </div>
  </div>
</x-filament-panels::page>
@script()
<script>
  document.getElementById('printLabelButton')?.addEventListener('click', async () => {
    await $wire.applySetting();
    window.print();
  });
</script>
@endscript
