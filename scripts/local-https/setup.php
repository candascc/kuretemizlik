<?php

declare(strict_types=1);

$rootDir = dirname(__DIR__, 2);
$certDir = $rootDir . '/local-https/certs';
$configDir = $rootDir . '/local-https/config';

if (!is_dir($certDir) && !mkdir($certDir, 0775, true) && !is_dir($certDir)) {
    throw new RuntimeException('Unable to create cert directory: ' . $certDir);
}
if (!is_dir($configDir) && !mkdir($configDir, 0775, true) && !is_dir($configDir)) {
    throw new RuntimeException('Unable to create config directory: ' . $configDir);
}

$openssl = trim((string) shell_exec('where openssl 2> NUL'));
if ($openssl === '') {
    $openssl = trim((string) shell_exec('which openssl 2> /dev/null'));
}
if ($openssl === '') {
    $candidatePaths = [
        'C:/Program Files/Git/usr/bin/openssl.exe',
        'C:/Program Files/OpenSSL-Win64/bin/openssl.exe',
        'C:/Program Files (x86)/OpenSSL-Win32/bin/openssl.exe',
    ];
    foreach ($candidatePaths as $candidate) {
        if (file_exists($candidate)) {
            $openssl = $candidate;
            break;
        }
    }
    if ($openssl === '') {
        fwrite(STDERR, "OpenSSL not found. Please install OpenSSL and ensure it is in your PATH.\n");
        exit(1);
    }
}

$openssl = strtok($openssl, "\r\n");
$opensslCmd = escapeshellarg($openssl);

function run(string $cmd): void
{
    echo "Running: {$cmd}\n";
    exec($cmd, $output, $exitCode);
    if ($exitCode !== 0) {
        throw new RuntimeException('Command failed: ' . $cmd . PHP_EOL . implode(PHP_EOL, $output));
    }
}

$rootKey = $certDir . '/kure-local-rootCA.key';
$rootCrt = $certDir . '/kure-local-rootCA.pem';
$serverKey = $certDir . '/kuretemizlik.local.key';
$serverCsr = $certDir . '/kuretemizlik.local.csr';
$serverCrt = $certDir . '/kuretemizlik.local.crt';
$serverPfx = $certDir . '/kuretemizlik.local.pfx';
$serial = $certDir . '/kure-local-rootCA.srl';
$sanConfig = $configDir . '/kuretemizlik.local.ext';

if (!file_exists($rootKey) || !file_exists($rootCrt)) {
run("{$opensslCmd} genrsa -out \"{$rootKey}\" 4096");
run("{$opensslCmd} req -x509 -new -nodes -key \"{$rootKey}\" -sha256 -days 3650 -out \"{$rootCrt}\" -subj \"/C=TR/ST=Istanbul/O=KureTemizlik/CN=Kure Dev Root CA\"");
}

file_put_contents($sanConfig, <<<EXT
authorityKeyIdentifier=keyid,issuer
basicConstraints=CA:FALSE
keyUsage = digitalSignature, nonRepudiation, keyEncipherment, dataEncipherment
extendedKeyUsage = serverAuth
subjectAltName = @alt_names

[alt_names]
DNS.1 = kuretemizlik.local
DNS.2 = *.kuretemizlik.local
DNS.3 = localhost
IP.1 = 127.0.0.1
IP.2 = ::1
EXT);

run("{$opensslCmd} genrsa -out \"{$serverKey}\" 2048");
run("{$opensslCmd} req -new -key \"{$serverKey}\" -out \"{$serverCsr}\" -subj \"/C=TR/ST=Istanbul/O=KureTemizlik/CN=kuretemizlik.local\"");
run("{$opensslCmd} x509 -req -in \"{$serverCsr}\" -CA \"{$rootCrt}\" -CAkey \"{$rootKey}\" -CAcreateserial -out \"{$serverCrt}\" -days 825 -sha256 -extfile \"{$sanConfig}\"");

if (file_exists($serial)) {
    @unlink($serial);
}
if (file_exists($serverCsr)) {
    @unlink($serverCsr);
}

run("{$opensslCmd} pkcs12 -export -out \"{$serverPfx}\" -inkey \"{$serverKey}\" -in \"{$serverCrt}\" -passout pass:\"\"");

echo "\nCertificates generated in {$certDir}\n";
echo "To trust the root certificate on Windows:\n";
echo "  1. Double click {$rootCrt}\n";
echo "  2. Install Certificate > Local Machine > Trusted Root Certification Authorities\n\n";
echo "To trust on macOS: sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain {$rootCrt}\n";
echo "To trust on Linux with update-ca-certificates, copy to /usr/local/share/ca-certificates/ and run sudo update-ca-certificates\n";
echo "\nNext steps:\n";
echo "  - Run docker-compose -f docker-compose.https.yml up --build\n";
echo "  - Access https://kuretemizlik.local:8443/app/resident/login\n";

