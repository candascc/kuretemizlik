<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Lib/Database.php';

$db = Database::getInstance();

$now = date('Y-m-d H:i:s');
$defaultPassword = 'Demo123!';
$passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);

$staffAccounts = [
    ['username' => 'admin_demo', 'role' => 'ADMIN', 'email' => 'admin.demo@example.com'],
    ['username' => 'operator_demo', 'role' => 'OPERATOR', 'email' => 'operator.demo@example.com'],
    ['username' => 'finance_demo', 'role' => 'FINANCE', 'email' => 'finance.demo@example.com'],
    ['username' => 'support_demo', 'role' => 'SUPPORT', 'email' => 'support.demo@example.com'],
    ['username' => 'manager_demo', 'role' => 'SITE_MANAGER', 'email' => 'manager.demo@example.com'],
    ['username' => 'superadmin_demo', 'role' => 'SUPERADMIN', 'email' => 'superadmin.demo@example.com'],
];

$staffResults = [];

foreach ($staffAccounts as $account) {
    $existing = $db->fetch('SELECT id FROM users WHERE username = ?', [$account['username']]);
    if ($existing) {
        $db->update('users', [
            'password_hash' => $passwordHash,
            'role' => $account['role'],
            'email' => $account['email'],
            'is_active' => 1,
            'updated_at' => $now,
        ], 'id = ?', [$existing['id']]);
        $staffResults[] = ['username' => $account['username'], 'role' => $account['role'], 'status' => 'updated'];
    } else {
        $db->insert('users', [
            'username' => $account['username'],
            'password_hash' => $passwordHash,
            'role' => $account['role'],
            'email' => $account['email'],
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $staffResults[] = ['username' => $account['username'], 'role' => $account['role'], 'status' => 'created'];
    }
}

$buildingName = 'Demo Residence';
$building = $db->fetch('SELECT id FROM buildings WHERE name = ?', [$buildingName]);

if ($building) {
    $buildingId = $building['id'];
    $buildingStatus = 'existing';
} else {
    $buildingId = $db->insert('buildings', [
        'name' => $buildingName,
        'building_type' => 'site',
        'address_line' => 'Demo Caddesi No:1',
        'district' => 'Kadıköy',
        'city' => 'İstanbul',
        'total_units' => 12,
        'manager_name' => 'Demo Yönetici',
        'manager_email' => 'yonetici@demo.com',
        'manager_phone' => '+90 555 111 22 33',
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $buildingStatus = 'created';
}

$unitsSeed = [
    ['unit_number' => 'A1-01', 'owner_name' => 'Ayşe Korkmaz'],
    ['unit_number' => 'B2-05', 'owner_name' => 'Murat Yıldız'],
];

$unitIds = [];

foreach ($unitsSeed as $index => $unitSeed) {
    $unit = $db->fetch(
        'SELECT id FROM units WHERE building_id = ? AND unit_number = ?',
        [$buildingId, $unitSeed['unit_number']]
    );

    if ($unit) {
        $unitIds[$index] = $unit['id'];
    } else {
        $unitIds[$index] = $db->insert('units', [
            'building_id' => $buildingId,
            'unit_type' => 'daire',
            'floor_number' => $index + 1,
            'unit_number' => $unitSeed['unit_number'],
            'owner_name' => $unitSeed['owner_name'],
            'monthly_fee' => 750.00,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

$residentPassword = 'Resident123!';
$residentHash = password_hash($residentPassword, PASSWORD_DEFAULT);

$residentAccounts = [
    [
        'name' => 'Ayşe Korkmaz',
        'email' => 'ayse.korkmaz.resident@example.com',
        'unit_index' => 0,
        'phone' => '+90 555 444 11 33',
    ],
    [
        'name' => 'Murat Yıldız',
        'email' => 'murat.yildiz.resident@example.com',
        'unit_index' => 1,
        'phone' => '+90 555 666 22 44',
    ],
];

$residentResults = [];

foreach ($residentAccounts as $residentAccount) {
    $unitId = $unitIds[$residentAccount['unit_index']];
    $existingResident = $db->fetch('SELECT id FROM resident_users WHERE email = ?', [$residentAccount['email']]);

    if ($existingResident) {
        $db->update('resident_users', [
            'unit_id' => $unitId,
            'name' => $residentAccount['name'],
            'phone' => $residentAccount['phone'],
            'password_hash' => $residentHash,
            'is_owner' => 1,
            'is_active' => 1,
            'email_verified' => 1,
            'email_verified_at' => $now,
            'phone_verified' => 1,
            'phone_verified_at' => $now,
            'updated_at' => $now,
        ], 'id = ?', [$existingResident['id']]);
        $residentResults[] = ['email' => $residentAccount['email'], 'status' => 'updated'];
    } else {
        $db->insert('resident_users', [
            'unit_id' => $unitId,
            'name' => $residentAccount['name'],
            'email' => $residentAccount['email'],
            'phone' => $residentAccount['phone'],
            'password_hash' => $residentHash,
            'is_owner' => 1,
            'is_active' => 1,
            'email_verified' => 1,
            'email_verified_at' => $now,
            'phone_verified' => 1,
            'phone_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $residentResults[] = ['email' => $residentAccount['email'], 'status' => 'created'];
    }
}

$customerAccounts = [
    [
        'name' => 'Demo Plaza Yönetimi',
        'email' => 'portal.demo@example.com',
        'phone' => '+90 555 777 44 11',
        'notes' => 'Demo müşteri – portal vitrini',
    ],
    [
        'name' => 'Site Yönetimi Demo',
        'email' => 'portal.site@example.com',
        'phone' => '+90 555 888 55 22',
        'notes' => 'Saha demo müşterisi',
    ],
];

$customerResults = [];

foreach ($customerAccounts as $customerAccount) {
    $existingCustomer = $db->fetch(
        'SELECT id FROM customers WHERE email = ? LIMIT 1',
        [$customerAccount['email']]
    );

    if ($existingCustomer) {
        $db->update('customers', [
            'name' => $customerAccount['name'],
            'phone' => $customerAccount['phone'],
            'notes' => $customerAccount['notes'],
            'updated_at' => $now,
        ], 'id = ?', [$existingCustomer['id']]);
        $customerResults[] = ['email' => $customerAccount['email'], 'status' => 'updated'];
    } else {
        $db->insert('customers', [
            'name' => $customerAccount['name'],
            'phone' => $customerAccount['phone'],
            'email' => $customerAccount['email'],
            'notes' => $customerAccount['notes'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $customerResults[] = ['email' => $customerAccount['email'], 'status' => 'created'];
    }
}

echo "=== Yönetici / Personel Hesapları ===\n";
foreach ($staffResults as $staff) {
    echo sprintf(
        "%s (%s) - %s\n",
        $staff['username'],
        $staff['role'],
        $staff['status']
    );
}

echo "\nVarsayılan şifre: {$defaultPassword}\n\n";

echo "=== Sakin Portalı Hesapları ===\n";
foreach ($residentResults as $resident) {
    echo sprintf(
        "%s - %s\n",
        $resident['email'],
        $resident['status']
    );
}

echo "\nSakin portal şifresi: {$residentPassword}\n";
echo "Oluşturulan bina: {$buildingName} ({$buildingStatus})\n";

echo "\n=== Müşteri Portalı Hesapları ===\n";
foreach ($customerResults as $customer) {
    echo sprintf(
        "%s - %s\n",
        $customer['email'],
        $customer['status']
    );
}

echo "\nMüşteri portalı girişi için e-posta veya telefon yeterlidir (şifre gerekmiyor).\n";

