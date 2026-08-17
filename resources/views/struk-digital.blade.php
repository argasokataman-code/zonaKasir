<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex" />
    <title>{{ $about?->shop_name ?? config('app.name') }} — Struk Digital</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            color: #111827;
        }
        .card {
            background: #ffffff;
            border-radius: 20px;
            max-width: 420px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, .08);
        }
        .header {
            padding: 24px 24px 16px;
            background: {{ $about?->primary_color ?: '#f97316' }};
            color: #fff;
        }
        .header h1 { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .header p { font-size: 13px; opacity: .9; }
        .body { padding: 20px 24px; }
        .meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 16px;
        }
        .items { border-top: 1px dashed #e5e7eb; }
        .item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px dashed #e5e7eb;
        }
        .item-name { font-size: 14px; font-weight: 500; }
        .item-detail { font-size: 12px; color: #6b7280; margin-top: 2px; }
        .item-price { font-size: 14px; font-weight: 600; white-space: nowrap; }
        .totals { padding-top: 12px; }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            padding: 3px 0;
            color: #374151;
        }
        .total-row.grand {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            padding-top: 8px;
            margin-top: 4px;
            border-top: 2px solid #111827;
        }
        .footer {
            text-align: center;
            padding: 16px 24px 24px;
            border-top: 1px solid #f3f4f6;
        }
        .qr { margin-bottom: 12px; }
        .qr svg { width: 140px; height: 140px; border-radius: 12px; padding: 8px; background: #fff; }
        .footer .share-hint { font-size: 12px; color: #6b7280; margin-bottom: 8px; }
        .footer .url {
            font-size: 12px;
            color: #f97316;
            word-break: break-all;
            background: #fff7ed;
            padding: 8px 12px;
            border-radius: 8px;
        }
        .payment { margin-top: 12px; }
        .payment .badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
            color: #047857;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 999px;
            padding: 4px 12px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>{{ $about?->shop_name ?? 'Kafe' }}</h1>
            @if ($about?->shop_location)
                <p>{{ $about->shop_location }}</p>
            @endif
        </div>

        <div class="body">
            <div class="meta">
                <div>
                    <div>{{ $selling->code }}</div>
                    @if ($selling->table)
                        <div>Meja {{ $selling->table->number }}</div>
                    @endif
                </div>
                <div>{{ $selling->created_at->format('d M Y H:i') }}</div>
            </div>

            <div class="items">
                @foreach ($selling->sellingDetails as $detail)
                    <div class="item">
                        <div>
                            <div class="item-name">{{ $detail->product?->name }}</div>
                            <div class="item-detail">{{ $detail->qty }} × {{ number_format($detail->price / max($detail->qty, 1), 0, ',', '.') }}</div>
                        </div>
                        <div class="item-price">{{ number_format($detail->price - $detail->discount_price, 0, ',', '.') }}</div>
                    </div>
                @endforeach
            </div>

            <div class="totals">
                <div class="total-row"><span>Subtotal</span><span>{{ number_format($selling->total_price, 0, ',', '.') }}</span></div>
                @if ((float) $selling->discount_price > 0)
                    <div class="total-row"><span>Diskon</span><span>-{{ number_format($selling->discount_price, 0, ',', '.') }}</span></div>
                @endif
                @if ((float) $selling->tax_price > 0)
                    <div class="total-row"><span>Pajak</span><span>{{ number_format($selling->tax_price, 0, ',', '.') }}</span></div>
                @endif
                <div class="total-row grand"><span>Total</span><span>{{ number_format($selling->grand_total_price, 0, ',', '.') }}</span></div>
            </div>

            <div class="payment">
                <span class="badge">Lunas — {{ $selling->paymentMethod?->name ?? 'Pembayaran' }}</span>
            </div>
        </div>

        <div class="footer">
            <div class="share-hint">Scan untuk lihat struk ini</div>
            <div class="qr">{!! QrCode::size(140)->generate($selling->shareUrl()) !!}</div>
            <div class="url">{{ $selling->shareUrl() }}</div>
        </div>
    </div>
</body>
</html>
