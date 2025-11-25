<?php
/**
 * Contract Template Model
 * Sözleşme şablonları modeli
 */

class ContractTemplate
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Tüm şablonları getir
     */
    public function all($filters = [])
    {
        $sql = "SELECT * FROM contract_templates WHERE 1=1";
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }

        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = ?";
            $params[] = (int)$filters['is_active'];
        }

        if (!empty($filters['is_default'])) {
            $sql .= " AND is_default = ?";
            $params[] = (int)$filters['is_default'];
        }

        if (isset($filters['service_key'])) {
            if ($filters['service_key'] === null) {
                $sql .= " AND service_key IS NULL";
            } else {
                $sql .= " AND service_key = ?";
                $params[] = $filters['service_key'];
            }
        }

        $sql .= " ORDER BY type, name, version DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * ID ile şablon getir
     */
    public function find($id)
    {
        return $this->db->fetch(
            "SELECT * FROM contract_templates WHERE id = ?",
            [$id]
        );
    }

    /**
     * Varsayılan şablonu getir (belirli type için)
     */
    public function getDefault($type = 'cleaning_job')
    {
        return $this->db->fetch(
            "SELECT * FROM contract_templates 
             WHERE type = ? AND is_default = 1 AND is_active = 1 
             ORDER BY version DESC 
             LIMIT 1",
            [$type]
        );
    }

    /**
     * Type ve service_key'ye göre template bul
     * 
     * @param string $type Template type (örn: 'cleaning_job')
     * @param string|null $serviceKey Service key (null = genel template)
     * @param bool $activeOnly Sadece aktif template'ler
     * @return array|null
     */
    public function findByTypeAndServiceKey(
        string $type, 
        ?string $serviceKey = null, 
        bool $activeOnly = true
    ): ?array {
        $sql = "SELECT * FROM contract_templates 
                WHERE type = ?";
        $params = [$type];
        
        if ($serviceKey !== null) {
            $sql .= " AND service_key = ?";
            $params[] = $serviceKey;
        } else {
            $sql .= " AND service_key IS NULL";
        }
        
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY is_default DESC, version DESC LIMIT 1";
        
        $result = $this->db->fetch($sql, $params);
        return $result ?: null; // false -> null
    }

    /**
     * Aktif şablonları getir
     */
    public function getActive($type = null)
    {
        $sql = "SELECT * FROM contract_templates WHERE is_active = 1";
        $params = [];

        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        $sql .= " ORDER BY type, name, version DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Yeni şablon oluştur
     */
    public function create($data)
    {
        $templateData = [
            'type' => $data['type'] ?? 'cleaning_job',
            'name' => $data['name'],
            'version' => $data['version'] ?? '1.0',
            'description' => $data['description'] ?? null,
            'template_text' => $data['template_text'],
            'template_variables' => isset($data['template_variables']) 
                ? (is_string($data['template_variables']) ? $data['template_variables'] : json_encode($data['template_variables']))
                : null,
            'pdf_template_path' => $data['pdf_template_path'] ?? null,
            'service_key' => $data['service_key'] ?? null,
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
            'is_default' => isset($data['is_default']) ? (int)$data['is_default'] : 0,
            'content_hash' => $data['content_hash'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Eğer bu şablon default olarak işaretleniyorsa, aynı type'daki diğer default'ları kaldır
        if ($templateData['is_default'] == 1) {
            $this->db->execute(
                "UPDATE contract_templates SET is_default = 0 WHERE type = ? AND is_default = 1",
                [$templateData['type']]
            );
        }

        return $this->db->insert('contract_templates', $templateData);
    }

    /**
     * Şablon güncelle
     */
    public function update($id, $data)
    {
        $template = $this->find($id);
        if (!$template) {
            return 0;
        }

        $templateData = [];
        $allowed = [
            'type', 'name', 'version', 'description',
            'template_text', 'template_variables', 'pdf_template_path',
            'service_key',
            'is_active', 'is_default', 'content_hash'
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'template_variables' && is_array($data[$field])) {
                    $templateData[$field] = json_encode($data[$field]);
                } else {
                    $templateData[$field] = $data[$field];
                }
            }
        }

        // Eğer is_default değişiyorsa ve 1 yapılıyorsa, aynı type'daki diğer default'ları kaldır
        if (isset($templateData['is_default']) && $templateData['is_default'] == 1) {
            $type = $templateData['type'] ?? $template['type'];
            $this->db->execute(
                "UPDATE contract_templates SET is_default = 0 WHERE type = ? AND is_default = 1 AND id != ?",
                [$type, $id]
            );
        }

        if (empty($templateData)) {
            return 0;
        }

        $templateData['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->update('contract_templates', $templateData, 'id = ?', [$id]);
    }

    /**
     * Şablon sil
     */
    public function delete($id)
    {
        $template = $this->find($id);
        if (!$template) {
            return 0;
        }

        return $this->db->delete('contract_templates', 'id = ?', [$id]);
    }

    /**
     * Şablon sayısı
     */
    public function count($filters = [])
    {
        $sql = "SELECT COUNT(*) as count FROM contract_templates WHERE 1=1";
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }

        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = ?";
            $params[] = (int)$filters['is_active'];
        }

        $result = $this->db->fetch($sql, $params);
        return (int)($result['count'] ?? 0);
    }

    /**
     * İlişki: Bu şablonu kullanan iş sözleşmeleri
     */
    public function jobContracts($templateId)
    {
        return $this->db->fetchAll(
            "SELECT * FROM job_contracts WHERE template_id = ? ORDER BY created_at DESC",
            [$templateId]
        );
    }

    /**
     * İlişki: Şablonu oluşturan kullanıcı
     */
    public function createdBy($templateId)
    {
        $template = $this->find($templateId);
        if (!$template || !$template['created_by']) {
            return null;
        }

        $userModel = new User();
        return $userModel->find($template['created_by']);
    }
}

