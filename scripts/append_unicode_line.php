<?php

declare(strict_types=1);

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/append_unicode_line.php <file> <line>\n");
    exit(1);
}

$path = $argv[1];
$line = $argv[2];

if (!is_file($path)) {
    fwrite(STDERR, "File not found: {$path}\n");
    exit(1);
}

$payload = mb_convert_encoding($line . PHP_EOL, 'UTF-16LE', 'UTF-8');

$handle = fopen($path, 'ab');
if ($handle === false) {
    fwrite(STDERR, "Unable to open {$path} for append.\n");
    exit(1);
}

fwrite($handle, $payload);
fclose($handle);

