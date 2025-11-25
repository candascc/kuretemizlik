<?php
/**
 * Comment Model
 * Yorum sistemi modeli
 */

class Comment
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Yorum oluştur
     */
    public function create(array $data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->insert('comments', $data);
    }

    /**
     * Yorum güncelle
     */
    public function update($id, array $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('comments', $data, ['id' => $id]);
    }

    /**
     * Yorum sil (soft delete)
     */
    public function delete($id)
    {
        return $this->update($id, ['status' => 'deleted']);
    }

    /**
     * Yorum getir
     */
    public function find($id)
    {
        return $this->db->fetch(
            "SELECT 
                c.*,
                u.username,
                u.full_name,
                u.avatar,
                COUNT(cr.id) as reaction_count,
                GROUP_CONCAT(cr.reaction_type) as reactions
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            LEFT JOIN comment_reactions cr ON c.id = cr.comment_id
            WHERE c.id = ? AND c.status = 'active'
            GROUP BY c.id",
            [$id]
        );
    }

    /**
     * Entity'ye ait yorumları getir
     */
    public function getByEntity($entityType, $entityId, $options = [])
    {
        $limit = $options['limit'] ?? 50;
        $offset = $options['offset'] ?? 0;
        $includeInternal = $options['include_internal'] ?? false;
        $orderBy = $options['order_by'] ?? 'created_at DESC';

        $where = ['c.entity_type = ?', 'c.entity_id = ?', "c.status = 'active'"];
        $params = [$entityType, $entityId];

        if (!$includeInternal) {
            $where[] = 'c.is_internal = 0';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $sql = "
            SELECT 
                c.*,
                u.username,
                u.full_name,
                u.avatar,
                COUNT(cr.id) as reaction_count,
                GROUP_CONCAT(DISTINCT cr.reaction_type) as reactions,
                COUNT(replies.id) as reply_count
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            LEFT JOIN comment_reactions cr ON c.id = cr.comment_id
            LEFT JOIN comments replies ON c.id = replies.parent_id AND replies.status = 'active'
            {$whereSql}
            GROUP BY c.id
            ORDER BY c.is_pinned DESC, c.{$orderBy}
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Yorumun yanıtlarını getir
     */
    public function getReplies($commentId, $options = [])
    {
        $limit = $options['limit'] ?? 20;
        $offset = $options['offset'] ?? 0;

        $sql = "
            SELECT 
                c.*,
                u.username,
                u.full_name,
                u.avatar,
                COUNT(cr.id) as reaction_count,
                GROUP_CONCAT(DISTINCT cr.reaction_type) as reactions
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            LEFT JOIN comment_reactions cr ON c.id = cr.comment_id
            WHERE c.parent_id = ? AND c.status = 'active'
            GROUP BY c.id
            ORDER BY c.created_at ASC
            LIMIT ? OFFSET ?
        ";

        return $this->db->fetchAll($sql, [$commentId, $limit, $offset]);
    }

    /**
     * Yorum sayısını getir
     */
    public function getCount($entityType, $entityId, $includeInternal = false)
    {
        $where = ['entity_type = ?', 'entity_id = ?', "status = 'active'"];
        $params = [$entityType, $entityId];

        if (!$includeInternal) {
            $where[] = 'is_internal = 0';
        }

        $sql = "SELECT COUNT(*) as count FROM comments WHERE " . implode(' AND ', $where);
        $result = $this->db->fetch($sql, $params);
        
        return $result['count'] ?? 0;
    }

    /**
     * Yorum pinle/unpin
     */
    public function togglePin($id)
    {
        $comment = $this->find($id);
        if (!$comment) {
            return false;
        }

        return $this->update($id, ['is_pinned' => $comment['is_pinned'] ? 0 : 1]);
    }

    /**
     * Yorum reaksiyonu ekle/çıkar
     */
    public function toggleReaction($commentId, $userId, $reactionType)
    {
        // Mevcut reaksiyonu kontrol et
        $existing = $this->db->fetch(
            'SELECT id FROM comment_reactions WHERE comment_id = ? AND user_id = ? AND reaction_type = ?',
            [$commentId, $userId, $reactionType]
        );

        if ($existing) {
            // Reaksiyonu kaldır
            return $this->db->delete('comment_reactions', 'id = ?', [$existing['id']]);
        } else {
            // Reaksiyonu ekle
            return $this->db->insert('comment_reactions', [
                'comment_id' => $commentId,
                'user_id' => $userId,
                'reaction_type' => $reactionType
            ]);
        }
    }

    /**
     * Mention ekle
     */
    public function addMentions($commentId, $mentions)
    {
        if (empty($mentions)) {
            return true;
        }

        foreach ($mentions as $userId) {
            $this->db->insert('comment_mentions', [
                'comment_id' => $commentId,
                'mentioned_user_id' => $userId
            ]);
        }

        return true;
    }

    /**
     * Dosya ekle
     */
    public function addAttachment($commentId, $fileId)
    {
        return $this->db->insert('comment_attachments', [
            'comment_id' => $commentId,
            'file_id' => $fileId
        ]);
    }

    /**
     * Yorumun dosyalarını getir
     */
    public function getAttachments($commentId)
    {
        return $this->db->fetchAll(
            "SELECT 
                ca.*,
                fu.original_name,
                fu.filename,
                fu.file_path,
                fu.file_size,
                fu.mime_type,
                fu.thumbnail_path
            FROM comment_attachments ca
            LEFT JOIN file_uploads fu ON ca.file_id = fu.id
            WHERE ca.comment_id = ?
            ORDER BY ca.created_at ASC",
            [$commentId]
        );
    }

    /**
     * Kullanıcının yorumlarını getir
     */
    public function getByUser($userId, $options = [])
    {
        $limit = $options['limit'] ?? 50;
        $offset = $options['offset'] ?? 0;

        $sql = "
            SELECT 
                c.*,
                u.username,
                u.full_name,
                u.avatar,
                COUNT(cr.id) as reaction_count,
                GROUP_CONCAT(DISTINCT cr.reaction_type) as reactions
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            LEFT JOIN comment_reactions cr ON c.id = cr.comment_id
            WHERE c.user_id = ? AND c.status = 'active'
            GROUP BY c.id
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ";

        return $this->db->fetchAll($sql, [$userId, $limit, $offset]);
    }

    /**
     * Mention edilen yorumları getir
     */
    public function getMentions($userId, $options = [])
    {
        $limit = $options['limit'] ?? 20;
        $offset = $options['offset'] ?? 0;

        $sql = "
            SELECT 
                c.*,
                u.username,
                u.full_name,
                u.avatar,
                COUNT(cr.id) as reaction_count,
                GROUP_CONCAT(DISTINCT cr.reaction_type) as reactions
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            LEFT JOIN comment_reactions cr ON c.id = cr.comment_id
            LEFT JOIN comment_mentions cm ON c.id = cm.comment_id
            WHERE cm.mentioned_user_id = ? AND c.status = 'active'
            GROUP BY c.id
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ";

        return $this->db->fetchAll($sql, [$userId, $limit, $offset]);
    }

    /**
     * Son yorumları getir
     */
    public function getRecent($options = [])
    {
        $limit = $options['limit'] ?? 20;
        $offset = $options['offset'] ?? 0;
        $entityType = $options['entity_type'] ?? null;

        $where = ["c.status = 'active'"];
        $params = [];

        if ($entityType) {
            $where[] = 'c.entity_type = ?';
            $params[] = $entityType;
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $sql = "
            SELECT 
                c.*,
                u.username,
                u.full_name,
                u.avatar,
                COUNT(cr.id) as reaction_count,
                GROUP_CONCAT(DISTINCT cr.reaction_type) as reactions
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            LEFT JOIN comment_reactions cr ON c.id = cr.comment_id
            {$whereSql}
            GROUP BY c.id
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Yorum arama
     */
    public function search($query, $options = [])
    {
        $limit = $options['limit'] ?? 50;
        $offset = $options['offset'] ?? 0;
        $entityType = $options['entity_type'] ?? null;

        $where = ["c.status = 'active'", "c.content LIKE ?"];
        $params = ["%{$query}%"];

        if ($entityType) {
            $where[] = 'c.entity_type = ?';
            $params[] = $entityType;
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $sql = "
            SELECT 
                c.*,
                u.username,
                u.full_name,
                u.avatar,
                COUNT(cr.id) as reaction_count,
                GROUP_CONCAT(DISTINCT cr.reaction_type) as reactions
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            LEFT JOIN comment_reactions cr ON c.id = cr.comment_id
            {$whereSql}
            GROUP BY c.id
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * İstatistikler
     */
    public function getStats($entityType = null, $entityId = null)
    {
        $where = ["status = 'active'"];
        $params = [];

        if ($entityType) {
            $where[] = 'entity_type = ?';
            $params[] = $entityType;
        }

        if ($entityId) {
            $where[] = 'entity_id = ?';
            $params[] = $entityId;
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $sql = "
            SELECT 
                COUNT(*) as total_comments,
                COUNT(CASE WHEN is_internal = 1 THEN 1 END) as internal_comments,
                COUNT(CASE WHEN is_pinned = 1 THEN 1 END) as pinned_comments,
                COUNT(CASE WHEN parent_id IS NOT NULL THEN 1 END) as replies
            FROM comments 
            {$whereSql}
        ";

        return $this->db->fetch($sql, $params);
    }
}
