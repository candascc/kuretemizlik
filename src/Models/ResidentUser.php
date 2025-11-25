<?php
/**
 * Resident User Model
 * Apartman/Site yönetimi - Sakin kullanıcı modeli
 */

class ResidentUser
{
    public const ROLE_OWNER = 'RESIDENT_OWNER';
    public const ROLE_TENANT = 'RESIDENT_TENANT';
    public const ROLE_BOARD = 'RESIDENT_BOARD';
    public const ROLE_GUEST = 'RESIDENT_GUEST';
    public const ROLE_DEFAULT = self::ROLE_TENANT;

    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Sakin kullanıcıları getir
     */
    public function all($filters = [], $limit = null, $offset = 0)
    {
        $sql = "
            SELECT 
                ru.*,
                u.unit_number,
                u.owner_name,
                b.name as building_name
            FROM resident_users ru
            LEFT JOIN units u ON ru.unit_id = u.id
            LEFT JOIN buildings b ON u.building_id = b.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['unit_id'])) {
            $sql .= " AND ru.unit_id = ?";
            $params[] = $filters['unit_id'];
        }

        if (!empty($filters['building_id'])) {
            $sql .= " AND b.id = ?";
            $params[] = $filters['building_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (
                ru.name LIKE ?
                OR ru.email LIKE ?
                OR u.unit_number LIKE ?
            )";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['is_active'])) {
            $sql .= " AND ru.is_active = ?";
            $params[] = $filters['is_active'];
        }

        $sql .= " ORDER BY ru.created_at DESC";

        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        return $this->db->fetchAll($sql, $params);
    }

    public function count(array $filters = []): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM resident_users ru
            LEFT JOIN units u ON ru.unit_id = u.id
            LEFT JOIN buildings b ON u.building_id = b.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['unit_id'])) {
            $sql .= " AND ru.unit_id = ?";
            $params[] = $filters['unit_id'];
        }

        if (!empty($filters['building_id'])) {
            $sql .= " AND b.id = ?";
            $params[] = $filters['building_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (
                ru.name LIKE ?
                OR ru.email LIKE ?
                OR u.unit_number LIKE ?
            )";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['is_active'])) {
            $sql .= " AND ru.is_active = ?";
            $params[] = $filters['is_active'];
        }

        $result = $this->db->fetch($sql, $params);
        return (int)($result['total'] ?? 0);
    }

    /**
     * ID ile sakin getir
     */
    public function find($id)
    {
        return $this->db->fetch(
            "SELECT 
                ru.*,
                u.unit_number,
                u.owner_name,
                b.id as building_id,
                b.name as building_name
            FROM resident_users ru
            LEFT JOIN units u ON ru.unit_id = u.id
            LEFT JOIN buildings b ON u.building_id = b.id
            WHERE ru.id = ?",
            [$id]
        );
    }

    public function findByEmailOrPhone(?string $email, ?string $phone): ?array
    {
        $email = $email ? trim($email) : null;
        $phone = $phone ? self::normalizePhone($phone) : null;
        if (!$email && !$phone) {
            return null;
        }

        $conditions = [];
        $params = [];
        if ($email) {
            $conditions[] = 'email = ?';
            $params[] = $email;
        }
        if ($phone) {
            $conditions[] = "REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '-', ''), '+', '') = ?";
            $params[] = preg_replace('/\D+/', '', $phone);
        }

        // ===== ERR-016 FIX: Use parameterized query instead of string interpolation =====
        if (empty($conditions)) {
            return null;
        }
        $where = implode(' OR ', $conditions);
        // $where is built from safe conditions with placeholders (?), so it's safe
        // Conditions are hardcoded: 'email = ?' or 'REPLACE(...) = ?'
        // No user input is directly interpolated, only parameterized
        // ===== ERR-016 FIX: End =====
        $result = $this->db->fetch(
            "SELECT * FROM resident_users WHERE {$where} LIMIT 1",
            $params
        );

        return $result ?: null;
    }

    public function findByPhone(string $phone): ?array
    {
        $normalized = self::normalizePhone($phone);
        if ($normalized === null || $normalized === '') {
            return null;
        }

        $normalizedDigits = preg_replace('/\D+/', '', $normalized);
        if ($normalizedDigits === '') {
            return null;
        }

        $result = $this->db->fetch(
            "SELECT *,
                    REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '-', ''), '+', '') AS normalized_phone
             FROM resident_users
             WHERE REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '-', ''), '+', '') = ?
             LIMIT 1",
            [$normalizedDigits]
        );

        return $result ?: null;
    }

    public function findByEmail($email)
    {
        $email = trim((string)$email);
        if ($email === '') {
            return null;
        }

        return $this->db->fetch(
            "SELECT * FROM resident_users WHERE email = ?",
            [$email]
        ) ?: null;
    }

    /**
     * Sakin kullanıcı oluştur
     */
    public function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['role'] = self::normalizeRole($data['role'] ?? null);

        if (isset($data['email']) && $data['email'] !== null && $data['email'] !== '') {
            if ($this->findByEmail($data['email'])) {
                throw new Exception("Bu email adresi zaten kullanılıyor");
            }
        } else {
            $data['email'] = null;
        }

        if (isset($data['phone'])) {
            $normalizedPhone = self::normalizePhone($data['phone']);
            $data['phone'] = $normalizedPhone !== '' ? self::formatPhoneForStorage($normalizedPhone) : null;
        }

        return $this->db->insert('resident_users', $data);
    }

    /**
     * Sakin kullanıcı güncelle
     */
    public function update($id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        if (array_key_exists('role', $data)) {
            $data['role'] = self::normalizeRole($data['role']);
        }
        return $this->db->update('resident_users', $data, ['id' => $id]);
    }

    /**
     * Şifre güncelle
     */
    public function updatePassword($id, $password): bool
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        return $this->update($id, [
            'password_hash' => $hash,
            'password_set_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Email doğrulama
     */
    public function verifyEmail($token): bool
    {
        $user = $this->db->fetch(
            "SELECT id FROM resident_users WHERE verification_token = ?",
            [$token]
        );

        if ($user) {
            return $this->update($user['id'], [
                'email_verified' => 1,
                'verification_token' => null
            ]);
        }

        return false;
    }

    /**
     * Son giriş zamanını güncelle
     */
    public function updateLastLogin($id): void
    {
        $this->update($id, ['last_login_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Kimlik doğrulama
     */
    public function authenticate($email, $password): ?array
    {
        $user = $this->findByEmail($email);

        if (!$user || !$user['is_active']) {
            return null;
        }

        $passwordHash = (string)($user['password_hash'] ?? '');
        if (empty($passwordHash) || !password_verify($password, $passwordHash)) {
            return null;
        }

        // ===== ERR-014 FIX: Rehash password if needed (upgrade old hashes) =====
        if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash) {
                try {
                    $this->updatePassword($user['id'], $password);
                } catch (Exception $e) {
                    // Log but don't fail authentication if rehash update fails
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("Password rehash failed for resident user {$user['id']}: " . $e->getMessage());
                    }
                }
            }
        }
        // ===== ERR-014 FIX: End =====

        $this->updateLastLogin($user['id']);
        return $user;
    }

    public static function hasPassword(?array $resident): bool
    {
        if (!$resident) {
            return false;
        }

        $passwordHash = $resident['password_hash'] ?? null;
        return is_string($passwordHash) && trim($passwordHash) !== '';
    }

    public function markOtpIssued(int $residentId, string $context): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->execute(
            "UPDATE resident_users
             SET last_otp_sent_at = ?,
                 otp_context = ?,
                 otp_attempts = CASE WHEN otp_context = ? THEN otp_attempts + 1 ELSE 1 END,
                 updated_at = ?
             WHERE id = ?",
            [$now, $context, $context, $now, $residentId]
        );
    }

    public function incrementOtpAttempt(int $residentId): void
    {
        $this->db->execute(
            "UPDATE resident_users
             SET otp_attempts = otp_attempts + 1,
                 updated_at = ?
             WHERE id = ?",
            [date('Y-m-d H:i:s'), $residentId]
        );
    }

    public function resetOtpState(int $residentId): void
    {
        $this->update($residentId, [
            'otp_attempts' => 0,
            'otp_context' => null,
            'last_otp_sent_at' => null,
        ]);
    }

    private static function normalizePhone(?string $phone): string
    {
        if ($phone === null) {
            return '';
        }
        if ($phone === null) {
            return '';
        }

        if (method_exists('Utils', 'normalizePhone')) {
            $normalized = Utils::normalizePhone($phone);
            if ($normalized !== null) {
                return preg_replace('/\D+/', '', $normalized);
            }
        }

        return preg_replace('/\D+/', '', $phone);
    }

    private static function formatPhoneForStorage(string $digits): string
    {
        if (strlen($digits) === 0) {
            return '';
        }

        if (method_exists('Utils', 'normalizePhone')) {
            $withPlus = Utils::normalizePhone('+' . $digits);
            if ($withPlus !== null) {
                return $withPlus;
            }
        }

        return '+' . ltrim($digits, '+');
    }

    public static function roleOptions(): array
    {
        return [
            self::ROLE_OWNER => 'Kat Maliki',
            self::ROLE_TENANT => 'Kiracı',
            self::ROLE_BOARD => 'Yönetim Kurulu',
            self::ROLE_GUEST => 'Misafir',
        ];
    }

    public static function normalizeRole(?string $role): string
    {
        $role = $role ? strtoupper(trim($role)) : '';
        $options = array_keys(self::roleOptions());
        if (in_array($role, $options, true)) {
            return $role;
        }
        return self::ROLE_DEFAULT;
    }

    public static function roleLabel(?string $role): string
    {
        $role = self::normalizeRole($role);
        return self::roleOptions()[$role] ?? $role;
    }
}

