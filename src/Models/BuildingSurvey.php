<?php
/**
 * Building Survey Model
 */

class BuildingSurvey
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id)
    {
        return $this->db->fetch('SELECT * FROM building_surveys WHERE id = ?', [$id]);
    }

    public function list(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['building_id'])) { $where[] = 'building_id = ?'; $params[] = $filters['building_id']; }
        if (!empty($filters['status'])) { $where[] = 'status = ?'; $params[] = $filters['status']; }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT * FROM building_surveys {$whereSql} ORDER BY id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data)
    {
        $payload = [
            'building_id' => (int)$data['building_id'],
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? null,
            'survey_type' => $data['survey_type'] ?? 'poll',
            'start_date' => $data['start_date'] ?? date('Y-m-d'),
            'end_date' => $data['end_date'] ?? date('Y-m-d', strtotime('+7 days')),
            'is_anonymous' => !empty($data['is_anonymous']) ? 1 : 0,
            'allow_multiple' => !empty($data['allow_multiple']) ? 1 : 0,
            'status' => $data['status'] ?? 'active',
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        return $this->db->insert('building_surveys', $payload);
    }

    public function update(int $id, array $data)
    {
        $fields = ['title','description','survey_type','start_date','end_date','is_anonymous','allow_multiple','status'];
        $payload = [];
        foreach ($fields as $f) { if (array_key_exists($f, $data)) { $payload[$f] = $data[$f]; } }
        if (empty($payload)) { return 0; }
        return $this->db->update('building_surveys', $payload, 'id = ?', [$id]);
    }

    public function delete(int $id)
    {
        return $this->db->delete('building_surveys', 'id = ?', [$id]);
    }

    /**
     * Anketleri getir
     */
    public function all($filters = [], $limit = null, $offset = 0)
    {
        $sql = "
            SELECT 
                bs.*,
                b.name as building_name,
                u.username as created_by_name,
                COUNT(DISTINCT sq.id) as question_count
            FROM building_surveys bs
            LEFT JOIN buildings b ON bs.building_id = b.id
            LEFT JOIN users u ON bs.created_by = u.id
            LEFT JOIN survey_questions sq ON sq.survey_id = bs.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['building_id'])) {
            $sql .= " AND bs.building_id = ?";
            $params[] = $filters['building_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND bs.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['survey_type'])) {
            $sql .= " AND bs.survey_type = ?";
            $params[] = $filters['survey_type'];
        }

        $sql .= " GROUP BY bs.id ORDER BY bs.created_at DESC";

        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Soruları getir
     */
    public function getQuestions($surveyId)
    {
        $questionModel = new SurveyQuestion();
        return $questionModel->listBySurvey($surveyId);
    }

    /**
     * Cevap sayısını getir
     */
    public function getResponseCount($surveyId): int
    {
        $result = $this->db->fetch(
            "SELECT COUNT(DISTINCT unit_id) as count FROM survey_responses WHERE survey_id = ?",
            [$surveyId]
        );
        return $result['count'] ?? 0;
    }
}

