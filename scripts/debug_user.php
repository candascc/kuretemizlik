<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Lib/Database.php';

$username = $argv[1] ?? 'test_support';
$db = Database::getInstance();
$user = $db->fetch('SELECT id, username, role, is_active, password_hash, updated_at FROM users WHERE username = ?', [$username]);
var_export($user);
echo PHP_EOL;

$password = $argv[2] ?? '12dream21';
if ($user && !empty($user['password_hash'])) {
    $isValid = password_verify($password, $user['password_hash']);
    echo "password_verify(" . $password . '): ' . ($isValid ? 'true' : 'false') . PHP_EOL;
}

