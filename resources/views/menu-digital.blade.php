<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex" />
    <title>{{ $about->shop_name ?? 'Menu' }} — Menu Digital</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
            color: #111827;
        }
        .header {
            background: {{ $about->primary_color ?: '#f97316' }};
            color: #fff;
            padding: 32px 20px;
            text-align: center;
        }
        .header h1 { font-size: 22px; font-weight: 700; }
        .header p { font-size: 13px; opacity: .9; margin-top: 4px; }
        .header .table-tag {
            display: inline-block;
            margin-top: 10px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(255, 255, 255, .2);
            border-radius: 999px;
            padding: 4px 12px;
        }
        .content { max-width: 520px; margin: 0 auto; padding: 20px 16px 40px; }
        .category {
            margin: 20px 0 8px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #6b7280;
        }
        .item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            background: #fff;
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
        }
        .item-name { font-size: 14px; font-weight: 600; }
        .item-price { font-size: 14px; font-weight: 700; white-space: nowrap; color: {{ $about->primary_color ?: '#f97316' }}; }
        .favorite-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            color: #b45309;
            background: #fef3c7;
            border-radius: 999px;
            padding: 2px 8px;
            margin-left: 6px;
            vertical-align: middle;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            padding: 16px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $about->shop_name ?? 'Menu' }}</h1>
        @if ($about->shop_location)
            <p>{{ $about->shop_location }}</p>
        @endif
        @if ($table)
            <span class="table-tag">Meja {{ $table->number }}</span>
        @endif
    </div>

    <div class="content">
        @php
            $groups = $items->groupBy(fn ($i) => $i['category'] ?? 'Menu');
        @endphp

        @forelse ($groups as $category => $group)
            <div class="category">{{ $category }}</div>
            @foreach ($group as $item)
                <div class="item">
                    <div>
                        <span class="item-name">{{ $item['name'] }}</span>
                        @if ($item['is_favorite'])
                            <span class="favorite-badge">🔥 Favorit</span>
                        @endif
                    </div>
                    <div class="item-price">{{ Number::currency($item['price'], 'IDR') }}</div>
                </div>
            @endforeach
        @empty
            <p style="text-align:center;color:#6b7280;margin-top:40px">Menu belum tersedia.</p>
        @endforelse
    </div>

    <div class="footer">Scan untuk lihat menu {{ $about->shop_name ?? 'kafe' }} ini</div>
</body>
</html>
