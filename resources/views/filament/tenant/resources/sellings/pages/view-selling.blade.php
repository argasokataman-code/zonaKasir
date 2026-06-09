@php
  use App\Features\{SellingTax};
  use App\Models\Tenants\{Profile, Setting, About};
@endphp
<x-filament-panels::page>
  <x-filament::section id="printElement">
    <div class="flex">
      <div class="w-full print:w-1/3 md:w-1/3 px-2">
        <div>
          <p class="font-semibold text-2xl">@lang('Selling details')</p>
          <div class="details">
            <ul class="my-1">
              <li class="flex justify-between text-secondary text-sm mb-1"><span class="font-semibold">@lang('Code')</span><span>#{{ $record->code }}</span></li>
              @if(About::first() && About::first()->business_type == 'fnb')
                <li class="flex justify-between text-secondary text-sm mb-1"><span class="font-semibold">@lang('Table')</span><span>{{ $record->table?->number ?? 'N/A' }}</span></li>
              @endif
              <li class="flex justify-between text-secondary text-sm mb-1"><span class="font-semibold">@lang('Cashier')</span><span>{{ $record->user?->name ?? 'N/A' }}</span></li>
              <li class="flex justify-between text-secondary text-sm mb-1"><span class="font-semibold">@lang('Date')</span><span>{{ now()->parse($record->date)->setTimezone(Profile::get()->timezone ?? 'UTC')->format('d F Y H:i') }}</span></li>
              <li class="flex justify-between text-secondary text-sm mb-1"><span class="font-semibold">@lang('Payment method')</span><span>{{ $record->paymentMethod?->name ?? 'N/A' }}</span></li>
              <li class="flex justify-between text-secondary text-sm mb-1"><span class="font-semibold">@lang('Voucher')</span><span>{{ $record->voucher ?? 'N/A' }}</span></li>
            </ul>
          </div>
        </div>
      </div>
      <div class="w-full print:w-1/3 md:w-1/3 px-2">
        <div>
          <p class="font-semibold text-2xl">@lang('Member details')</p>
          <div class="details">
            <ul class="my-1">
              <li class="flex justify-between text-secondary text-sm mb-1"><span class="font-semibold">@lang('Name')</span><span>{{ $record->member?->name ?? 'N/A' }}</span></li>
              <li class="flex justify-between text-secondary text-sm mb-1"><span class="font-semibold">@lang('Code')</span><span>{{ $record->member?->code ?? 'N/A' }}</span></li>
              <li class="flex justify-between text-secondary text-sm mb-1"><span class="font-semibold">@lang('Joined date')</span><span>{{ $record->member?->joined_date ? now()->parse($record->member?->joined_date)->setTimezone(Profile::get()->timezone ?? 'UTC')->format('d F Y H:i') : 'N/A' }}</span></li>
              <li class="flex justify-between text-secondary text-sm mb-1"><span class="font-semibold">@lang('Identity type')</span><span>{{ $record->member?->identity_type ?? 'N/A' }}</span></li>
              <li class="flex justify-between text-secondary text-sm mb-1"><span class="font-semibold">@lang('Identity number')</span><span>{{ $record->member?->identity_number ?? 'N/A' }}</span></li>
              <li class="flex justify-between text-secondary text-sm mb-1"><span class="font-semibold">@lang('Contact')</span><span>{{ $record->member?->email ?? 'N/A' }}</span></li>
              <li class="flex justify-between text-secondary text-sm mb-1"><span class="font-semibold">@lang('Address')</span><span>{{ $record->member?->address ?? 'N/A' }}</span></li>
              <!---->
            </ul>
          </div>
        </div>
      </div>
    </div>
    <div class="table w-full my-4">
      <table class="table ns-table w-full">
        <thead class="text-secondary">
          <tr>
            <th width="400" class="p-2 border">@lang('Product')</th>
            <th width="200" class="p-2 border">@lang('Unit price')</th>
            <th width="200" class="p-2 border">@lang('Quantity')</th>
            <th width="200" class="p-2 border">@lang('Discount')</th>
            <th width="200" class="p-2 border">@lang('Total price')</th>
          </tr>
        </thead>
        <tbody>
          @foreach($record->sellingDetails as $detail)
            <tr>
              <td class="p-2 border">
                <h3 class="text-primary">{{ $detail->product?->name ?? '' }}</h3><span class="text-sm text-secondary"></span></td>
              <td class="p-2 border text-center text-primary">{{ Number::currency($detail->price_per_unit, Setting::get('currency', 'IDR')) }}</td>
              <td class="p-2 border text-center text-primary">{{ $detail->qty }}</td>
              <td class="p-2 border text-center text-primary">{{ Number::currency($detail->discount_price, Setting::get('currency', 'IDR')) }}</td>
              <td class="p-2 border text-center text-primary">{{ Number::currency($detail->total_price, Setting::get('currency', 'IDR')) }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot class="font-semibold">
          <tr>
            <td class="p-2 border text-center text-primary" colspan="3"></td>
            <td class="p-2 border text-primary text-left">@lang('Subtotal')</td>
            <td class="p-2 border text-right text-primary">{{ Number::currency($record->total_price, Setting::get('currency', 'IDR')) }}</td>
          </tr>
          <tr>
            <td class="p-2 border text-center text-primary" colspan="3"></td>
            <td class="p-2 border text-primary text-left">@lang('Discount')</td>
            <td class="p-2 border text-right text-primary">{{ Number::currency($record->total_discount_per_item + $record->discount_price, Setting::get('currency', 'IDR')) }}</td>
          </tr>
          <!---->
          <tr>
            <td class="p-2 border text-center text-primary" colspan="3"></td>
            <td class="p-2 border text-primary text-left">@lang('Tax')</td>
            <td class="p-2 border text-right text-primary">{{ $record->tax }}%</td>
          </tr>
          <tr>
            <td class="p-2 border text-center text-primary" colspan="3"></td>
            <td class="p-2 border text-primary text-left">@lang('Tax price')</td>
            <td class="p-2 border text-right text-primary">{{ Number::currency($record->tax_price, Setting::get('currency', 'IDR')) }}</td>
          </tr>
          <tr>
            <td class="p-2 border text-center text-primary" colspan="3"></td>
            <td class="p-2 border text-primary text-left">@lang('Total')</td>
            <td class="p-2 border text-right text-primary">{{ Number::currency($record->grand_total_price, Setting::get('currency', 'IDR')) }}</td>
          </tr>
          <tr>
            <td class="p-2 border text-center text-primary" colspan="3"></td>
            <td class="p-2 border text-primary text-left">@lang('Payed money')</td>
            <td class="p-2 border text-right text-primary">{{ Number::currency($record->payed_money, Setting::get('currency', 'IDR')) }}</td>
          </tr>
          <tr>
            <td class="p-2 border text-center text-primary" colspan="3"></td>
            <td class="p-2 border text-primary text-left">@lang('Money changes')</td>
            <td class="p-2 border text-right text-primary">{{ Number::currency($record->money_changes, Setting::get('currency', 'IDR')) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </x-filament::section>

  @include('partials.receipt-preview')
</x-filament-panels::page>
@script()
<script>
  console.log(@js($record));
  document.getElementById('printInvoice').addEventListener('click', () => {
    const printContents = document.getElementById("printElement").innerHTML;
    const originalContents = document.body.innerHTML;


    document.body.innerHTML = printContents;

    window.print();

    window.location.reload();
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
    if(about != undefined || about != null) {
      p.size(1).align('center').text(about.shop_name).size(0).text(about.shop_location);
      if(printerData.header != undefined) p.text(printerData.header);
      p.align('left').text('-------------------------------');
    }
    p.table(['@lang('Cashier')', selling.user.name])
    if(selling.table != undefined && selling.table != null) p.table(['@lang('Table')', selling.table.number])
    p.table(['@lang('Payment method')', selling.payment_method.name]);
    if(selling.member != undefined && selling.member != null) p.table(['Member', selling.member.name]);
    p.text('-------------------------------');
    selling.selling_details.forEach(d => {
      p.table([d.product.name, moneyFormat(d.price / d.qty) + ' x ' + d.qty.toString()])
      if (d.discount_price > 0) {
        p.align('right').text(`(${moneyFormat(d.discount_price)})`)
      }
      p.align('right').text(moneyFormat(d.price)).align('left')
    });
    p.text('-------------------------------');
    if("@js(feature(SellingTax::class))" == 'true') {
      p.table(['@lang('Tax')', `${selling.tax}%`]).table(['@lang('Tax price')', moneyFormat(selling.tax_price)]);
    }
    p.table(['@lang('Subtotal')', moneyFormat(selling.total_price)])
      .table(['@lang('Discount')', `(${moneyFormat(selling.total_discount_per_item + selling.discount_price)})`])
      .table(['@lang('Total price')', moneyFormat(selling.grand_total_price)])
      .text('-------------------------------')
      .table(['@lang('Payed money')', moneyFormat(selling.payed_money)])
      .table(['@lang('Change')', moneyFormat(selling.money_changes)])
      .align('center');
    if(printerData.footer != undefined) p.text(printerData.footer);
    p.align('left').text('copy');
    await p.cut().print();
  }

  function previewHtml(selling, about, printerData) {
    const line = '─'.repeat(31);
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

  document.getElementById('printButton').addEventListener('click', async () => {
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
