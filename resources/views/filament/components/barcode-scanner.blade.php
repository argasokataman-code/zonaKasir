@props(['statePath'])

<div x-data x-init="
    // Keep the state path of the form field.
    const statePath = '{{ $statePath }}';

    // 1. Define the success callback separately for clarity.
    const onScanSuccess = (decodedText, decodedResult) => {
        // Set the value in Livewire.
        $wire.set(statePath, decodedText);

        // Find and click the modal's close button.
        // This will also trigger the 'click' event we define below to clear the scanner.
        document.getElementById('close-barcode-scanner-button')?.click();
    };

    // 2. Define an error callback (optional).
    const onScanFailure = (error) => {
        // You can ignore errors or log them to the console for debugging.
        // console.warn(`Code scan error = ${error}`);
    };

    // 3. Wait for the library to be loaded.
    const startScanner = () => {
        if (!window.Html5QrcodeScanner) {
            setTimeout(startScanner, 100);
            return;
        }

        // Create the scanner instance with its UI.
        const html5QrcodeScanner = new Html5QrcodeScanner(
            'reader', // ID of the <div> element where the scanner will be rendered.
            {
                fps: 10,
                qrbox: { width: 250, height: 150 },
                formatsToSupport: [ // Make sure this global variable is available.
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

        // Render the scanner.
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);

        // 4. VERY IMPORTANT: Clean up the scanner when the modal is closed.
        // This is crucial to release the camera.
        const closeButton = document.getElementById('close-barcode-scanner-button');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                // Check if the scanner is active before trying to clear it.
                if (html5QrcodeScanner && html5QrcodeScanner.getState() === Html5QrcodeScannerState.SCANNING) {
                    html5QrcodeScanner.clear().catch(err => {
                        console.error('Failed to clear the scanner.', err);
                    });
                }
            });
        }
    };

    // Start the whole process.
    startScanner();
">
    {{-- This div is the container where the library will build its UI --}}
    <div id="reader" style="width: 100%;"></div>
</div>
