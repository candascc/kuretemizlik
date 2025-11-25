<?php
/**
 * Building Meeting Model
 */

class BuildingMeeting
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id)
    {
        return $this->db->fetch('SELECT * FROM building_meetings WHERE id = ?', [$id]);
    }

    public function list(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['building_id'])) { $where[] = 'building_id = ?'; $params[] = $filters['building_id']; }
        if (!empty($filters['status'])) { $where[] = 'status = ?'; $params[] = $filters['status']; }
        if (!empty($filters['from'])) { $where[] = 'date(meeting_date) >= date(?)'; $params[] = $filters['from']; }
        if (!empty($filters['to'])) { $where[] = 'date(meeting_date) <= date(?)'; $params[] = $filters['to']; }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT * FROM building_meetings {$whereSql} ORDER BY date(meeting_date) DESC, id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data)
    {
        $payload = [
            'building_id' => (int)$data['building_id'],
            'meeting_type' => $data['meeting_type'] ?? 'regular',
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? null,
            'meeting_date' => $data['meeting_date'] ?? date('Y-m-d'),
            'location' => $data['location'] ?? null,
            'agenda' => $data['agenda'] ?? null,
            'attendance_count' => isset($data['attendance_count']) ? (int)$data['attendance_count'] : 0,
            'quorum_reached' => !empty($data['quorum_reached']) ? 1 : 0,
            'minutes' => $data['minutes'] ?? null,
            'minutes_document_id' => $data['minutes_document_id'] ?? null,
            'status' => $data['status'] ?? 'scheduled',
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        return $this->db->insert('building_meetings', $payload);
    }

    public function update(int $id, array $data)
    {
        $fields = ['meeting_type','title','description','meeting_date','location','agenda','attendance_count','quorum_reached','minutes','minutes_document_id','status'];
        $payload = [];
        foreach ($fields as $f) { if (array_key_exists($f, $data)) { $payload[$f] = $data[$f]; } }
        if (empty($payload)) { return 0; }
        $payload['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('building_meetings', $payload, 'id = ?', [$id]);
    }

    public function delete(int $id)
    {
        return $this->db->delete('building_meetings', 'id = ?', [$id]);
    }

    /**
     * Toplantıları getir (extended version with joins)
     */
    public function all($filters = [], $limit = null, $offset = 0)
    {
        $sql = "
            SELECT 
                bm.*,
                b.name as building_name,
                u.username as created_by_name,
                COUNT(DISTINCT ma.id) as attendee_count
            FROM building_meetings bm
            LEFT JOIN buildings b ON bm.building_id = b.id
            LEFT JOIN users u ON bm.created_by = u.id
            LEFT JOIN meeting_attendees ma ON ma.meeting_id = bm.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['building_id'])) {
            $sql .= " AND bm.building_id = ?";
            $params[] = $filters['building_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND bm.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['meeting_type'])) {
            $sql .= " AND bm.meeting_type = ?";
            $params[] = $filters['meeting_type'];
        }

        $sql .= " GROUP BY bm.id ORDER BY bm.meeting_date DESC";

        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * ID ile toplantı getir (extended version with joins)
     */
    public function findWithAttendees($id)
    {
        $meeting = $this->db->fetch(
            "SELECT 
                bm.*,
                b.name as building_name,
                u.username as created_by_name
            FROM building_meetings bm
            LEFT JOIN buildings b ON bm.building_id = b.id
            LEFT JOIN users u ON bm.created_by = u.id
            WHERE bm.id = ?",
            [$id]
        );

        if ($meeting) {
            $meeting['attendees'] = $this->getAttendees($id);
        }

        return $meeting;
    }

    /**
     * Katılımcıları getir
     */
    public function getAttendees($meetingId)
    {
        return $this->db->fetchAll(
            "SELECT 
                ma.*,
                u.unit_number,
                u.owner_name
            FROM meeting_attendees ma
            LEFT JOIN units u ON ma.unit_id = u.id
            WHERE ma.meeting_id = ?
            ORDER BY ma.attendee_name ASC",
            [$meetingId]
        );
    }

    /**
     * Katılımcı ekle
     */
    public function addAttendee($meetingId, array $data): int
    {
        $data['meeting_id'] = $meetingId;
        return $this->db->insert('meeting_attendees', $data);
    }

    /**
     * Katılım sayısını güncelle
     */
    public function updateAttendanceCount($meetingId): void
    {
        $count = $this->db->fetch(
            "SELECT COUNT(*) as count FROM meeting_attendees WHERE meeting_id = ? AND attended = 1",
            [$meetingId]
        )['count'] ?? 0;

        $this->update($meetingId, ['attendance_count' => $count]);
    }
}

