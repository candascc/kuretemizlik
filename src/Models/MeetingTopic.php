<?php
/**
 * Meeting Topic Model
 */

class MeetingTopic
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id)
    {
        return $this->db->fetch('SELECT * FROM meeting_topics WHERE id = ?', [$id]);
    }

    /**
     * Get all topics for a meeting
     */
    public function getByMeeting(int $meetingId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM meeting_topics WHERE meeting_id = ? ORDER BY display_order ASC, id ASC',
            [$meetingId]
        );
    }

    /**
     * Create a topic
     */
    public function create(array $data): int
    {
        $payload = [
            'meeting_id' => (int)$data['meeting_id'],
            'topic_title' => $data['topic_title'] ?? '',
            'topic_description' => $data['topic_description'] ?? null,
            'topic_type' => $data['topic_type'] ?? 'information',
            'voting_enabled' => !empty($data['voting_enabled']) ? 1 : 0,
            'voting_type' => $data['voting_type'] ?? null,
            'voting_options' => isset($data['voting_options']) ? json_encode($data['voting_options']) : null,
            'requires_quorum' => !empty($data['requires_quorum']) ? 1 : 0,
            'quorum_percentage' => isset($data['quorum_percentage']) ? (float)$data['quorum_percentage'] : 50.00,
            'is_approved' => !empty($data['is_approved']) ? 1 : 0,
            'display_order' => isset($data['display_order']) ? (int)$data['display_order'] : 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        return $this->db->insert('meeting_topics', $payload);
    }

    /**
     * Update a topic
     */
    public function update(int $id, array $data): int
    {
        $fields = ['topic_title', 'topic_description', 'topic_type', 'voting_enabled', 'voting_type',
                   'voting_options', 'requires_quorum', 'quorum_percentage', 'is_approved', 'display_order'];
        $payload = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                if ($f === 'voting_options') {
                    $payload[$f] = json_encode($data[$f]);
                } elseif ($f === 'voting_enabled' || $f === 'requires_quorum' || $f === 'is_approved') {
                    $payload[$f] = !empty($data[$f]) ? 1 : 0;
                } else {
                    $payload[$f] = $data[$f];
                }
            }
        }
        if (empty($payload)) {
            return 0;
        }
        return $this->db->update('meeting_topics', $payload, 'id = ?', [$id]);
    }

    /**
     * Delete a topic (cascades votes)
     */
    public function delete(int $id): int
    {
        return $this->db->delete('meeting_topics', 'id = ?', [$id]);
    }

    /**
     * Get vote statistics for a topic
     */
    public function getVoteStats(int $topicId): array
    {
        $topic = $this->find($topicId);
        if (!$topic) {
            return [];
        }

        // Get all votes
        $votes = $this->db->fetchAll(
            'SELECT vote_value, SUM(vote_weight) as total_weight, COUNT(*) as vote_count 
             FROM meeting_votes 
             WHERE topic_id = ? 
             GROUP BY vote_value',
            [$topicId]
        );

        $stats = [
            'yes' => 0, 'no' => 0, 'abstain' => 0,
            'yes_weight' => 0, 'no_weight' => 0, 'abstain_weight' => 0,
            'total_votes' => 0, 'total_weight' => 0,
            'approval_rate' => 0
        ];

        foreach ($votes as $vote) {
            $value = $vote['vote_value'];
            $weight = (float)$vote['total_weight'];
            $count = (int)$vote['vote_count'];

            $stats["{$value}_weight"] = $weight;
            $stats[$value] = $count;
            $stats['total_weight'] += $weight;
            $stats['total_votes'] += $count;
        }

        // Calculate approval rate (yes_weight / (yes_weight + no_weight))
        $totalDecisive = $stats['yes_weight'] + $stats['no_weight'];
        if ($totalDecisive > 0) {
            $stats['approval_rate'] = round(($stats['yes_weight'] / $totalDecisive) * 100, 2);
        }

        return $stats;
    }

    /**
     * Check if topic meets quorum
     */
    public function checkQuorum(int $topicId): array
    {
        $topic = $this->find($topicId);
        if (!$topic || !$topic['requires_quorum']) {
            return ['met' => true, 'current' => 0, 'required' => 0, 'percentage' => 0];
        }

        // Get total vote weight from attendees who attended
        $attendanceWeight = $this->db->fetch(
            'SELECT COALESCE(SUM(vote_weight), 0) as total_weight 
             FROM meeting_attendees 
             WHERE meeting_id = ? AND attended = 1',
            [$topic['meeting_id']]
        )['total_weight'] ?? 0;

        // Get total vote weight from actual votes on this topic
        $votingWeight = $this->db->fetch(
            'SELECT COALESCE(SUM(vote_weight), 0) as total_weight 
             FROM meeting_votes 
             WHERE topic_id = ?',
            [$topicId]
        )['total_weight'] ?? 0;

        $required = (float)$topic['quorum_percentage'];
        $current = $attendanceWeight > 0 ? ($votingWeight / $attendanceWeight) * 100 : 0;

        return [
            'met' => $current >= $required,
            'current' => round($current, 2),
            'required' => $required,
            'percentage' => round($current, 2)
        ];
    }
}

