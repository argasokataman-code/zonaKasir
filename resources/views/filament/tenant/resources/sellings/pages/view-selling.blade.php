@php
  use App\Features\{SellingTax};
  use App\Models\Tenants\{Profile, Setting, About};

  $about = $about ?? About::first();
  $currency = Setting::get('currency', 'IDR');
@endphp
<x-filament-panels::page>
  <style>
    .print-only {
      display: none;
    }
    .print-hide {
      display: block;
    }
    @media print {
      body * {
        visibility: hidden;
      }
      #printElement, #printElement * {
        visibility: visible;
      }
      #printElement {
        position: fixed;
        inset: 0;
        padding: 0.75in 0.5in;
        background: #fff;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        font-size: 10pt;
        line-height: 1.5;
        color: #1a1a1a;
      }
      .print-only {
        display: block !important;
      }
      .print-hide {
        display: none !important;
      }
      #printElement .print-header {
        text-align: center;
        margin-bottom: 20pt;
        padding-bottom: 12pt;
        border-bottom: 2px solid #1a1a1a;
      }
      #printElement .print-header h1 {
        font-size: 16pt;
        font-weight: 700;
        margin: 0 0 2pt;
        text-transform: uppercase;
        letter-spacing: 1pt;
      }
      #printElement .print-header p {
        margin: 0;
        font-size: 9pt;
        color: #555;
      }
      #printElement .print-meta {
        display: flex;
        justify-content: space-between;
        margin-bottom: 16pt;
        font-size: 9pt;
      }
      #printElement .print-meta div {
        flex: 1;
      }
      #printElement .print-meta div:last-child {
        text-align: right;
      }
      #printElement .print-meta strong {
        display: inline-block;
        min-width: 70pt;
      }
      #printElement .invoice-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 16pt;
        font-size: 9pt;
      }
      #printElement .invoice-table th {
        background: #f5f5f5;
        padding: 6pt 8pt;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #1a1a1a;
        text-transform: uppercase;
        font-size: 8pt;
        letter-spacing: 0.5pt;
      }
      #printElement .invoice-table th:last-child,
      #printElement .invoice-table td:last-child {
        text-align: right;
      }
      #printElement .invoice-table td {
        padding: 5pt 8pt;
        border-bottom: 1px solid #e0e0e0;
      }
      #printElement .invoice-table tbody tr:last-child td {
        border-bottom: 2px solid #1a1a1a;
      }
      #printElement .invoice-table td:last-child {
        font-weight: 600;
      }
      #printElement .invoice-table tfoot td {
        padding: 4pt 8pt;
        border: none;
        font-weight: 600;
      }
      #printElement .invoice-table tfoot tr:last-child td {
        font-size: 12pt;
        padding-top: 6pt;
      }
      #printElement .invoice-footer {
        text-align: center;
        font-size: 8pt;
        color: #888;
        margin-top: 20pt;
        padding-top: 10pt;
        border-top: 1px solid #ddd;
      }
    }
  </style>

  <x-filament::section id="printElement">
    {{-- Screen-only header --}}
    <div class="print-hide">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div>
          <p class="font-semibold text-xl lg:text-2xl">@lang('Selling details')</p>
          <ul class="my-1 space-y-1">
            <li class="flex justify-between text-sm"><span class="font-semibold">@lang('Code')</span><span>#{{ $record->code }}</span></li>
            @if($about && $about->business_type == 'fnb')
              <li class="flex justify-between text-sm"><span class="font-semibold">@lang('Table')</span><span>{{ $record->table?->number ?? 'N/A' }}</span></li>
            @endif
            <li class="flex justify-between text-sm"><span class="font-semibold">@lang('Cashier')</span><span>{{ $record->user?->name ?? 'N/A' }}</span></li>
            <li class="flex justify-between text-sm"><span class="font-semibold">@lang('Date')</span><span>{{ now()->parse($record->date)->setTimezone(Profile::get()->timezone ?? 'UTC')->format('d F Y H:i') }}</span></li>
            <li class="flex justify-between text-sm"><span class="font-semibold">@lang('Payment method')</span><span>{{ $record->paymentMethod?->name ?? 'N/A' }}</span></li>
            <li class="flex justify-between text-sm"><span class="font-semibold">@lang('Voucher')</span><span>{{ $record->voucher ?? 'N/A' }}</span></li>
          </ul>
        </div>
        <div>
          <p class="font-semibold text-xl lg:text-2xl">@lang('Member details')</p>
          <ul class="my-1 space-y-1">
            <li class="flex justify-between text-sm"><span class="font-semibold">@lang('Name')</span><span>{{ $record->member?->name ?? 'N/A' }}</span></li>
            <li class="flex justify-between text-sm"><span class="font-semibold">@lang('Code')</span><span>{{ $record->member?->code ?? 'N/A' }}</span></li>
            <li class="flex justify-between text-sm"><span class="font-semibold">@lang('Joined date')</span><span>{{ $record->member?->joined_date ? now()->parse($record->member->joined_date)->setTimezone(Profile::get()->timezone ?? 'UTC')->format('d F Y') : 'N/A' }}</span></li>
            <li class="flex justify-between text-sm"><span class="font-semibold">@lang('Identity type')</span><span>{{ $record->member?->identity_type ?? 'N/A' }}</span></li>
            <li class="flex justify-between text-sm"><span class="font-semibold">@lang('Identity number')</span><span>{{ $record->member?->identity_number ?? 'N/A' }}</span></li>
            <li class="flex justify-between text-sm"><span class="font-semibold">@lang('Contact')</span><span>{{ $record->member?->email ?? 'N/A' }}</span></li>
            <li class="flex justify-between text-sm"><span class="font-semibold">@lang('Address')</span><span>{{ $record->member?->address ?? 'N/A' }}</span></li>
          </ul>
        </div>
      </div>
    </div>

    {{-- Print-only header --}}
    <div class="print-header print-only">
      <h1>{{ $about?->shop_name ?? config('app.name') }}</h1>
      <p>{{ $about?->shop_location ?? '' }}</p>
      <p>{{ $about?->shop_phone ?? '' }}</p>
    </div>

    {{-- Print-only meta --}}
    <div class="print-meta print-only">
      <div>
        <strong>@lang('Invoice'):</strong> #{{ $record->code }}<br>
        <strong>@lang('Date'):</strong> {{ now()->parse($record->date)->setTimezone(Profile::get()->timezone ?? 'UTC')->format('d M Y H:i') }}<br>
        <strong>@lang('Cashier'):</strong> {{ $record->user?->name ?? 'N/A' }}
      </div>
      <div>
        <strong>@lang('Customer'):</strong> {{ $record->member?->name ?? 'Walk-in' }}<br>
        <strong>@lang('Payment'):</strong> {{ $record->paymentMethod?->name ?? 'N/A' }}
      </div>
    </div>

    {{-- Product table --}}
    <div class="overflow-x-auto -mx-2 lg:mx-0">
      <table class="invoice-table w-full">
        <thead>
          <tr>
            <th class="p-2 border text-sm">@lang('Product')</th>
            <th class="p-2 border text-sm text-center">@lang('Price')</th>
            <th class="p-2 border text-sm text-center">@lang('Qty')</th>
            <th class="p-2 border text-sm text-center">@lang('Discount')</th>
            <th class="p-2 border text-sm text-right">@lang('Total')</th>
          </tr>
        </thead>
        <tbody>
          @foreach($record->sellingDetails as $detail)
            <tr>
              <td class="p-2 border">
                <span class="font-medium text-sm">{{ $detail->product?->name ?? '' }}</span>
              </td>
              <td class="p-2 border text-center text-sm">{{ Number::currency($detail->price_per_unit, $currency) }}</td>
              <td class="p-2 border text-center text-sm">{{ $detail->qty }}</td>
              <td class="p-2 border text-center text-sm">{{ $detail->discount_price > 0 ? Number::currency($detail->discount_price, $currency) : '-' }}</td>
              <td class="p-2 border text-right text-sm font-semibold">{{ Number::currency($detail->total_price, $currency) }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4" class="p-1.5 border text-sm text-right">@lang('Subtotal')</td>
            <td class="p-1.5 border text-right text-sm">{{ Number::currency($record->total_price, $currency) }}</td>
          </tr>
          @if($record->total_discount_per_item + $record->discount_price > 0)
            <tr>
              <td colspan="4" class="p-1.5 border text-sm text-right">@lang('Discount')</td>
              <td class="p-1.5 border text-right text-sm text-danger-600">-{{ Number::currency($record->total_discount_per_item + $record->discount_price, $currency) }}</td>
            </tr>
          @endif
          @feature(SellingTax::class)
            <tr>
              <td colspan="4" class="p-1.5 border text-sm text-right">@lang('Tax') ({{ $record->tax }}%)</td>
              <td class="p-1.5 border text-right text-sm">{{ Number::currency($record->tax_price, $currency) }}</td>
            </tr>
          @endfeature
          <tr class="font-bold">
            <td colspan="4" class="p-1.5 border text-sm text-right">@lang('Grand Total')</td>
            <td class="p-1.5 border text-right text-sm text-lg">{{ Number::currency($record->grand_total_price, $currency) }}</td>
          </tr>
          <tr>
            <td colspan="4" class="p-1.5 border text-sm text-right">@lang('Payed')</td>
            <td class="p-1.5 border text-right text-sm">{{ Number::currency($record->payed_money, $currency) }}</td>
          </tr>
          <tr>
            <td colspan="4" class="p-1.5 border text-sm text-right">@lang('Change')</td>
            <td class="p-1.5 border text-right text-sm">{{ Number::currency($record->money_changes, $currency) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>

    {{-- Print-only footer --}}
    <div class="invoice-footer print-only">
      <p>@lang('Thank you for your business!')</p>
      @if($about?->shop_location)
        <p>{{ $about->shop_location }}</p>
      @endif
    </div>
  </x-filament::section>

  @include('partials.receipt-preview')
</x-filament-panels::page>

@script()
<script>
  document.getElementById('printInvoice')?.addEventListener('click', () => {
    window.print();
  });

  async function doPrintReceiptViewSelling() {
    let selling = @js($record);
    let about = @js($about);
    const printerData = getPrinter();
    if (!printerData || printerData instanceof Error) {
      new FilamentNotification()
        .title('@lang('You should choose the printer first in printer setting')')
        .danger()
        .actions([
          new FilamentNotificationAction('Setting')
            .icon('heroicon-o-cog-6-tooth')
            .button()
            .url('/member/printer'),
        ])
        .send()
      return;
    }
    const printer = new Printer(printerData.printerId);
    let p = printer.font('a');
    if (about != undefined || about != null) {
      p.size(1).align('center').text(about.shop_name).size(0).text(about.shop_location);
      if (printerData.header != undefined) p.text(printerData.header);
      p.align('left').text('-------------------------------');
    }
    p.table(['@lang('Cashier')', selling.user.name])
    if (selling.table != undefined && selling.table != null) p.table(['@lang('Table')', selling.table.number])
    p.table(['@lang('Payment method')', selling.payment_method.name]);
    if (selling.member != undefined && selling.member != null) p.table(['Member', selling.member.name]);
    p.text('-------------------------------');
    selling.selling_details.forEach(d => {
      p.table([d.product.name, moneyFormat(d.price / d.qty) + ' x ' + d.qty.toString()])
      if (d.discount_price > 0) {
        p.align('right').text(`(${moneyFormat(d.discount_price)})`)
      }
      p.align('right').text(moneyFormat(d.price)).align('left')
    });
    p.text('-------------------------------');
    if ("@js(feature(SellingTax::class))" == 'true') {
      p.table(['@lang('Tax')', `${selling.tax}%`]).table(['@lang('Tax price')', moneyFormat(selling.tax_price)]);
    }
    p.table(['@lang('Subtotal')', moneyFormat(selling.total_price)])
      .table(['@lang('Discount')', `(${moneyFormat(selling.total_discount_per_item + selling.discount_price)})`])
      .table(['@lang('Total price')', moneyFormat(selling.grand_total_price)])
      .text('-------------------------------')
      .table(['@lang('Payed money')', moneyFormat(selling.payed_money)])
      .table(['@lang('Change')', moneyFormat(selling.money_changes)])
      .align('center');
    if (printerData.footer != undefined) p.text(printerData.footer);
    p.align('left').text('copy');
    await p.cut().print();
  }

  function previewHtml(selling, about, printerData) {
    const line = '\u2500'.repeat(31);
    let h = '';
    const esc = (s) => s == null ? '' : String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    if (about) {
      h += `<div style="text-align:center;font-size:14px;font-weight:700;margin-bottom:2px">${esc(about.shop_name)}</div>`;
      if (about.shop_location) h += `<div style="text-align:center;font-size:11px;margin-bottom:2px">${esc(about.shop_location)}</div>`;
      if (printerData?.header) h += `<div style="text-align:center;font-size:11px">${esc(printerData.header)}</div>`;
    }
    h += `<div style="text-align:center;letter-spacing:.2em;margin:6px 0">${line}</div>`;
    if (selling.user?.name) h += `<div style="display:flex;justify-content:space-between"><span>Cashier</span><span>${esc(selling.user.name)}</span></div>`;
    if (selling.table?.number) h += `<div style="display:flex;justify-content:space-between"><span>Table</span><span>${esc(selling.table.number)}</span></div>`;
    if (selling.payment_method?.name) h += `<div style="display:flex;justify-content:space-between"><span>Payment</span><span>${esc(selling.payment_method.name)}</span></div>`;
    if (selling.member?.name) h += `<div style="display:flex;justify-content:space-between"><span>Member</span><span>${esc(selling.member.name)}</span></div>`;
    h += `<div style="text-align:center;letter-spacing:.2em;margin:6px 0">${line}</div>`;
    const details = selling.selling_details || selling.details || [];
    details.forEach(d => {
      const qty = d.qty || 1;
      const ppu = d.price_per_unit || (d.price ? d.price / qty : 0);
      const nm = d.product?.name || d.name || 'Product';
      h += `<div style="display:flex;justify-content:space-between"><span>${esc(nm)}</span><span>${moneyFormat(ppu)} x ${qty}</span></div>`;
      if (d.discount_price > 0) h += `<div style="text-align:right">(${moneyFormat(d.discount_price)})</div>`;
      h += `<div style="text-align:right;font-weight:600">${moneyFormat(d.price || d.total_price || 0)}</div>`;
    });
    h += `<div style="text-align:center;letter-spacing:.2em;margin:6px 0">${line}</div>`;
    if (selling.tax > 0) {
      h += `<div style="display:flex;justify-content:space-between"><span>Tax</span><span>${selling.tax}%</span></div>`;
      h += `<div style="display:flex;justify-content:space-between"><span>Tax price</span><span>${moneyFormat(selling.tax_price)}</span></div>`;
    }
    h += `<div style="display:flex;justify-content:space-between"><span>Subtotal</span><span>${moneyFormat(selling.total_price)}</span></div>`;
    const discount = (selling.total_discount_per_item || 0) + (selling.discount_price || 0);
    if (discount > 0) h += `<div style="display:flex;justify-content:space-between"><span>Discount</span><span>(${moneyFormat(discount)})</span></div>`;
    h += `<div style="display:flex;justify-content:space-between;font-weight:700"><span>Total price</span><span>${moneyFormat(selling.grand_total_price)}</span></div>`;
    h += `<div style="text-align:center;letter-spacing:.2em;margin:6px 0">${line}</div>`;
    h += `<div style="display:flex;justify-content:space-between"><span>Payed money</span><span>${moneyFormat(selling.payed_money)}</span></div>`;
    h += `<div style="display:flex;justify-content:space-between"><span>Change</span><span>${moneyFormat(selling.money_changes)}</span></div>`;
    if (printerData?.footer) h += `<div style="text-align:center;margin-top:4px">${esc(printerData.footer)}</div>`;
    h += `<div style="font-size:10px;margin-top:2px">copy</div>`;
    return h;
  }

  document.getElementById('printButton')?.addEventListener('click', async () => {
    let selling = @js($record);
    let about = @js($about);
    const pd = getPrinter();
    const preview = previewHtml(selling, about, pd instanceof Error ? null : pd);
    document.getElementById('receiptPreviewContent').innerHTML = preview;
    $wire.dispatch('open-modal', {id: 'receipt-preview-modal'});
  });

  document.addEventListener('click', async (e) => {
    if (e.target.id === 'confirmPrintButton' || e.target.closest('#confirmPrintButton')) {
      await doPrintReceiptViewSelling();
      $wire.dispatch('close-modal', {id: 'receipt-preview-modal'});
    }
  });
</script>
@endscript
