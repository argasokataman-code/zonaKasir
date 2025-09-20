<div
    x-data
    x-init="
        const initScanner = () => {
            // Espera a que la librería cargue
            if (!window.Html5Qrcode) {
                console.log('Esperando que Html5Qrcode cargue...');
                setTimeout(initScanner, 100);
                return;
            }

            if (!window.html5QrCode) {
                window.html5QrCode = new Html5Qrcode('reader');
            }

            // Función para detener el escáner de forma segura
            const stopScanner = () => {
                if (window.html5QrCode && window.html5QrCode.isScanning) {
                    window.html5QrCode.stop().catch(err => {
                        console.warn('El escáner ya estaba detenido o no se pudo detener limpiamente.', err);
                    });
                }
            };

            // Busca el botón de cerrar y le asigna la función de detener
            const closeButton = document.getElementById('close-barcode-scanner-button');
            if (closeButton) {
                closeButton.addEventListener('click', stopScanner);
            }

            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    const cameraId = devices[0].id;
                    window.html5QrCode.start(
                        cameraId,
                        {
                            fps: 10,
                            qrbox: { width: 300, height: 200 }, // ideal para códigos de barra
                            formatsToSupport: [
                                Html5QrcodeSupportedFormats.EAN_13,
                                Html5QrcodeSupportedFormats.CODE_128,
                                Html5QrcodeSupportedFormats.UPC_A,
                                Html5QrcodeSupportedFormats.UPC_E,
                                Html5QrcodeSupportedFormats.CODE_39,
                                Html5QrcodeSupportedFormats.ITF,
                            ],
                        },
                        decodedText => {
                            console.log('Código detectado:', decodedText);

                            // Rellena el input del formulario
                            const input = document.querySelector('input[name=barcode]');
                            if (input) {
                                input.value = decodedText;
                                // Esto notifica a Livewire que el valor cambió
                                input.dispatchEvent(new Event('input', { bubbles: true }));
                            }

                            $wire.set('data.barcode', decodedText);

                            // Cierra el modal
                            const closeButton = document.getElementById('close-barcode-scanner-button');
                            closeButton.click();

                            // Detiene el scanner
                            // window.html5QrCode.stop().catch(() => {});
                        },
                        errorMessage => {
                            console.warn('Error de lectura:', errorMessage);
                        }
                    );
                } else {
                    console.error('No se encontraron cámaras');
                }
            }).catch(err => console.error(err));
        }

        initScanner();
    "
>
    <div id="reader" style="width:100%; min-height:300px;"></div>
</div>
