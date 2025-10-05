@props(['statePath'])

<div x-data x-init="
    const statePath = '{{ $statePath }}';

    const onScanSuccess = (decodedText, decodedResult) => {

        $wire.set(statePath, decodedText);

        document.getElementById('close-barcode-scanner-button')?.click();
    };


    const onScanFailure = (error) => {

        // console.warn(`Code scan error = ${error}`);
    };


    const startScanner = () => {
        if (!window.Html5QrcodeScanner) {
            setTimeout(startScanner, 100);
            return;
        }


        const html5QrcodeScanner = new Html5QrcodeScanner(
            'reader',
            {
                fps: 10,
                qrbox: { width: 250, height: 150 },
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.UPC_A,
                    Html5QrcodeSupportedFormats.UPC_E,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.ITF,
                ]
            },
            /* verbose= */ false
        );


        html5QrcodeScanner.render(onScanSuccess, onScanFailure);


        const closeButton = document.getElementById('close-barcode-scanner-button');
        if (closeButton) {
            closeButton.addEventListener('click', () => {

                if (html5QrcodeScanner && html5QrcodeScanner.getState() === Html5QrcodeScannerState.SCANNING) {
                    html5QrcodeScanner.clear().catch(err => {
                        console.error('Failed to clear the scanner.', err);
                    });
                }
            });
        }
    };

    
    startScanner();
">
    {{-- Este div es el contenedor donde la librería construirá su interfaz --}}
    <div id="reader" style="width: 100%;"></div>
</div>
