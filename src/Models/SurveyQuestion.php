<?php
/**
 * Survey Question Model
 */

class SurveyQuestion
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listBySurvey(int $surveyId): array
    {
        return $this->db->fetchAll('SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY display_order, id', [$surveyId]);
    }

    public function create(array $data)
    {
        $payload = [
            'survey_id' => (int)$data['survey_id'],
            'question_text' => $data['question_text'] ?? '',
            'question_type' => $data['question_type'] ?? 'single',
            'options' => isset($data['options']) ? (is_array($data['options']) ? json_encode(array_values($data['options'])) : (string)$data['options']) : null,
            'is_required' => !empty($data['is_required']) ? 1 : 0,
            'display_order' => isset($data['display_order']) ? (int)$data['display_order'] : 0,
        ];
        return $this->db->insert('survey_questions', $payload);
    }

    public function update(int $id, array $data)
    {
        $fields = ['question_text','question_type','options','is_required','display_order'];
        $payload = [];
        foreach ($fields as $f) { if (array_key_exists($f, $data)) { $payload[$f] = $data[$f]; } }
        if (empty($payload)) { return 0; }
        return $this->db->update('survey_questions', $payload, 'id = ?', [$id]);
    }

    public function delete(int $id)
    {
        return $this->db->delete('survey_questions', 'id = ?', [$id]);
    }
}


