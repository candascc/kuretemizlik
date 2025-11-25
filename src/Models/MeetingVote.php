<?php
/**
 * Meeting Vote Model
 */

class MeetingVote
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get vote by ID
     */
    public function find(int $id)
    {
        return $this->db->fetch('SELECT * FROM meeting_votes WHERE id = ?', [$id]);
    }

    /**
     * Get votes for a topic
     */
    public function getByTopic(int $topicId): array
    {
        return $this->db->fetchAll(
            "SELECT mv.*, ma.attendee_name, ma.unit_id, ma.vote_weight as attendee_vote_weight,
                    u.unit_number
             FROM meeting_votes mv
             JOIN meeting_attendees ma ON mv.attendee_id = ma.id
             LEFT JOIN units u ON ma.unit_id = u.id
             WHERE mv.topic_id = ?
             ORDER BY mv.created_at DESC",
            [$topicId]
        );
    }

    /**
     * Get votes for a meeting
     */
    public function getByMeeting(int $meetingId): array
    {
        return $this->db->fetchAll(
            "SELECT mv.*, ma.attendee_name, ma.unit_id, mt.topic_title
             FROM meeting_votes mv
             JOIN meeting_attendees ma ON mv.attendee_id = ma.id
             JOIN meeting_topics mt ON mv.topic_id = mt.id
             WHERE mv.meeting_id = ?
             ORDER BY mt.display_order ASC, mv.created_at DESC",
            [$meetingId]
        );
    }

    /**
     * Get vote by attendee and topic
     */
    public function getByAttendeeAndTopic(int $attendeeId, int $topicId)
    {
        return $this->db->fetch(
            'SELECT * FROM meeting_votes WHERE attendee_id = ? AND topic_id = ?',
            [$attendeeId, $topicId]
        );
    }

    /**
     * Submit a vote
     */
    public function submit(array $data): int
    {
        // Validate vote value
        $allowedValues = ['yes', 'no', 'abstain'];
        
        // If multi_choice, allow numeric option indices
        $topicModel = new MeetingTopic();
        $topic = $topicModel->find($data['topic_id']);
        if ($topic && $topic['voting_type'] === 'multi_choice') {
            $options = json_decode($topic['voting_options'], true) ?? [];
            for ($i = 0; $i < count($options); $i++) {
                $allowedValues[] = (string)$i;
            }
        }

        $voteValue = $data['vote_value'];
        if (!in_array($voteValue, $allowedValues)) {
            throw new Exception('Invalid vote value');
        }

        // Get attendee's vote_weight
        $attendee = $this->db->fetch(
            'SELECT * FROM meeting_attendees WHERE id = ?',
            [$data['attendee_id']]
        );
        if (!$attendee) {
            throw new Exception('Attendee not found');
        }

        // Check if attendee already voted
        $existing = $this->getByAttendeeAndTopic($data['attendee_id'], $data['topic_id']);
        if ($existing) {
            // Update existing vote
            return $this->db->update('meeting_votes', [
                'vote_value' => $voteValue,
                'comment' => $data['comment'] ?? null
            ], 'id = ?', [$existing['id']]);
        }

        // Insert new vote
        $payload = [
            'meeting_id' => (int)$data['meeting_id'],
            'topic_id' => (int)$data['topic_id'],
            'attendee_id' => (int)$data['attendee_id'],
            'vote_value' => $voteValue,
            'vote_weight' => (float)$attendee['vote_weight'],
            'comment' => $data['comment'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        return $this->db->insert('meeting_votes', $payload);
    }

    /**
     * Update vote comment
     */
    public function updateComment(int $voteId, string $comment): int
    {
        return $this->db->update('meeting_votes', [
            'comment' => $comment
        ], 'id = ?', [$voteId]);
    }

    /**
     * Delete a vote
     */
    public function delete(int $id): int
    {
        return $this->db->delete('meeting_votes', 'id = ?', [$id]);
    }

    /**
     * Get topic approval status
     */
    public function getTopicApprovalStatus(int $topicId): array
    {
        $topicModel = new MeetingTopic();
        $topic = $topicModel->find($topicId);
        if (!$topic || !$topic['voting_enabled']) {
            return ['approved' => false, 'reason' => 'Voting not enabled'];
        }

        $stats = $topicModel->getVoteStats($topicId);
        $quorum = $topicModel->checkQuorum($topicId);

        // Check if quorum is met
        if ($topic['requires_quorum'] && !$quorum['met']) {
            return [
                'approved' => false,
                'reason' => 'Quorum not met',
                'quorum' => $quorum,
                'stats' => $stats
            ];
        }

        // Check approval rate (default threshold: 50% yes votes by weight)
        $approved = $stats['approval_rate'] >= 50;

        return [
            'approved' => $approved,
            'approval_rate' => $stats['approval_rate'],
            'stats' => $stats,
            'quorum' => $quorum
        ];
    }
}

