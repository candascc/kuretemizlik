<?php
/**
 * Meeting Attendee Model
 */

class MeetingAttendee
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listByMeeting(int $meetingId): array
    {
        return $this->db->fetchAll('SELECT * FROM meeting_attendees WHERE meeting_id = ? ORDER BY id ASC', [$meetingId]);
    }

    public function add(array $data)
    {
        $payload = [
            'meeting_id' => (int)$data['meeting_id'],
            'unit_id' => (int)$data['unit_id'],
            'attendee_name' => $data['attendee_name'] ?? '',
            'is_owner' => !empty($data['is_owner']) ? 1 : 0,
            'proxy_holder' => $data['proxy_holder'] ?? null,
            'attended' => !empty($data['attended']) ? 1 : 0,
            'vote_weight' => isset($data['vote_weight']) ? (float)$data['vote_weight'] : 1.0,
        ];
        return $this->db->insert('meeting_attendees', $payload);
    }

    public function update(int $id, array $data)
    {
        $fields = ['attendee_name','is_owner','proxy_holder','attended','vote_weight'];
        $payload = [];
        foreach ($fields as $f) { if (array_key_exists($f, $data)) { $payload[$f] = $data[$f]; } }
        if (empty($payload)) { return 0; }
        return $this->db->update('meeting_attendees', $payload, 'id = ?', [$id]);
    }

    public function delete(int $id)
    {
        return $this->db->delete('meeting_attendees', 'id = ?', [$id]);
    }
}


