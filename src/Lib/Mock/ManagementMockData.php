<?php

class ManagementMockData
{
    private const BUILDINGS = [
        ['id' => 1, 'name' => 'Atlas Sitesi'],
        ['id' => 2, 'name' => 'Nova Residence'],
        ['id' => 3, 'name' => 'Palmarya Evleri'],
        ['id' => 4, 'name' => 'Azure Towers'],
    ];

    public static function dashboard(): array
    {
        return [
            'summary' => [
                'buildings_total' => 12,
                'buildings_active' => 11,
                'units_total' => 842,
                'occupancy_rate' => 93.6,
                'fees_outstanding' => 185_320.75,
                'fees_overdue' => 43_210.10,
                'fees_collected' => 896_540.33,
                'collection_rate' => 95.2,
            ],
            'portalStats' => [
                'total' => 612,
                'active' => 524,
                'verified' => 571,
                'unverified' => 41,
                'logged_in_recent' => 148,
            ],
            'pendingPortalInvites' => [
                [
                    'name' => 'Mert Kılıç',
                    'email' => 'mert.kilic@example.com',
                    'created_at' => '2025-11-07 09:32:00',
                    'building_name' => 'Atlas Sitesi',
                    'unit_number' => 'B2-14',
                ],
                [
                    'name' => 'Elif Arslan',
                    'email' => 'elif.arslan@example.com',
                    'created_at' => '2025-11-06 15:10:00',
                    'building_name' => 'Nova Residence',
                    'unit_number' => 'C1-08',
                ],
                [
                    'name' => 'Kemal Yıldırım',
                    'email' => 'kemal.yildirim@example.com',
                    'created_at' => '2025-11-05 19:45:00',
                    'building_name' => 'Palmarya Evleri',
                    'unit_number' => 'D3-05',
                ],
            ],
            'topOutstandingUnits' => [
                [
                    'unit_number' => 'A1-12',
                    'building_name' => 'Atlas Sitesi',
                    'balance' => 12_500.00,
                    'period' => '2025-10',
                ],
                [
                    'unit_number' => 'B2-09',
                    'building_name' => 'Nova Residence',
                    'balance' => 9_870.50,
                    'period' => '2025-10',
                ],
                [
                    'unit_number' => 'C4-02',
                    'building_name' => 'Palmarya Evleri',
                    'balance' => 7_320.90,
                    'period' => '2025-09',
                ],
                [
                    'unit_number' => 'D1-07',
                    'building_name' => 'Azure Towers',
                    'balance' => 6_980.10,
                    'period' => '2025-10',
                ],
                [
                    'unit_number' => 'E2-04',
                    'building_name' => 'Atlas Sitesi',
                    'balance' => 5_450.00,
                    'period' => '2025-09',
                ],
            ],
            'upcomingMeetings' => [
                [
                    'title' => 'Aidat Planı Gözden Geçirme',
                    'meeting_date' => '2025-11-12 19:30:00',
                    'location' => 'Atlas Sitesi Sosyal Alan',
                    'building_name' => 'Atlas Sitesi',
                ],
                [
                    'title' => 'Güvenlik İyileştirme Toplantısı',
                    'meeting_date' => '2025-11-15 20:00:00',
                    'location' => 'Nova Residence Lounge',
                    'building_name' => 'Nova Residence',
                ],
                [
                    'title' => 'Yeni Yıl Hazırlıkları',
                    'meeting_date' => '2025-11-18 18:00:00',
                    'location' => 'Palmarya Etkinlik Salonu',
                    'building_name' => 'Palmarya Evleri',
                ],
            ],
            'upcomingReservations' => [
                [
                    'facility_name' => 'Misafir Suiti',
                    'start_date' => '2025-11-10 14:00:00',
                    'end_date' => '2025-11-12 11:00:00',
                    'resident_name' => 'Seda Gür',
                    'building_name' => 'Atlas Sitesi',
                ],
                [
                    'facility_name' => 'Toplantı Salonu',
                    'start_date' => '2025-11-11 09:00:00',
                    'end_date' => '2025-11-11 12:30:00',
                    'resident_name' => 'Kaan Bozkurt',
                    'building_name' => 'Nova Residence',
                ],
                [
                    'facility_name' => 'Açık Spor Alanı',
                    'start_date' => '2025-11-13 17:00:00',
                    'end_date' => '2025-11-13 19:00:00',
                    'resident_name' => 'Ece Duran',
                    'building_name' => 'Palmarya Evleri',
                ],
            ],
            'recentAnnouncements' => [
                [
                    'title' => 'Kasım Ayı Aidat Hatırlatması',
                    'publish_date' => '2025-11-05',
                    'building_name' => 'Atlas Sitesi',
                ],
                [
                    'title' => 'Kış Bakım Takvimi',
                    'publish_date' => '2025-11-03',
                    'building_name' => 'Nova Residence',
                ],
                [
                    'title' => 'Otopark Yenileme Çalışmaları',
                    'publish_date' => '2025-11-02',
                    'building_name' => 'Palmarya Evleri',
                ],
            ],
        ];
    }

    public static function residents(array $filters, int $page, int $perPage): array
    {
        $residents = self::residentDataset();
        $search = strtolower(trim((string)($filters['search'] ?? '')));
        $buildingFilter = isset($filters['building_id']) ? (int)$filters['building_id'] : null;

        $filtered = array_values(array_filter($residents, function ($resident) use ($search, $buildingFilter) {
            if ($buildingFilter && (int)$resident['building_id'] !== $buildingFilter) {
                return false;
            }
            if ($search === '') {
                return true;
            }
            $haystack = strtolower($resident['name'] . ' ' . $resident['email'] . ' ' . $resident['unit_number']);
            return strpos($haystack, $search) !== false;
        }));

        $total = count($filtered);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        $pageResidents = array_slice($filtered, $offset, $perPage);

        return [
            'portalStats' => [
                'total' => 612,
                'active' => 524,
                'inactive' => 88,
                'verified' => 571,
                'unverified' => 41,
                'logged_in' => 312,
                'logged_in_recent' => 148,
            ],
            'recentPortalLogins' => self::recentPortalLogins(),
            'pendingVerifications' => self::pendingVerifications(),
            'requestStats' => [
                'total' => 74,
                'open' => 18,
                'in_progress' => 9,
                'resolved' => 47,
            ],
            'recentRequests' => self::recentRequests(),
            'residents' => $pageResidents,
            'total_residents' => $total,
            'page' => $page,
            'pages' => $totalPages,
            'buildings' => self::BUILDINGS,
            'alerts' => [],
        ];
    }

    private static function residentDataset(): array
    {
        return [
            [
                'name' => 'Ayşe Korkmaz',
                'email' => 'ayse.korkmaz@example.com',
                'phone' => '+90 532 111 22 33',
                'building_name' => 'Atlas Sitesi',
                'building_id' => 1,
                'unit_number' => 'A1-12',
                'is_active' => 1,
                'email_verified' => 1,
                'last_login_at' => '2025-11-08 21:12:00',
            ],
            [
                'name' => 'Bilal Demir',
                'email' => 'bilal.demir@example.com',
                'phone' => '+90 542 234 45 56',
                'building_name' => 'Nova Residence',
                'building_id' => 2,
                'unit_number' => 'C2-03',
                'is_active' => 1,
                'email_verified' => 0,
                'last_login_at' => '2025-11-06 19:43:00',
            ],
            [
                'name' => 'Ceren Arı',
                'email' => 'ceren.ari@example.com',
                'phone' => '+90 533 778 89 90',
                'building_name' => 'Palmarya Evleri',
                'building_id' => 3,
                'unit_number' => 'D3-05',
                'is_active' => 1,
                'email_verified' => 1,
                'last_login_at' => '2025-11-08 12:08:00',
            ],
            [
                'name' => 'Deniz Öztürk',
                'email' => 'deniz.ozturk@example.com',
                'phone' => '+90 544 998 12 12',
                'building_name' => 'Atlas Sitesi',
                'building_id' => 1,
                'unit_number' => 'B2-09',
                'is_active' => 0,
                'email_verified' => 0,
                'last_login_at' => null,
            ],
            [
                'name' => 'Ezgi Karaca',
                'email' => 'ezgi.karaca@example.com',
                'phone' => '+90 534 554 66 77',
                'building_name' => 'Azure Towers',
                'building_id' => 4,
                'unit_number' => 'E2-04',
                'is_active' => 1,
                'email_verified' => 1,
                'last_login_at' => '2025-11-07 09:25:00',
            ],
            [
                'name' => 'Fikret Kaya',
                'email' => 'fikret.kaya@example.com',
                'phone' => '+90 533 123 45 67',
                'building_name' => 'Nova Residence',
                'building_id' => 2,
                'unit_number' => 'C1-08',
                'is_active' => 1,
                'email_verified' => 1,
                'last_login_at' => '2025-11-09 08:14:00',
            ],
            [
                'name' => 'Gizem Çetin',
                'email' => 'gizem.cetin@example.com',
                'phone' => '+90 535 765 43 21',
                'building_name' => 'Palmarya Evleri',
                'building_id' => 3,
                'unit_number' => 'D1-02',
                'is_active' => 1,
                'email_verified' => 1,
                'last_login_at' => '2025-11-08 18:50:00',
            ],
            [
                'name' => 'Hasan Uçar',
                'email' => 'hasan.ucar@example.com',
                'phone' => '+90 532 909 87 65',
                'building_name' => 'Atlas Sitesi',
                'building_id' => 1,
                'unit_number' => 'A4-11',
                'is_active' => 1,
                'email_verified' => 0,
                'last_login_at' => '2025-10-30 10:05:00',
            ],
            [
                'name' => 'Işıl Tekin',
                'email' => 'isil.tekin@example.com',
                'phone' => '+90 512 343 98 76',
                'building_name' => 'Azure Towers',
                'building_id' => 4,
                'unit_number' => 'E1-06',
                'is_active' => 1,
                'email_verified' => 1,
                'last_login_at' => '2025-11-08 07:28:00',
            ],
            [
                'name' => 'Kemal Doğan',
                'email' => 'kemal.dogan@example.com',
                'phone' => '+90 537 678 90 12',
                'building_name' => 'Nova Residence',
                'building_id' => 2,
                'unit_number' => 'C3-10',
                'is_active' => 0,
                'email_verified' => 0,
                'last_login_at' => null,
            ],
            [
                'name' => 'Leyla Sezgin',
                'email' => 'leyla.sezgin@example.com',
                'phone' => '+90 533 456 78 91',
                'building_name' => 'Palmarya Evleri',
                'building_id' => 3,
                'unit_number' => 'D5-01',
                'is_active' => 1,
                'email_verified' => 1,
                'last_login_at' => '2025-11-09 06:52:00',
            ],
            [
                'name' => 'Murat Yıldız',
                'email' => 'murat.yildiz@example.com',
                'phone' => '+90 546 335 77 88',
                'building_name' => 'Atlas Sitesi',
                'building_id' => 1,
                'unit_number' => 'B1-03',
                'is_active' => 1,
                'email_verified' => 1,
                'last_login_at' => '2025-11-08 22:10:00',
            ],
        ];
    }

    private static function recentPortalLogins(): array
    {
        return [
            [
                'name' => 'Ayşe Korkmaz',
                'email' => 'ayse.korkmaz@example.com',
                'last_login_at' => '2025-11-08 21:12:00',
                'building_name' => 'Atlas Sitesi',
                'unit_number' => 'A1-12',
            ],
            [
                'name' => 'Fikret Kaya',
                'email' => 'fikret.kaya@example.com',
                'last_login_at' => '2025-11-08 19:21:00',
                'building_name' => 'Nova Residence',
                'unit_number' => 'C1-08',
            ],
            [
                'name' => 'Gizem Çetin',
                'email' => 'gizem.cetin@example.com',
                'last_login_at' => '2025-11-08 18:50:00',
                'building_name' => 'Palmarya Evleri',
                'unit_number' => 'D1-02',
            ],
            [
                'name' => 'Ezgi Karaca',
                'email' => 'ezgi.karaca@example.com',
                'last_login_at' => '2025-11-08 17:05:00',
                'building_name' => 'Azure Towers',
                'unit_number' => 'E2-04',
            ],
            [
                'name' => 'Murat Yıldız',
                'email' => 'murat.yildiz@example.com',
                'last_login_at' => '2025-11-08 22:10:00',
                'building_name' => 'Atlas Sitesi',
                'unit_number' => 'B1-03',
            ],
            [
                'name' => 'Ceren Arı',
                'email' => 'ceren.ari@example.com',
                'last_login_at' => '2025-11-08 12:08:00',
                'building_name' => 'Palmarya Evleri',
                'unit_number' => 'D3-05',
            ],
            [
                'name' => 'Işıl Tekin',
                'email' => 'isil.tekin@example.com',
                'last_login_at' => '2025-11-08 07:28:00',
                'building_name' => 'Azure Towers',
                'unit_number' => 'E1-06',
            ],
            [
                'name' => 'Hasan Uçar',
                'email' => 'hasan.ucar@example.com',
                'last_login_at' => '2025-10-30 10:05:00',
                'building_name' => 'Atlas Sitesi',
                'unit_number' => 'A4-11',
            ],
        ];
    }

    private static function pendingVerifications(): array
    {
        return [
            [
                'name' => 'Bilal Demir',
                'email' => 'bilal.demir@example.com',
                'created_at' => '2025-11-06 19:43:00',
                'building_name' => 'Nova Residence',
                'unit_number' => 'C2-03',
            ],
            [
                'name' => 'Deniz Öztürk',
                'email' => 'deniz.ozturk@example.com',
                'created_at' => '2025-11-05 14:22:00',
                'building_name' => 'Atlas Sitesi',
                'unit_number' => 'B2-09',
            ],
            [
                'name' => 'Kemal Doğan',
                'email' => 'kemal.dogan@example.com',
                'created_at' => '2025-11-04 09:12:00',
                'building_name' => 'Nova Residence',
                'unit_number' => 'C3-10',
            ],
        ];
    }

    private static function recentRequests(): array
    {
        return [
            [
                'subject' => 'Otopark bariyer arızası',
                'request_type' => 'maintenance',
                'priority' => 'urgent',
                'status' => 'open',
                'created_at' => '2025-11-08 08:15:00',
                'building_name' => 'Atlas Sitesi',
                'unit_number' => 'A1-12',
                'resident_name' => 'Ayşe Korkmaz',
            ],
            [
                'subject' => 'Asansör yıllık bakım talebi',
                'request_type' => 'maintenance',
                'priority' => 'high',
                'status' => 'in_progress',
                'created_at' => '2025-11-07 17:40:00',
                'building_name' => 'Nova Residence',
                'unit_number' => 'C2-03',
                'resident_name' => 'Bilal Demir',
            ],
            [
                'subject' => 'Aidat dekontu talebi',
                'request_type' => 'finance',
                'priority' => 'normal',
                'status' => 'resolved',
                'created_at' => '2025-11-06 11:05:00',
                'building_name' => 'Palmarya Evleri',
                'unit_number' => 'D3-05',
                'resident_name' => 'Ceren Arı',
            ],
            [
                'subject' => 'Havuz kullanım saatleri',
                'request_type' => 'info',
                'priority' => 'normal',
                'status' => 'open',
                'created_at' => '2025-11-05 15:32:00',
                'building_name' => 'Azure Towers',
                'unit_number' => 'E2-04',
                'resident_name' => 'Ezgi Karaca',
            ],
        ];
    }
}

