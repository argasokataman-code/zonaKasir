<x-filament-panels::page>
    <x-filament-panels::form
        id="form"
        wire:key="{{ 'forms.' . $this->getFormStatePath() }}"
    >
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    <div id="printable-element" class="max-w-full space-y-2">
      @if($reports)
        @include('reports.partials.selling-data', [
          'header' => $reports['header'],
          'reports' => $reports['reports'],
          'footer' => $reports['footer'],
        ])
      @endif
    </div>
</x-filament-panels::page>
@script()
<script>
  document.getElementById('print-btn').addEventListener('click', () => {
    const printContents = document.getElementById("printable-element").innerHTML;
    const printWindow = window.open('', '', 'height=400,width=800');
    printWindow.document.write(printContents);
    printWindow.document.close();
    printWindow.print();
  });
</script>
@endscript
