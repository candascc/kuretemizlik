<?php
require_once __DIR__ . '/../Lib/Utils.php';
?>
<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Çevrimdışı Mod - Küre Temizlik</title>
    <link rel="icon" href="<?= Utils::asset('img/logokureapp.png') ?>">
    <style>
        :root {
            color-scheme: light dark;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: radial-gradient(circle at top, #2563eb33, rgba(15, 23, 42, 0.88)), #0f172a;
            color: #f8fafc;
            padding: 2rem 1.5rem;
        }
        .card {
            width: min(420px, 100%);
            border-radius: 28px;
            padding: 2.25rem;
            background: rgba(15, 23, 42, 0.82);
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow: 0 32px 80px -32px rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(22px);
            text-align: center;
        }
        .logo-ring {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            padding: 3px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.7), rgba(16, 185, 129, 0.7));
            display: grid;
            place-items: center;
        }
        .logo-ring img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        h1 {
            font-size: clamp(1.5rem, 1.2vw + 1.2rem, 2.1rem);
            margin: 0 0 0.75rem;
        }
        p {
            margin: 0 auto 1.75rem;
            line-height: 1.6;
            color: rgba(226, 232, 240, 0.85);
        }
        ul {
            list-style: none;
            margin: 0 0 1.75rem;
            padding: 0;
            display: grid;
            gap: 0.75rem;
            text-align: left;
        }
        li {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
            font-size: 0.95rem;
        }
        li span {
            display: inline-flex;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: rgba(79, 70, 229, 0.18);
            color: #c7d2fe;
            font-weight: 600;
            align-items: center;
            justify-content: center;
        }
        .actions {
            display: grid;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        a.button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 0.6rem;
            border-radius: 999px;
            padding: 0.85rem 1.4rem;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        a.button.primary {
            background: linear-gradient(135deg, #6366f1, #22d3ee);
            color: #0f172a;
            box-shadow: 0 20px 32px -16px rgba(14, 165, 233, 0.45);
        }
        a.button.secondary {
            background: rgba(226, 232, 240, 0.12);
            color: rgba(226, 232, 240, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.28);
        }
        a.button:hover {
            transform: translateY(-2px);
        }
        footer {
            margin-top: 2rem;
            font-size: 0.75rem;
            color: rgba(148, 163, 184, 0.7);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo-ring">
            <img src="<?= Utils::asset('img/logokureapp.png') ?>" alt="Küre Temizlik Logosu">
        </div>
        <h1>Şu an çevrimdışısınız</h1>
        <p>Sakin portalı ve yönetim modüllerine ulaşmak için bağlantınızı kontrol edin. İnternet geri geldiğinde veriler otomatik olarak senkronize edilecektir.</p>
        <ul>
            <li><span>1</span> Wi-Fi veya mobil veri bağlantınızı kontrol edin</li>
            <li><span>2</span> Gerekirse uygulamayı kapa/aç yaparak yeniden deneyin</li>
            <li><span>3</span> Yardım için <a href="mailto:support@kuretemizlik.com">support@kuretemizlik.com</a></li>
        </ul>
        <div class="actions">
            <a class="button primary" href="<?= base_url('/') ?>">
                <i class="fas fa-sync-alt"></i>
                Yeniden Dene
            </a>
            <a class="button secondary" href="tel:+908508502525">
                <i class="fas fa-phone"></i>
                Destek hattı 0850 850 25 25
            </a>
        </div>
        <footer>© <?= date('Y') ?> Küre Temizlik · Yönetim & Portal Platformu</footer>
    </div>
    <script>
        window.addEventListener('online', () => window.location.reload());
    </script>
</body>
</html>
