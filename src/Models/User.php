<?php
/**
 * User Model
 */

class User
{
    use CompanyScope;

    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Tüm kullanıcıları getir
     */
    public function all()
    {
        $where = $this->scopeToCompany('WHERE 1=1', 'u');
        return $this->db->fetchAll("SELECT * FROM users u {$where} ORDER BY created_at DESC");
    }
    
    /**
     * ID ile kullanıcı getir
     */
    public function find($id)
    {
        $where = $this->scopeToCompany('WHERE u.id = ?', 'u');
        return $this->db->fetch("SELECT * FROM users u {$where}", [$id]);
    }
    
    /**
     * Kullanıcı adı ile kullanıcı getir
     */
    public function findByUsername($username)
    {
        $where = $this->scopeToCompany('WHERE u.username = ?', 'u');
        return $this->db->fetch("SELECT * FROM users u {$where}", [$username]);
    }
    
    /**
     * Yeni kullanıcı olu�Ytur
     */
    public function create($data)
    {
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);
        
        if (!isset($data['company_id'])) {
            $data['company_id'] = $this->getCompanyIdForInsert();
        } else {
            $data['company_id'] = (int)$data['company_id'];
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->insert('users', $data);
    }
    
    /**
     * Kullanıcı güncelle
     */
    public function update($id, $data)
    {
        $existing = $this->find($id);
        if (!$existing || !$this->verifyCompanyAccess((int)($existing['company_id'] ?? 0))) {
            return 0;
        }

        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->update('users', $data, 'id = ?', [$id]);
    }
    
    /**
     * Kullanıcı sil
     */
    public function delete($id)
    {
        $existing = $this->find($id);
        if (!$existing || !$this->verifyCompanyAccess((int)($existing['company_id'] ?? 0))) {
            return 0;
        }

        return $this->db->delete('users', 'id = ?', [$id]);
    }
    
    /**
     * Şifre do�Yrula
     */
    public function verifyPassword($id, $password)
    {
        $user = $this->find($id);
        if (!$user) {
            return false;
        }
        
        $passwordHash = (string)($user['password_hash'] ?? '');
        if (empty($passwordHash) || !password_verify($password, $passwordHash)) {
            return false;
        }
        
        // ===== ERR-014 FIX: Rehash password if needed (upgrade old hashes) =====
        if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash) {
                try {
                    $this->update($id, ['password' => $password]);
                } catch (Exception $e) {
                    // Log but don't fail verification if rehash update fails
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("Password rehash failed for user {$id}: " . $e->getMessage());
                    }
                }
            }
        }
        // ===== ERR-014 FIX: End =====
        
        return true;
    }
    
    /**
     * Şifre de�Yi�Ytir
     */
    public function changePassword($id, $newPassword)
    {
        $data = [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->update('users', $data, 'id = ?', [$id]);
    }
    
    /**
     * Kullanıcıyı aktif/pasif yap
     */
    public function toggleActive($id)
    {
        $user = $this->find($id);
        if (!$user) return false;
        
        $newStatus = $user['is_active'] ? 0 : 1;
        
        return $this->db->update('users', [
            'is_active' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);
    }
    
    /**
     * Aktif kullanıcıları getir
     */
    public function getActive()
    {
        $where = $this->scopeToCompany('WHERE u.is_active = 1', 'u');
        return $this->db->fetchAll("SELECT * FROM users u {$where} ORDER BY username");
    }
    
    /**
     * Kullanıcı sayısı
     */
    public function count()
    {
        $where = $this->scopeToCompany('WHERE 1=1', 'u');
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM users u {$where}");
        return $result['count'];
    }
    
    /**
     * Kullanıcı istatistikleri
     */
    public function getStats()
    {
        $total = $this->count();
        $active = $this->db->fetch("SELECT COUNT(*) as count FROM users " . $this->scopeToCompany('WHERE is_active = 1'))['count'];
        $admins = $this->db->fetch("SELECT COUNT(*) as count FROM users " . $this->scopeToCompany("WHERE role = 'ADMIN'"))['count'];
        $operators = $this->db->fetch("SELECT COUNT(*) as count FROM users " . $this->scopeToCompany("WHERE role = 'OPERATOR'"))['count'];
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'admins' => $admins,
            'operators' => $operators
        ];
    }
}
