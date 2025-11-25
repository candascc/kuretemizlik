<?php
/**
 * Customer Model
 */

class Customer
{
    use CompanyScope;
    public const ROLE_STANDARD = 'CUSTOMER_STANDARD';
    public const ROLE_VIP = 'CUSTOMER_VIP';
    public const ROLE_CORPORATE = 'CUSTOMER_CORPORATE';
    public const ROLE_DEFAULT = self::ROLE_STANDARD;

    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Tüm mü�Yterileri getir
     */
    public function all($limit = null, $offset = 0)
    {
        // ===== PRODUCTION FIX: Handle scopeToCompany and database errors gracefully =====
        try {
            $where = $this->scopeToCompany('WHERE 1=1');
            $sql = "SELECT * FROM customers {$where} ORDER BY created_at DESC";
            if ($limit) {
                $sql .= " LIMIT ? OFFSET ?";
                return $this->db->fetchAll($sql, [$limit, $offset]);
            }
            return $this->db->fetchAll($sql);
        } catch (Throwable $e) {
            error_log("Customer::all() error: " . $e->getMessage());
            error_log("SQL: SELECT * FROM customers {$where} ORDER BY created_at DESC");
            // Return empty array instead of crashing
            return [];
        }
        // ===== PRODUCTION FIX END =====
    }
    
    /**
     * ID ile mü�Yteri getir
     */
    public function find($id)
    {
        $where = $this->scopeToCompany('WHERE c.id = ?', 'c');
        return $this->db->fetch("SELECT * FROM customers c {$where}", [$id]);
    }
    
    /**
     * Mü�Yteri ve adreslerini getir
     */
    /**
     * Get customer addresses
     * UX-CRIT-001: Helper for wizard API
     */
    public function getAddresses($customerId)
    {
        $where = $this->scopeToCompany('WHERE c.id = ?', 'c');

        return $this->db->fetchAll(
            "SELECT a.*
             FROM addresses a
             INNER JOIN customers c ON a.customer_id = c.id
             {$where}
             ORDER BY a.id",
            [$customerId]
        );
    }
    
    public function findWithAddresses($id)
    {
        $customer = $this->find($id);
        if (!$customer) return null;
        
        $addresses = $this->getAddresses($id);
        
        $customer['addresses'] = $addresses;
        return $customer;
    }
    
    /**
     * Yeni mü�Yteri olu�Ytur
     */
        /**
     * Telefon numarası ile müşteri bul
     */
    public function findByPhone(string $phone): ?array
    {
        $normalized = self::normalizePhone($phone);
        if ($normalized === '') {
            return null;
        }

        $normalizedDigits = preg_replace('/\D+/', '', $normalized);
        if ($normalizedDigits === '') {
            return null;
        }

        $where = $this->scopeToCompany(
            "WHERE REPLACE(REPLACE(REPLACE(COALESCE(c.phone, ''), ' ', ''), '-', ''), '+', '') = ?",
            'c'
        );

        $result = $this->db->fetch(
            "SELECT c.*,
                    REPLACE(REPLACE(REPLACE(COALESCE(c.phone, ''), ' ', ''), '-', ''), '+', '') AS normalized_phone
             FROM customers c
             {$where}
             LIMIT 1",
            [$normalizedDigits]
        );

        return $result ?: null;
    }
    
    /**
     * Email adresi ile müşteri bul
     */
    public function findByEmail($email)
    {
        $where = $this->scopeToCompany('WHERE c.email = ?', 'c');
        return $this->db->fetch(
            "SELECT * FROM customers c {$where}",
            [$email]
        );
    }
    
    /**
     * Yeni müşteri oluştur
     */
    public function create($data)
    {
        $companyId = $this->getCompanyIdForInsert();

        if (isset($data['phone'])) {
            $normalizedPhone = self::normalizePhone($data['phone']);
            $data['phone'] = $normalizedPhone !== '' ? self::formatPhoneForStorage($normalizedPhone) : null;
        }

        $customerData = [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'role' => self::normalizeRole($data['role'] ?? null),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'company_id' => $companyId
        ];
        
        $customerId = $this->db->insert('customers', $customerData);
        
        // Adresleri ekle
        if (!empty($data['addresses'])) {
            foreach ($data['addresses'] as $address) {
                if (!empty($address['line'])) {
                    $this->db->insert('addresses', [
                        'customer_id' => $customerId,
                        'company_id' => $companyId,
                        'label' => $address['label'] ?? null,
                        'line' => $address['line'],
                        'city' => $address['city'] ?? null,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }
        
        return $customerId;
    }
    
    /**
     * Müşteri güncelle
     * FIXED: Address update logic to prevent orphaned job references (MED-011)
     */
    public function update($id, $data)
    {
        $customer = $this->find($id);
        if (!$customer) {
            return 0;
        }

        if (isset($data['phone'])) {
            $normalizedPhone = self::normalizePhone($data['phone']);
            $data['phone'] = $normalizedPhone !== '' ? self::formatPhoneForStorage($normalizedPhone) : null;
        }

        $customerData = [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (array_key_exists('role', $data)) {
            $customerData['role'] = self::normalizeRole($data['role']);
        }
        
        $result = $this->db->update('customers', $customerData, 'id = ?', [$id]);
        
        // CRITICAL FIX: Update addresses without deleting (preserve IDs for job references)
        if (isset($data['addresses'])) {
            // Wrap address updates in transaction for atomicity
            $customerCompanyId = (int)($customer['company_id'] ?? 0);
            $this->db->transaction(function() use ($id, $data, $customerCompanyId) {
                // Get existing addresses
                $existingAddresses = $this->db->fetchAll(
                    "SELECT * FROM addresses WHERE customer_id = ? ORDER BY id",
                    [$id]
                );
                
                $existingIds = array_column($existingAddresses, 'id');
                $processedIds = [];
                
                // Process incoming addresses
                foreach ($data['addresses'] as $index => $address) {
                    if (empty($address['line'])) {
                        continue; // Skip empty addresses
                    }
                    
                    $addressData = [
                        'label' => $address['label'] ?? null,
                        'line' => $address['line'],
                        'city' => $address['city'] ?? null,
                    ];
                    
                    // If address has ID and exists, UPDATE it (preserve ID)
                    if (!empty($address['id']) && in_array($address['id'], $existingIds)) {
                        $this->db->update('addresses', $addressData, 'id = ?', [$address['id']]);
                        $processedIds[] = $address['id'];
                    } 
                    // If matching existing address by index, UPDATE it (preserve ID)
                    elseif (isset($existingAddresses[$index])) {
                        $existingId = $existingAddresses[$index]['id'];
                        $this->db->update('addresses', $addressData, 'id = ?', [$existingId]);
                        $processedIds[] = $existingId;
                    }
                    // Otherwise, INSERT new address
                    else {
                        $addressData['customer_id'] = $id;
                        $addressData['company_id'] = $customerCompanyId ?: null;
                        $addressData['created_at'] = date('Y-m-d H:i:s');
                        $newId = $this->db->insert('addresses', $addressData);
                        $processedIds[] = $newId;
                    }
                }
                
                // SELF-AUDIT FIX: Simplified error handling for address cleanup
                // Handle removed addresses: soft delete if possible, hard delete if safe, keep if referenced
                $removedIds = array_diff($existingIds, $processedIds);
                if (!empty($removedIds)) {
                    foreach ($removedIds as $removedId) {
                        $this->handleRemovedAddress($removedId);
                    }
                }
            });
        }
        
        return $result;
    }
    
    /**
     * Handle removed address during customer update
     * SELF-AUDIT FIX: Extracted method for cleaner error handling
     * 
     * Strategy:
     * 1. Try soft delete (set is_deleted = 1) if column exists
     * 2. If soft delete fails, check if address is referenced by jobs
     * 3. Hard delete only if no job references exist
     * 4. Otherwise, keep address to prevent orphaned job references
     * 
     * @param int $addressId Address ID to remove
     * @return void
     */
    private function handleRemovedAddress($addressId)
    {
        try {
            // Strategy 1: Soft delete (preferred)
            $this->db->update('addresses', ['is_deleted' => 1], 'id = ?', [$addressId]);
            
        } catch (Exception $softDeleteError) {
            // Soft delete failed (is_deleted column might not exist)
            // Strategy 2: Check job references before hard delete
            try {
                $jobCount = $this->db->fetch(
                    "SELECT COUNT(*) as c FROM jobs WHERE address_id = ?",
                    [$addressId]
                )['c'] ?? 0;
                
                if ($jobCount == 0) {
                    // Strategy 3: Safe to hard delete (no job references)
                    $this->db->delete('addresses', 'id = ?', [$addressId]);
                } else {
                    // Strategy 4: Keep address (has job references, prevents orphans)
                    error_log("Cannot delete address {$addressId}: referenced by {$jobCount} jobs");
                }
                
            } catch (Exception $hardDeleteError) {
                // Even reference check failed, log and continue
                error_log("Address cleanup error for ID {$addressId}: " . $hardDeleteError->getMessage());
            }
        }
    }
    
    /**
     * Mü�Yteri sil
     */
    public function delete($id)
    {
        // Controller'da zaten müşteri bulunmuş ve kontrol edilmiş
        // Burada tekrar find() çağırmaya gerek yok, direkt silme işlemini yap
        // Ama güvenlik için company scope kontrolünü yapalım
        $customer = $this->find($id);
        if (!$customer) {
            error_log("Customer::delete() - Customer not found: ID={$id}");
            return 0;
        }

        // ===== PRODUCTION FIX: Ensure company scope is applied =====
        // Company scope kontrolü - sadece kendi şirketinin müşterisini silebilir
        $companyId = Auth::companyId();
        if ($companyId && isset($customer['company_id']) && $customer['company_id'] != $companyId) {
            error_log("Customer::delete() - Company scope violation: Customer Company={$customer['company_id']}, User Company={$companyId}");
            throw new Exception('Bu müşteriyi silme yetkiniz yok.');
        }
        
        $companyId = $customer['company_id'] ?? 'N/A';
        error_log("Customer::delete() - Starting deletion for customer ID={$id}, Company_ID=" . $companyId);
        
        // Müşteri bulundu, şimdi silme işlemini yap

        // ===== FIX: İlişkili kayıtları otomatik olarak sil (foreign key constraint hatalarını önler) =====
        try {
            error_log("Customer::delete() - Starting deletion process for customer ID={$id}");
            
            // ÖNEMLİ: SQLite'da PRAGMA foreign_keys transaction İÇİNDE çalışmaz!
            // Transaction BAŞLAMADAN ÖNCE foreign_keys'i kapatmalıyız
            $pdo = $this->db->getPdo();
            $foreignKeysWasEnabled = true;
            try {
                $result = $pdo->query("PRAGMA foreign_keys")->fetchColumn();
                $foreignKeysWasEnabled = (bool)$result;
                // Transaction BAŞLAMADAN ÖNCE kapat
                $pdo->exec("PRAGMA foreign_keys = OFF");
                error_log("Customer::delete() - Foreign keys disabled BEFORE transaction (was: " . ($foreignKeysWasEnabled ? 'ON' : 'OFF') . ")");
            } catch (Exception $e) {
                error_log("Customer::delete() - Failed to disable foreign keys: " . $e->getMessage());
                throw $e; // Bu kritik, hata varsa devam etme
            }
            
            // Transaction içinde tüm silme işlemlerini yap
            // NOT: Transaction içinde PRAGMA komutları çalışmaz, bu yüzden transaction dışında kapatıldı
            $this->db->transaction(function() use ($id) {
                error_log("Customer::delete() - Starting transaction for customer ID={$id}");
                
                // 1. Job contracts sil (jobs üzerinden - önce bunları silmeliyiz)
                // Ayrıca approved_customer_id'yi NULL yap (ON DELETE SET NULL)
                try {
                    $jobs = $this->db->fetchAll("SELECT id FROM jobs WHERE customer_id = ?", [$id]);
                    error_log("Customer::delete() - Found " . count($jobs) . " jobs for customer ID={$id}");
                    foreach ($jobs as $job) {
                        // Job contracts'ı sil
                        $this->db->delete('job_contracts', 'job_id = ?', [$job['id']]);
                    }
                    // approved_customer_id'yi NULL yap
                    $this->db->query("UPDATE job_contracts SET approved_customer_id = NULL WHERE approved_customer_id = ?", [$id]);
                } catch (Exception $e) {
                    error_log("Customer::delete job_contracts cleanup failed for customer {$id}: " . $e->getMessage());
                }

                // 2. Money entries sil (jobs'a bağlı olabilir, önce bunları silmeliyiz)
                try {
                    $jobIds = array_column($jobs, 'id');
                    if (!empty($jobIds)) {
                        $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
                        $deletedMoneyEntries = $this->db->query("DELETE FROM money_entries WHERE job_id IN ({$placeholders})", $jobIds)->rowCount();
                        error_log("Customer::delete() - Deleted {$deletedMoneyEntries} money_entries for customer ID={$id}");
                    }
                } catch (Exception $e) {
                    error_log("Customer::delete money_entries cleanup failed for customer {$id}: " . $e->getMessage());
                }

                // 3. Recurring job money entries sil
                try {
                    $recurringJobs = $this->db->fetchAll("SELECT id FROM recurring_jobs WHERE customer_id = ?", [$id]);
                    $recurringJobIds = array_column($recurringJobs, 'id');
                    if (!empty($recurringJobIds)) {
                        $placeholders = implode(',', array_fill(0, count($recurringJobIds), '?'));
                        $deletedRecurringMoneyEntries = $this->db->query("DELETE FROM money_entries WHERE recurring_job_id IN ({$placeholders})", $recurringJobIds)->rowCount();
                        error_log("Customer::delete() - Deleted {$deletedRecurringMoneyEntries} recurring job money_entries for customer ID={$id}");
                    }
                } catch (Exception $e) {
                    error_log("Customer::delete recurring job money_entries cleanup failed for customer {$id}: " . $e->getMessage());
                }

                // 4. Jobs sil (job_contracts ve money_entries'ten sonra)
                try {
                    $deletedJobs = $this->db->delete('jobs', 'customer_id = ?', [$id]);
                    error_log("Customer::delete() - Deleted {$deletedJobs} jobs for customer ID={$id}");
                } catch (Exception $e) {
                    error_log("Customer::delete jobs cleanup failed for customer {$id}: " . $e->getMessage());
                    throw $e; // Re-throw to rollback transaction
                }

                // 5. Recurring job occurrences sil (recurring_jobs'a bağlı)
                try {
                    $recurringJobs = $this->db->fetchAll("SELECT id FROM recurring_jobs WHERE customer_id = ?", [$id]);
                    $recurringJobIds = array_column($recurringJobs, 'id');
                    if (!empty($recurringJobIds)) {
                        $placeholders = implode(',', array_fill(0, count($recurringJobIds), '?'));
                        $deletedOccurrences = $this->db->query("DELETE FROM recurring_job_occurrences WHERE recurring_job_id IN ({$placeholders})", $recurringJobIds)->rowCount();
                        error_log("Customer::delete() - Deleted {$deletedOccurrences} recurring_job_occurrences for customer ID={$id}");
                    }
                } catch (Exception $e) {
                    error_log("Customer::delete recurring_job_occurrences cleanup failed for customer {$id}: " . $e->getMessage());
                }

                // 6. Recurring jobs sil
                try {
                    $deletedRecurring = $this->db->delete('recurring_jobs', 'customer_id = ?', [$id]);
                    error_log("Customer::delete() - Deleted {$deletedRecurring} recurring_jobs for customer ID={$id}");
                } catch (Exception $e) {
                    error_log("Customer::delete recurring_jobs cleanup failed for customer {$id}: " . $e->getMessage());
                    throw $e; // Re-throw to rollback transaction
                }

                // 7. Contracts sil
                try {
                    $deletedContracts = $this->db->delete('contracts', 'customer_id = ?', [$id]);
                    error_log("Customer::delete() - Deleted {$deletedContracts} contracts for customer ID={$id}");
                } catch (Exception $e) {
                    error_log("Customer::delete contracts cleanup failed for customer {$id}: " . $e->getMessage());
                    throw $e; // Re-throw to rollback transaction
                }

                // 8. Appointments sil
                try {
                    $deletedAppointments = $this->db->delete('appointments', 'customer_id = ?', [$id]);
                    error_log("Customer::delete() - Deleted {$deletedAppointments} appointments for customer ID={$id}");
                } catch (Exception $e) {
                    error_log("Customer::delete appointments cleanup failed for customer {$id}: " . $e->getMessage());
                }

                // 9. Email logları sil
                try {
                    $deletedEmailLogs = $this->db->delete('email_logs', 'customer_id = ?', [$id]);
                    error_log("Customer::delete() - Deleted {$deletedEmailLogs} email_logs for customer ID={$id}");
                } catch (Exception $e) {
                    error_log("Customer::delete email_logs cleanup failed for customer {$id}: " . $e->getMessage());
                }

                // 10. Contract OTP tokens sil
                try {
                    $deletedOtpTokens = $this->db->delete('contract_otp_tokens', 'customer_id = ?', [$id]);
                    error_log("Customer::delete() - Deleted {$deletedOtpTokens} contract_otp_tokens for customer ID={$id}");
                } catch (Exception $e) {
                    error_log("Customer::delete contract_otp_tokens cleanup failed for customer {$id}: " . $e->getMessage());
                }

                // 11. Customer login tokens sil
                try {
                    $deletedTokens = $this->db->delete('customer_login_tokens', 'customer_id = ?', [$id]);
                    error_log("Customer::delete() - Deleted {$deletedTokens} customer_login_tokens for customer ID={$id}");
                } catch (Exception $e) {
                    error_log("Customer::delete customer_login_tokens cleanup failed for customer {$id}: " . $e->getMessage());
                }

                // 12. Addresses sil (CASCADE olabilir ama emin olmak için manuel sil)
                try {
                    $deletedAddresses = $this->db->delete('addresses', 'customer_id = ?', [$id]);
                    error_log("Customer::delete() - Deleted {$deletedAddresses} addresses for customer ID={$id}");
                } catch (Exception $e) {
                    error_log("Customer::delete addresses cleanup failed for customer {$id}: " . $e->getMessage());
                    throw $e; // Re-throw to rollback transaction
                }

                // 14. Son olarak müşteriyi sil
                error_log("Customer::delete() - Attempting to delete customer ID={$id}");
                $deleted = $this->db->delete('customers', 'id = ?', [$id]);
                if (!$deleted) {
                    error_log("Customer::delete() - Customer delete returned 0 rows");
                    throw new Exception('Müşteri silinemedi.');
                }
                error_log("Customer::delete() - Successfully deleted customer ID={$id}");
            });
            
            // Foreign keys'i tekrar aç (transaction DIŞINDA, commit'ten sonra)
            // NOT: Transaction commit edildikten sonra foreign_keys'i tekrar açmalıyız
            if ($foreignKeysWasEnabled) {
                try {
                    // Transaction commit edildikten sonra foreign_keys'i aç
                    $pdo->exec("PRAGMA foreign_keys = ON");
                    error_log("Customer::delete() - Foreign keys re-enabled AFTER transaction commit");
                } catch (Exception $e) {
                    error_log("Customer::delete() - Failed to re-enable foreign keys: " . $e->getMessage());
                    // Bu kritik değil, çünkü Database::connect() zaten foreign_keys'i açıyor
                }
            }

            return 1;
        } catch (Exception $e) {
            // Re-throw with more context
            throw new Exception("Müşteri silinirken veritabanı hatası: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Mü�Yteri ara
     */
    public function search($query, $limit = 20)
    {
        $searchTerm = '%' . $query . '%';

        $where = $this->scopeToCompany(
            "WHERE (c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)",
            'c'
        );

        return $this->db->fetchAll(
            "SELECT c.* FROM customers c
             {$where}
             ORDER BY c.name
             LIMIT ?",
            [$searchTerm, $searchTerm, $searchTerm, $limit]
        );
    }
    
    /**
     * Mü�Yteri sayısı
     */
    public function count()
    {
        $where = $this->scopeToCompany('WHERE 1=1');
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM customers {$where}");
        return $result['count'];
    }
    
    /**
     * Mü�Yteri istatistikleri
     */
    public function getStats()
    {
        // OPTIMIZED: Single query for all stats
        $where = $this->scopeToCompany('WHERE 1=1', 'c');

        $stats = $this->db->fetch("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN c.phone IS NOT NULL AND c.phone != '' THEN 1 END) as with_phone,
                COUNT(CASE WHEN c.email IS NOT NULL AND c.email != '' THEN 1 END) as with_email
            FROM customers c
            {$where}
        ");
        
        $total = (int)($stats['total'] ?? 0);
        $withPhone = (int)($stats['with_phone'] ?? 0);
        $withEmail = (int)($stats['with_email'] ?? 0);
        
        return [
            'total' => $total,
            'with_phone' => $withPhone,
            'with_email' => $withEmail,
            'without_contact' => $total - max($withPhone, $withEmail)
        ];
    }
    
    /**
     * Mü�Yteri i�Ylerini getir
     */
    public function getJobs($customerId, $limit = 10)
    {
        if (!$this->find($customerId)) {
            return [];
        }

        $where = $this->scopeToCompany('WHERE j.customer_id = ?', 'j');

        return $this->db->fetchAll(
            "SELECT j.*, s.name as service_name, a.line as address_line
             FROM jobs j
             LEFT JOIN services s ON j.service_id = s.id
             LEFT JOIN addresses a ON j.address_id = a.id
             {$where}
             ORDER BY j.start_at DESC
             LIMIT ?",
            [$customerId, $limit]
        );
    }
    
    /**
     * Mü�Yteri i�Y sayısı
     */
    public function getJobCount($customerId)
    {
        if (!$this->find($customerId)) {
            return 0;
        }

        $where = $this->scopeToCompany('WHERE customer_id = ?');

        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM jobs {$where}",
            [$customerId]
        );
        return $result['count'];
    }
    
    /**
     * Müşterileri adresleriyle birlikte getir (N+1 query optimization)
     */
    public function allWithAddresses($limit = null, $offset = 0)
    {
        $where = $this->scopeToCompany('WHERE 1=1', 'c');

        $sql = "SELECT c.*, 
                       GROUP_CONCAT(a.id || '||' || a.label || '||' || a.line || '||' || a.city, ';;') as addresses
                FROM customers c
                LEFT JOIN addresses a ON c.id = a.customer_id
                {$where}
                GROUP BY c.id
                ORDER BY c.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $customers = $this->db->fetchAll($sql, [$limit, $offset]);
        } else {
            $customers = $this->db->fetchAll($sql);
        }
        
        // Parse addresses for each customer
        foreach ($customers as &$customer) {
            $customer['addresses'] = [];
            if ($customer['addresses']) {
                $addressStrings = explode(';;', $customer['addresses']);
                foreach ($addressStrings as $addrStr) {
                    if (trim($addrStr)) {
                        $parts = explode('||', $addrStr);
                        if (count($parts) >= 4) {
                            $customer['addresses'][] = [
                                'id' => $parts[0],
                                'label' => $parts[1],
                                'line' => $parts[2],
                                'city' => $parts[3]
                            ];
                        }
                    }
                }
            }
        }
        
        return $customers;
    }

    public static function hasPassword(?array $customer): bool
    {
        if (!$customer) {
            return false;
        }

        $passwordHash = $customer['password_hash'] ?? null;
        $passwordSetAt = $customer['password_set_at'] ?? null;

        if (empty($passwordHash)) {
            return false;
        }

        if (is_string($passwordSetAt) && trim($passwordSetAt) !== '') {
            return true;
        }

        return false;
    }

    public function updatePassword(int $customerId, string $password): bool
    {
        if (!$this->find($customerId)) {
            return false;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $updated = $this->db->update(
            'customers',
            [
                'password_hash' => $hash,
                'password_set_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            'id = ?',
            [$customerId]
        );

        return $updated > 0;
    }

    public function markOtpIssued(int $customerId, string $context): void
    {
        if (!$this->find($customerId)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->execute(
            "UPDATE customers
             SET last_otp_sent_at = ?,
                 otp_context = ?,
                 otp_attempts = CASE WHEN otp_context = ? THEN otp_attempts + 1 ELSE 1 END,
                 updated_at = ?
             WHERE id = ?",
            [$now, $context, $context, $now, $customerId]
        );
    }

    public function incrementOtpAttempt(int $customerId): void
    {
        if (!$this->find($customerId)) {
            return;
        }

        $this->db->execute(
            "UPDATE customers
             SET otp_attempts = otp_attempts + 1,
                 updated_at = ?
             WHERE id = ?",
            [date('Y-m-d H:i:s'), $customerId]
        );
    }

    public function resetOtpState(int $customerId): void
    {
        if (!$this->find($customerId)) {
            return;
        }

        $this->db->update(
            'customers',
            [
                'otp_attempts' => 0,
                'otp_context' => null,
                'last_otp_sent_at' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            'id = ?',
            [$customerId]
        );
    }

    private static function normalizePhone(?string $phone): string
    {
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
        if ($digits === '') {
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
            self::ROLE_STANDARD => 'Standart Müşteri',
            self::ROLE_VIP => 'VIP Müşteri',
            self::ROLE_CORPORATE => 'Kurumsal Müşteri',
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
        $normalized = self::normalizeRole($role);
        return self::roleOptions()[$normalized] ?? $normalized;
    }
}

