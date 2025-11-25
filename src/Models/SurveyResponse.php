<?php
/**
 * Survey Response Model
 */

class SurveyResponse
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listBySurvey(int $surveyId, int $limit = 100, int $offset = 0): array
    {
        return $this->db->fetchAll('SELECT * FROM survey_responses WHERE survey_id = ? ORDER BY id DESC LIMIT ? OFFSET ?', [$surveyId, $limit, $offset]);
    }

    public function create(array $data)
    {
        $payload = [
            'survey_id' => (int)$data['survey_id'],
            'question_id' => (int)$data['question_id'],
            'unit_id' => $data['unit_id'] ?? null,
            'respondent_name' => $data['respondent_name'] ?? null,
            'response_data' => is_array($data['response_data'] ?? null) ? json_encode($data['response_data']) : (string)($data['response_data'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
        ];
        return $this->db->insert('survey_responses', $payload);
    }
}


