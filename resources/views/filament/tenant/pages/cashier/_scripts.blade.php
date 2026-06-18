@script()
<script>
  window.zonakasirCurrency = @js($currency);
  window.zonakasirLocale = @js($locale);
  if (typeof window.getPrinter !== 'function') {
    window.getPrinter = function() {
      if (localStorage.printer == undefined) {
        console.error('printer didn\'t set');
        return Error('printer didn\'t set');
      }
      return JSON.parse(localStorage.printer);
    }
  }
  let selling = null;

  var snapScript = document.createElement('script');
  snapScript.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
  snapScript.setAttribute('data-client-key', @js(config('midtrans.client_key') ?? ''));
  snapScript.async = false;
  document.head.appendChild(snapScript);

  $wire.on('midtrans-payment', (event) => {
    var data = Array.isArray(event) ? event[0] : (event.detail || event);
    var orderId = data && data.order_id;
    var token = data && data.token;
    var redirect_url = data && data.redirect_url;
    if (!orderId || !token || !redirect_url) { console.error('Midtrans: missing data', event); return; }

    setTimeout(function() {
      if (window.snap) {
        window.snap.pay(token, {
          onSuccess: function() {
            $wire.call('confirmMidtransPayment', orderId).then(() => {
              window.location.reload();
            });
          },
          onPending: function() {},
          onError: function(e) { console.error('Snap error', e); }
        });
      }
    }, 500);
  });

  $wire.on('selling-created', (event) => {
    selling = event.selling;
    $wire.dispatch('close-modal', {
      id: 'proceed-the-payment'
    });

    $wire.dispatch('open-modal', {
      id: 'success-modal',
      money_changes: selling.money_changes
    });
    setTimeout(() => {
      document.getElementById('changes').innerHTML = moneyFormat(selling.money_changes);
    }, 300);
  });

  async function doPrintReceipt() {
    let about = @js($about);
      const printerData = window.getPrinter();
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
    if ("@js(feature(Discount::class))" == 'true') {
      p.table(['@lang('Discount')', `(${moneyFormat(selling.total_discount_per_item + selling.discount_price)})`])
    }
    p.table(['@lang('Total price')', moneyFormat(selling.grand_total_price)])
      .text('-------------------------------');
    if (selling.payment_method?.is_credit) {
      if (selling.payed_money > 0) {
        p.table(['@lang('DP (Down payment)')', moneyFormat(selling.payed_money)])
         .table(['@lang('Remaining')', moneyFormat(selling.grand_total_price - selling.payed_money)]);
      } else {
        p.table(['@lang('Remaining')', moneyFormat(selling.grand_total_price)]);
      }
      if (selling.due_date) p.table(['@lang('Due date')', selling.due_date]);
    } else {
      p.table(['@lang('Payed money')', moneyFormat(selling.payed_money)])
       .table(['@lang('Change')', moneyFormat(selling.money_changes)]);
    }
    p.align('center');
    if (printerData.footer != undefined) p.text(printerData.footer);
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
    const isCredit = selling.payment_method?.is_credit || false;
    if (isCredit) {
      h += `<div style="display:flex;justify-content:space-between"><span>Total</span><span>${moneyFormat(selling.grand_total_price)}</span></div>`;
      if (selling.payed_money > 0) {
        h += `<div style="display:flex;justify-content:space-between"><span>DP (Down payment)</span><span>${moneyFormat(selling.payed_money)}</span></div>`;
        h += `<div style="display:flex;justify-content:space-between;font-weight:700"><span>Remaining</span><span>${moneyFormat(selling.grand_total_price - selling.payed_money)}</span></div>`;
      } else {
        h += `<div style="display:flex;justify-content:space-between;font-weight:700"><span>Remaining</span><span>${moneyFormat(selling.grand_total_price)}</span></div>`;
      }
      if (selling.due_date) h += `<div style="display:flex;justify-content:space-between"><span>Due date</span><span>${esc(selling.due_date)}</span></div>`;
    } else {
      h += `<div style="display:flex;justify-content:space-between"><span>Payed money</span><span>${moneyFormat(selling.payed_money)}</span></div>`;
      h += `<div style="display:flex;justify-content:space-between"><span>Change</span><span>${moneyFormat(selling.money_changes)}</span></div>`;
    }
    if (printerData?.footer) h += `<div style="text-align:center;margin-top:4px">${esc(printerData.footer)}</div>`;
    h += `<div style="font-size:10px;margin-top:2px">copy</div>`;
    return h;
  }

  document.getElementById("printReceiptButton").addEventListener('click', async () => {
    let a = @js($about);
    const pd = typeof window.getPrinter === 'function' ? window.getPrinter() : null;
    const preview = previewHtml(selling, a, pd instanceof Error ? null : pd);
    document.getElementById('receiptPreviewContent').innerHTML = preview;
    $wire.dispatch('close-modal', {id: 'success-modal'});
    $wire.dispatch('open-modal', {id: 'receipt-preview-modal'});
  });

  document.addEventListener('click', async (e) => {
    if (e.target.id === 'confirmPrintButton' || e.target.closest('#confirmPrintButton')) {
      await doPrintReceipt();
      $wire.dispatch('close-modal', {id: 'receipt-preview-modal'});
    }
  });

  Alpine.data('fullscreen', () => {
    return {
      isFullscreen: false,
      requestFullscreen() {
        if (!document.fullscreenElement) {
          document.documentElement.requestFullscreen();
          isFullscreen = true;
        } else {
          document.exitFullscreen();
          isFullscreen = false;
        }
      }
    }
  });
  Alpine.data('detail', () => {
    return {
      paymentMethodWarning: false,
      rawValue: 0,
      changeAmount: 0,
      maxValue: 999999999,
      decimalActive: false,
      decimalCount: 0,

      init() {
          Livewire.on('payment-method-missing', () => {
              this.paymentMethodWarning = true;
              setTimeout(() => this.paymentMethodWarning = false, 3000);
          });
          this.$watch('subtotal', (val) => {
            if (val !== undefined && val !== null) this.recalc();
          });
          document.addEventListener('shortcut-payment', (e) => {
            this.shortcut(e.detail.amount);
          });
      },
      isTouchScreen() {
        return ('ontouchstart' in window) ||
          (navigator.maxTouchPoints > 0) ||
          (navigator.msMaxTouchPoints > 0);
      },
      paymentMethods: $wire.entangle('paymentMethods'),
      cartDetail: @js($cartDetail),
      subtotal: $wire.entangle('total_price'),

      pressDigit(digit) {
        if (this.decimalActive) {
          this.decimalCount++;
          if (this.decimalCount > 2) return;
          this.rawValue = this.rawValue + digit / Math.pow(10, this.decimalCount);
          this.rawValue = parseFloat(this.rawValue.toFixed(2));
        } else {
          let next = this.rawValue * 10 + digit;
          if (next > this.maxValue) return;
          this.rawValue = next;
        }
        this.sync();
      },
      pressDecimal() {
        if (this.decimalActive) return;
        this.decimalActive = true;
        this.decimalCount = 0;
        this.rawValue = parseFloat(this.rawValue.toString());
        this.sync();
      },
      pressBackspace() {
        if (this.decimalActive && this.decimalCount > 0) {
          this.decimalCount--;
          if (this.decimalCount === 0) {
            this.decimalActive = false;
            this.rawValue = Math.round(this.rawValue);
          } else {
            this.rawValue = Math.floor(this.rawValue * Math.pow(10, this.decimalCount)) / Math.pow(10, this.decimalCount);
          }
        } else if (this.decimalActive && this.decimalCount === 0) {
          this.decimalActive = false;
        } else {
          this.rawValue = Math.floor(this.rawValue / 10);
        }
        this.sync();
      },
      pressClear() {
        this.rawValue = 0;
        this.decimalActive = false;
        this.decimalCount = 0;
        this.sync();
      },
      pressNoChange() {
        let sub = this.getSubtotal();
        this.rawValue = sub;
        this.decimalActive = false;
        this.decimalCount = 0;
        this.sync();
      },
      shortcut(number) {
        let n = parseFloat(number);
        if (isNaN(n) || n < 0) return;
        if (n > this.maxValue) n = this.maxValue;
        this.rawValue = n;
        this.decimalActive = false;
        this.decimalCount = 0;
        this.sync();
      },
      getSubtotal() {
        let sub = typeof this.subtotal === 'number' ? this.subtotal : parseFloat(this.subtotal || '0');
        return isNaN(sub) ? 0 : sub;
      },
      sync() {
        this.$refs.payedMoney.value = moneyFormat(this.rawValue);
        this.recalc();
      },
      recalc() {
        let sub = this.getSubtotal();
        this.changeAmount = this.rawValue - sub;
        let safeChange = this.changeAmount > 0 ? this.changeAmount : 0;
        $wire.set('cartDetail.money_changes', safeChange);
        $wire.set('cartDetail.payed_money', this.rawValue);
        if (this.$refs.moneyChanges) {
          this.$refs.moneyChanges.textContent = moneyFormat(safeChange);
        }
      },
      handleKeydown(event) {
        let k = event.key;
        if (k >= '0' && k <= '9') { this.pressDigit(parseInt(k, 10)); event.preventDefault(); }
        else if (k === '.' || k === ',') { this.pressDecimal(); event.preventDefault(); }
        else if (k === 'Backspace') { this.pressBackspace(); event.preventDefault(); }
        else if (k === 'Delete') { this.pressClear(); event.preventDefault(); }
        else if (k === 'Escape') { $wire.dispatch('close-modal', {id: 'proceed-the-payment'}); event.preventDefault(); }
      },
      selectPayment(method) {
        this.cartDetail['payment_method_id'] = method.id;
        $wire.setPaymentMethodId(method.id);

        var midtransTypes = ['debit_card', 'gopay', 'shopeepay', 'qris', 'bank_transfer', 'indomaret', 'alfamart', 'kredivo', 'akulaku'];
        if (midtransTypes.includes(method.payment_type)) {
          setTimeout(() => { $wire.proceedThePayment(); }, 100);
        }
      }
    }
  });

  Alpine.data('cart', () => {
    return {
      add: (productId, amount) => {
        $wire.addCart(productId, {
          amount: amount ?? 0
        })
      }
    }
  })

  let barcodeData = '';
  let barcodeTimeout;
  let scannerEnabled = true;
  let modalOpened = false;
  let input;
  let index;

  function generateSuggestedPayments(totalPrice) {
    const denominations = [500, 1000, 2000, 5000, 10000, 20000, 50000, 100000];
    const suggestions = [];

    for (let denom of denominations) {
      const suggestion = Math.ceil(totalPrice / denom) * denom;
      if (!suggestions.includes(suggestion)) {
        suggestions.push(suggestion);
      }
    }

    suggestions.sort((a, b) => a - b);

    return suggestions;
  }

  let lastGeneratedTotal = null;

  function generateButton(totalPrice) {
    if (totalPrice === lastGeneratedTotal) return;
    lastGeneratedTotal = totalPrice;

    const shortcutSuggestion = generateSuggestedPayments(totalPrice);
    let calculatorBtn = document.getElementById('calculator-button-shortcut');
    if (!calculatorBtn) return;

    while (calculatorBtn.firstChild) {
      calculatorBtn.removeChild(calculatorBtn.firstChild);
    }

    for (let suggestion of shortcutSuggestion) {
      const button = document.createElement('button');
      button.textContent = numberFormat(suggestion);
      button.setAttribute('type', 'button');
      button.className = 'flex min-h-[40px] items-center justify-center rounded-xl bg-zonakasir-primary/10 p-2 text-sm font-semibold text-zonakasir-primary shadow-sm ring-1 ring-zonakasir-primary/20 transition-all hover:bg-zonakasir-primary/20 active:scale-95 dark:bg-zonakasir-primary/20 dark:text-zonakasir-primary/90 dark:ring-zonakasir-primary/30';
      button.addEventListener('click', () => {
        document.dispatchEvent(new CustomEvent('shortcut-payment', { detail: { amount: suggestion } }));
      });
      calculatorBtn.appendChild(button);
    }
  }

  $wire.on('open-modal', (event) => {
    if (event.id === 'qr-scanner-modal') {
      if (!html5QrcodeScanner) {
        html5QrcodeScanner = new Html5QrcodeScanner(
          "qr-reader",
          {
            fps: 10,
            qrbox: { width: 300, height: 200 },
            rememberLastUsedCamera: true
          },
          false
        );
      }
      html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    }

    if (event.inputId != undefined) {
      let inputId = event.inputId;
      let title = event.title;
      let titleModal = document.getElementById("titleEditDetail");
      titleModal.innerHTML = title;
      index = event.index;
      input = document.getElementById(inputId);
      const result = [...(input.parentNode.parentNode.parentNode.parentNode.parentNode.children)].forEach((child,
        i) => {
        if (i != index) {
          child.classList.add('hidden');
        }
      });
      input.classList.remove('hidden');
    }
    let totalPrice = $refs.total.getAttribute('data-value');
    if ("@js(feature(PaymentShortcutButton::class))" == 'true') {
      generateButton(totalPrice);
    }
    if (event.id === 'proceed-the-payment') {
      setTimeout(() => {
        const el = document.getElementById('display');
        if (el) el.focus();
      }, 150);
    }
    modalOpened = true;
  });

  $wire.on('close-modal', (event) => {
    if (input != undefined) {
      let titleModal = document.getElementById("titleEditDetail");
      titleModal.innerHTML = '@lang('Edit detail')';
      const result = [...(input.parentNode.parentNode.parentNode.parentNode.parentNode.children)].forEach((child,
        i) => {
        if (i != index) {
          child.classList.remove('hidden');
        }
      });
      input.classList.add('hidden');
      input = undefined
    }
    modalOpened = false;
  });

  let html5QrcodeScanner = null;
  let isScanningEnabled = true;

  async function onScanSuccess(decodedText, decodedResult) {
    if (!isScanningEnabled) return;

    const readerElement = document.getElementById('qr-reader');
    if (!readerElement) {
      console.error('Scanner reader element not found!');
      return;
    }

    const alpineContainer = readerElement.closest('[x-ref="scannerContainer"]');
    if (!alpineContainer || !alpineContainer._x_dataStack) {
      console.error('Could not find the Alpine.js scanner container.');
      return;
    }
    const alpineComponent = alpineContainer._x_dataStack[0];

    isScanningEnabled = false;
    alpineComponent.isLoading = true;

    console.log(`Scan result: ${decodedText}`);

    await $wire.call('addCartUsingScanner', decodedText);

    alpineComponent.isLoading = false;

    new FilamentNotification()
      .title('Product added')
      .success()
      .duration(3000)
      .send();

    setTimeout(() => {
      isScanningEnabled = true;
    }, 1000);
  }

  function onScanFailure(error) {
  }

  window.stopScanner = () => {
    if (html5QrcodeScanner && html5QrcodeScanner.getState() === Html5QrcodeScannerState.SCANNING) {
      html5QrcodeScanner.clear().then(() => {
        console.log('QR Code scanner stopped successfully.');
      }).catch(err => {
      });
    }
  };
  document.addEventListener('keypress', (event) => {
    if (modalOpened || !scannerEnabled) {
      return;
    }

    if (barcodeTimeout) {
      clearTimeout(barcodeTimeout);
    }

    if (event.key === 'Enter') {
      console.log('Barcode scanned:', barcodeData);
      $wire.addCartUsingScanner(barcodeData);

      barcodeData = '';
      scannerEnabled = false;

      setTimeout(() => {
        scannerEnabled = true;
      }, 1000);
    } else {
      barcodeData += event.key;
    }

    barcodeTimeout = setTimeout(() => {
      barcodeData = '';
    }, 500);
  });
</script>
@endscript
