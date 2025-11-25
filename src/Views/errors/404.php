<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Sayfa Bulunamadı</title>
    <link rel="stylesheet" href="<?= Utils::asset('css/tailwind.css') ?>?v=<?= file_exists(__DIR__ . '/../../../assets/css/tailwind.css') ? filemtime(__DIR__ . '/../../../assets/css/tailwind.css') : time() ?>">
</head>
<body>
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-lg w-full space-y-8">
        <div class="text-center">
            <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-yellow-100">
                <i class="fas fa-search text-yellow-600 text-2xl"></i>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Sayfa Bulunamadi
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Aradi?Yiniz sayfa mevcut de?Yil veya ta?Yinmi?Y olabilir.
            </p>
            
            <div class="mt-8 space-y-3">
                <a href="<?= base_url('/') ?>" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-home mr-2"></i>
                    Ana Sayfaya D�n
                </a>
                <button onclick="history.back()" class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Geri D�n
                </button>
            </div>
        </div>
    </div>
</div>
</body>
</html>
