<?php
/**
 * Comment Controller
 * Yorum sistemi kontrolcüsü
 */

class CommentController
{
    private $commentModel;
    private $db;

    public function __construct()
    {
        $this->commentModel = new Comment();
        $this->db = Database::getInstance();
    }

    /**
     * Yorum oluştur
     */
    public function create()
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            return View::json(['success' => false, 'error' => 'CSRF doğrulaması başarısız'], 403);
        }

        try {
            $entityType = $_POST['entity_type'] ?? '';
            $entityId = (int)($_POST['entity_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $isInternal = !empty($_POST['is_internal']);
            $mentions = $_POST['mentions'] ?? [];
            $attachments = $_POST['attachments'] ?? [];

            // Validasyon
            if (empty($entityType) || !$entityId) {
                throw new Exception('Geçersiz entity bilgisi');
            }

            if (empty($content)) {
                throw new Exception('Yorum içeriği boş olamaz');
            }

            if (strlen($content) > 2000) {
                throw new Exception('Yorum çok uzun (maksimum 2000 karakter)');
            }

            // Yorum oluştur
            $commentId = $this->commentModel->create([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'parent_id' => $parentId,
                'user_id' => Auth::id(),
                'content' => $content,
                'is_internal' => $isInternal ? 1 : 0
            ]);

            // Mention'ları ekle
            if (!empty($mentions)) {
                $this->commentModel->addMentions($commentId, $mentions);
            }

            // Dosya ekleri
            if (!empty($attachments)) {
                foreach ($attachments as $fileId) {
                    $this->commentModel->addAttachment($commentId, $fileId);
                }
            }

            // Activity log
            ActivityLogger::log('comment.created', $entityType, $entityId, [
                'comment_id' => $commentId,
                'is_internal' => $isInternal
            ]);

            ResponseFormatter::success([
                'comment_id' => $commentId,
                'message' => 'Yorum başarıyla eklendi'
            ]);

        } catch (Exception $e) {
            ResponseFormatter::error($e->getMessage(), 400);
        }
    }

    /**
     * Yorum güncelle
     */
    public function update($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            return View::json(['success' => false, 'error' => 'CSRF doğrulaması başarısız'], 403);
        }

        try {
            $comment = $this->commentModel->find($id);
            if (!$comment) {
                throw new Exception('Yorum bulunamadı');
            }

            // Yetki kontrolü
            if ($comment['user_id'] != Auth::id() && !Auth::hasRole('admin')) {
                throw new Exception('Bu yorumu düzenleme yetkiniz yok');
            }

            $content = trim($_POST['content'] ?? '');
            if (empty($content)) {
                throw new Exception('Yorum içeriği boş olamaz');
            }

            $this->commentModel->update($id, ['content' => $content]);

            ActivityLogger::log('comment.updated', 'comment', $id);

            ResponseFormatter::success(['message' => 'Yorum güncellendi']);

        } catch (Exception $e) {
            ResponseFormatter::error($e->getMessage(), 400);
        }
    }

    /**
     * Yorum sil
     */
    public function delete($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            return View::json(['success' => false, 'error' => 'CSRF doğrulaması başarısız'], 403);
        }

        try {
            $comment = $this->commentModel->find($id);
            if (!$comment) {
                throw new Exception('Yorum bulunamadı');
            }

            // Yetki kontrolü
            if ($comment['user_id'] != Auth::id() && !Auth::hasRole('admin')) {
                throw new Exception('Bu yorumu silme yetkiniz yok');
            }

            $this->commentModel->delete($id);

            ActivityLogger::log('comment.deleted', 'comment', $id);

            ResponseFormatter::success(['message' => 'Yorum silindi']);

        } catch (Exception $e) {
            ResponseFormatter::error($e->getMessage(), 400);
        }
    }

    /**
     * Entity yorumlarını getir
     */
    public function getByEntity()
    {
        Auth::require();

        try {
            $entityType = $_GET['entity_type'] ?? '';
            $entityId = (int)($_GET['entity_id'] ?? 0);
            $includeInternal = !empty($_GET['include_internal']);
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);

            if (empty($entityType) || !$entityId) {
                throw new Exception('Geçersiz entity bilgisi');
            }

            $comments = $this->commentModel->getByEntity($entityType, $entityId, [
                'include_internal' => $includeInternal,
                'limit' => $limit,
                'offset' => $offset
            ]);

            // Her yorum için yanıtları getir
            foreach ($comments as &$comment) {
                $comment['replies'] = $this->commentModel->getReplies($comment['id'], ['limit' => 5]);
                $comment['attachments'] = $this->commentModel->getAttachments($comment['id']);
            }

            ResponseFormatter::success([
                'comments' => $comments,
                'total' => $this->commentModel->getCount($entityType, $entityId, $includeInternal)
            ]);

        } catch (Exception $e) {
            ResponseFormatter::error($e->getMessage(), 400);
        }
    }

    /**
     * Yorum yanıtlarını getir
     */
    public function getReplies($id)
    {
        Auth::require();

        try {
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = (int)($_GET['offset'] ?? 0);

            $replies = $this->commentModel->getReplies($id, [
                'limit' => $limit,
                'offset' => $offset
            ]);

            // Her yanıt için dosyaları getir
            foreach ($replies as &$reply) {
                $reply['attachments'] = $this->commentModel->getAttachments($reply['id']);
            }

            ResponseFormatter::success(['replies' => $replies]);

        } catch (Exception $e) {
            ResponseFormatter::error($e->getMessage(), 400);
        }
    }

    /**
     * Yorum pinle/unpin
     */
    public function togglePin($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            return View::json(['success' => false, 'error' => 'CSRF doğrulaması başarısız'], 403);
        }

        try {
            $comment = $this->commentModel->find($id);
            if (!$comment) {
                throw new Exception('Yorum bulunamadı');
            }

            // Sadece admin pinleyebilir
            if (!Auth::hasRole('admin')) {
                throw new Exception('Bu işlem için yetkiniz yok');
            }

            $this->commentModel->togglePin($id);

            ResponseFormatter::success(['message' => 'Pin durumu güncellendi']);

        } catch (Exception $e) {
            ResponseFormatter::error($e->getMessage(), 400);
        }
    }

    /**
     * Reaksiyon ekle/çıkar
     */
    public function toggleReaction($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            return View::json(['success' => false, 'error' => 'CSRF doğrulaması başarısız'], 403);
        }

        try {
            $reactionType = $_POST['reaction_type'] ?? '';
            $userId = Auth::id();

            if (empty($reactionType)) {
                throw new Exception('Reaksiyon türü belirtilmelidir');
            }

            $validReactions = ['like', 'dislike', 'helpful', 'urgent'];
            if (!in_array($reactionType, $validReactions)) {
                throw new Exception('Geçersiz reaksiyon türü');
            }

            $this->commentModel->toggleReaction($id, $userId, $reactionType);

            ResponseFormatter::success(['message' => 'Reaksiyon güncellendi']);

        } catch (Exception $e) {
            ResponseFormatter::error($e->getMessage(), 400);
        }
    }

    /**
     * Kullanıcının yorumlarını getir
     */
    public function getByUser()
    {
        Auth::require();

        try {
            $userId = (int)($_GET['user_id'] ?? Auth::id());
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);

            $comments = $this->commentModel->getByUser($userId, [
                'limit' => $limit,
                'offset' => $offset
            ]);

            ResponseFormatter::success(['comments' => $comments]);

        } catch (Exception $e) {
            ResponseFormatter::error($e->getMessage(), 400);
        }
    }

    /**
     * Mention edilen yorumları getir
     */
    public function getMentions()
    {
        Auth::require();

        try {
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = (int)($_GET['offset'] ?? 0);

            $comments = $this->commentModel->getMentions(Auth::id(), [
                'limit' => $limit,
                'offset' => $offset
            ]);

            ResponseFormatter::success(['comments' => $comments]);

        } catch (Exception $e) {
            ResponseFormatter::error($e->getMessage(), 400);
        }
    }

    /**
     * Son yorumları getir
     */
    public function getRecent()
    {
        Auth::require();

        try {
            $entityType = $_GET['entity_type'] ?? null;
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = (int)($_GET['offset'] ?? 0);

            $comments = $this->commentModel->getRecent([
                'entity_type' => $entityType,
                'limit' => $limit,
                'offset' => $offset
            ]);

            ResponseFormatter::success(['comments' => $comments]);

        } catch (Exception $e) {
            ResponseFormatter::error($e->getMessage(), 400);
        }
    }

    /**
     * Yorum ara
     */
    public function search()
    {
        Auth::require();

        try {
            $query = $_GET['q'] ?? '';
            $entityType = $_GET['entity_type'] ?? null;
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);

            if (empty($query)) {
                throw new Exception('Arama terimi belirtilmelidir');
            }

            $comments = $this->commentModel->search($query, [
                'entity_type' => $entityType,
                'limit' => $limit,
                'offset' => $offset
            ]);

            ResponseFormatter::success(['comments' => $comments]);

        } catch (Exception $e) {
            ResponseFormatter::error($e->getMessage(), 400);
        }
    }

    /**
     * İstatistikler
     */
    public function getStats()
    {
        Auth::require();

        try {
            $entityType = $_GET['entity_type'] ?? null;
            $entityId = !empty($_GET['entity_id']) ? (int)$_GET['entity_id'] : null;

            $stats = $this->commentModel->getStats($entityType, $entityId);

            ResponseFormatter::success(['stats' => $stats]);

        } catch (Exception $e) {
            ResponseFormatter::error($e->getMessage(), 400);
        }
    }
}
