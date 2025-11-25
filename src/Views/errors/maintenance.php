<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakım Modu</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
        h1 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 12px;
        }
        p {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .info {
            background: #f9fafb;
            border-left: 4px solid #667eea;
            padding: 16px;
            margin: 24px 0;
            text-align: left;
            border-radius: 4px;
        }
        .info p {
            margin: 0;
            font-size: 14px;
        }
        .refresh-btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }
        .refresh-btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔧</div>
        <h1>Bakım Modu</h1>
        <p>
            Sistem şu anda bakım modunda. Lütfen daha sonra tekrar deneyin.
        </p>
        <div class="info">
            <p><strong>Bakım Süresi:</strong> <?= htmlspecialchars($maintenanceMessage ?? 'Yakında geri döneceğiz.') ?></p>
        </div>
        <a href="javascript:location.reload()" class="refresh-btn">Sayfayı Yenile</a>
    </div>
</body>
</html>

