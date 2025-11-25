<?php
/**
 * Form Submit Test Script
 * İş ve finans form submit sorunlarını test etmek için
 * 
 * Erişim: https://kuretemizlik.com/app/test_form_submit.php?confirm=yes
 * Kullanım sonrası SİLİN!
 */

// Güvenlik: Sadece confirm=yes ile erişim
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    die("Güvenlik: Bu dosyaya erişmek için ?confirm=yes parametresi gerekli.\n");
}

// Bootstrap
define('APP_ROOT', __DIR__);
define('APP_BASE', '/app');

// Load environment
if (file_exists(__DIR__ . '/env.local')) {
    $envFile = file(__DIR__ . '/env.local', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envFile as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, '"\'');
        if (!defined($key)) {
            define($key, $value);
        }
    }
}

define('APP_DEBUG', defined('APP_DEBUG') ? APP_DEBUG : false);

// Define DB_PATH early
if (!defined('DB_PATH')) {
    define('DB_PATH', APP_ROOT . '/db/app.sqlite');
}

// Load config
require_once __DIR__ . '/config/config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load necessary classes
require_once __DIR__ . '/src/Lib/Database.php';
require_once __DIR__ . '/src/Lib/CSRF.php';
require_once __DIR__ . '/src/Lib/Auth.php';

// Start output buffering
ob_start();
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Form Submit Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 1000px; }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #4CAF50; }
        .error { border-left-color: #f44336; background: #ffe6e6; }
        .success { border-left-color: #4CAF50; background: #e8f5e9; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .test-form { background: #e3f2fd; padding: 15px; border-radius: 4px; margin: 20px 0; }
        button { padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #45a049; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Form Submit Test - Production</h1>
        
        <?php
        echo '<div class="section">';
        echo '<h2>1. Session Durumu</h2>';
        echo '<pre>';
        echo "Session ID: " . session_id() . "\n";
        echo "Session Status: " . session_status() . " (" . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active') . ")\n";
        $cookieParams = session_get_cookie_params();
        echo "Session Cookie Params:\n";
        echo "  Path: " . $cookieParams['path'] . "\n";
        echo "  Domain: " . ($cookieParams['domain'] ?: 'null') . "\n";
        echo "  Secure: " . ($cookieParams['secure'] ? 'true' : 'false') . "\n";
        echo "  HttpOnly: " . ($cookieParams['httponly'] ? 'true' : 'false') . "\n";
        echo "  SameSite: " . ($cookieParams['samesite'] ?: 'None') . "\n";
        echo "Cookie in \$_COOKIE:\n";
        $cookieName = session_name();
        if (isset($_COOKIE[$cookieName])) {
            echo "  ✅ Cookie var: " . substr($_COOKIE[$cookieName], 0, 20) . "...\n";
        } else {
            echo "  ❌ Cookie yok!\n";
        }
        echo '</pre>';
        echo '</div>';

        echo '<div class="section">';
        echo '<h2>2. CSRF Token Testi</h2>';
        $csrfToken = CSRF::get();
        echo '<div class="success"><strong>✅ CSRF Token oluşturuldu:</strong> ' . htmlspecialchars(substr($csrfToken, 0, 32)) . '...</div>';
        echo '<pre>';
        echo "Session'daki token havuzu: \n";
        $tokensInSession = $_SESSION['csrf_tokens'] ?? [];
        echo "  Token sayısı: " . count($tokensInSession) . "\n";
        if (count($tokensInSession) > 0) {
            $firstToken = array_key_first($tokensInSession);
            echo "  İlk token: " . htmlspecialchars(substr($firstToken, 0, 32)) . "...\n";
        }
        echo '</pre>';
        echo '</div>';

        echo '<div class="section">';
        echo '<h2>3. Test Form Submit</h2>';
        echo '<div class="test-form">';
        echo '<h3>Test İş Form Submit:</h3>';
        echo '<form method="POST" action="' . base_url('/jobs/create') . '" id="testJobForm">';
        echo CSRF::field();
        echo '<input type="hidden" name="customer_id" value="1">';
        echo '<input type="hidden" name="service_id" value="1">';
        echo '<input type="hidden" name="start_at" value="' . date('Y-m-d H:i') . '">';
        echo '<input type="hidden" name="end_at" value="' . date('Y-m-d H:i', strtotime('+1 hour')) . '">';
        echo '<input type="hidden" name="total_amount" value="100">';
        echo '<button type="submit">Test İş Form Submit</button>';
        echo '</form>';
        echo '</div>';
        
        echo '<div class="test-form">';
        echo '<h3>Test Finans Form Submit:</h3>';
        echo '<form method="POST" action="' . base_url('/finance/create') . '" id="testFinanceForm">';
        echo CSRF::field();
        echo '<input type="hidden" name="kind" value="INCOME">';
        echo '<input type="hidden" name="category" value="TEST">';
        echo '<input type="hidden" name="amount" value="100">';
        echo '<input type="hidden" name="date" value="' . date('Y-m-d') . '">';
        echo '<button type="submit">Test Finans Form Submit</button>';
        echo '</form>';
        echo '</div>';
        echo '</div>';

        // Test POST data if form was submitted
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo '<div class="section">';
            echo '<h2>4. POST Request Testi</h2>';
            echo '<pre>';
            echo "POST Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
            echo "POST Data:\n";
            print_r($_POST);
            echo "\nCSRF Token in POST: " . (isset($_POST['csrf_token']) ? substr($_POST['csrf_token'], 0, 32) . '...' : 'YOK') . "\n";
            echo "CSRF Verification: ";
            if (CSRF::verifyRequest()) {
                echo "✅ BAŞARILI\n";
            } else {
                echo "❌ BAŞARISIZ\n";
            }
            echo '</pre>';
            echo '</div>';
        }
        ?>
        
        <div class="section error">
            <h2>⚠️ Güvenlik Uyarısı</h2>
            <p>Bu dosya hassas bilgiler içerir. Test tamamlandıktan sonra <strong>MUTLAKA SİLİN!</strong></p>
        </div>
    </div>
</body>
</html>
<?php
ob_end_flush();
?>

