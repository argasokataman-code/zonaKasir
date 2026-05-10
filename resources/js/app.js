let selectedDevice = null;

function getPrinter() {
  if (localStorage.printer == undefined) {
    console.error('printer didn\'t set');
    return Error('printer didn\'t set');
  }

  return JSON.parse(localStorage.printer);
}

async function printToUSBPrinter(text) {
  let receiptText = text;
  console.log(receiptText);

  try {
    if (localStorage.printer == undefined) {
      console.error('No USB printer selected');
      return;
    }

    let printer = JSON.parse(localStorage.printer);
    const devices = await navigator.usb.getDevices();

    const device = devices.find(device => device.vendorId === printer.vendorId);
    if (device) {
      console.log('Found USB device:', device.productName);

      await device.open();
      await device.selectConfiguration(1);
      await device.claimInterface(0);

      const encoder = new TextEncoder();
      const data = encoder.encode(receiptText);
      const endpoint = device.configuration.interfaces[0].alternate.endpoints.filter(endpoint => endpoint.direction === 'out')[0]
      await device.transferOut(endpoint.endpointNumber, data);

      console.log('Data sent to printer');
    } else {
      console.log('No USB device with the specified vendor ID found');
      new FilamentNotification()
        .title('You should choose the printer first in printer setting')
        .danger()
        .actions([
          new FilamentNotificationAction('Setting')
            .icon('heroicon-o-cog-6-tooth')
            .button()
            .url('/member/printer'),
        ])
        .send()
    }
  } catch (e) {
    console.error(e);
  }
}

function padText(text, length, alignRight = false, center = false, textSize = 'normal') {
  const sizes = {
    'normal': '\x1D\x21\x00', // Normal text
    'large': '\x1D\x21\x11', // Large text
  }[textSize];
  let paddedText = text;

  if (center) {
    const padLength = Math.max(0, length - text.length);
    const padStart = Math.floor(padLength / 2);
    const padEnd = Math.ceil(padLength / 2);
    paddedText = ' '.repeat(padStart) + text + ' '.repeat(padEnd);
  } else if (alignRight) {
    paddedText = text.padStart(length);
  } else {
    paddedText = text.padEnd(length);
  }

  return paddedText;
}

function moneyFormat(number, currency = null) {
  const activeCurrency = currency || window.lakasirCurrency || 'IDR';

  const options = {
    style: 'currency',
    currency: activeCurrency,
  };

  // For IDR, we typically want to hide decimals in a POS context for a cleaner look.
  // For other currencies (like USD), Intl.NumberFormat will naturally use 2 decimals.
  if (activeCurrency === 'IDR') {
    options.minimumFractionDigits = 0;
  }

  const formatter = new Intl.NumberFormat(undefined, options);

  return formatter.format(number);
}

