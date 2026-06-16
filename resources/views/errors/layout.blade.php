<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Error')</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #FFF7ED 0%, #FFEDD5 50%, #FED7AA 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1a1a;
        }
        .error-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08), 0 1px 4px rgba(0,0,0,0.04);
            padding: 48px;
            text-align: center;
            max-width: 440px;
            width: 90%;
        }
        .error-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: @yield('icon-bg', '#FEF3C7');
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 28px;
        }
        .error-code {
            font-size: 56px;
            font-weight: 800;
            color: @yield('code-color', '#FF6600');
            line-height: 1;
            margin-bottom: 8px;
        }
        .error-title {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 12px;
        }
        .error-message {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .error-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background: #FF6600;
            color: white;
        }
        .btn-primary:hover {
            background: #E55A00;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255,102,0,0.3);
        }
        .btn-secondary {
            background: #F3F4F6;
            color: #374151;
        }
        .btn-secondary:hover {
            background: #E5E7EB;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="error-card">
        @yield('content')
    </div>
</body>
</html>
