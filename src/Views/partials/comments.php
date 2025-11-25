<?php
/**
 * Comments Component
 * Yorum sistemi bileşeni
 */

$entityType = $entityType ?? '';
$entityId = $entityId ?? 0;
$showInternal = $showInternal ?? false;
$allowCreate = $allowCreate ?? true;
$maxHeight = $maxHeight ?? '400px';
?>

<div id="comments-container" 
     class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
     x-data="commentsSystem(<?= $entityId ?>, '<?= $entityType ?>', <?= $showInternal ? 'true' : 'false' ?>)"
     x-init="loadComments()">
    
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                <i class="fas fa-comments mr-2 text-primary-600"></i>
                Yorumlar
                <span x-show="total > 0" class="ml-2 text-sm text-gray-500" x-text="'(' + total + ')'"></span>
            </h3>
            <div class="flex items-center space-x-2">
                <button @click="toggleInternal()" 
                        class="text-sm px-3 py-1 rounded-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                        :class="{'bg-primary-100 text-primary-700 border-primary-300': showInternal}">
                    <i class="fas fa-eye-slash mr-1"></i>
                    İç Yorumlar
                </button>
                <button @click="refreshComments()" 
                        class="text-sm px-3 py-1 rounded-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <i class="fas fa-sync-alt mr-1"></i>
                    Yenile
                </button>
            </div>
        </div>
    </div>

    <!-- Comments List -->
    <div class="max-h-<?= str_replace('px', '', $maxHeight) ?> overflow-y-auto" 
         style="max-height: <?= $maxHeight ?>">
        
        <!-- Loading -->
        <div x-show="loading" class="p-4 text-center">
            <div class="inline-flex items-center space-x-2">
                <div class="w-4 h-4 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
                <span class="text-gray-500">Yorumlar yükleniyor...</span>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && comments.length === 0" class="p-8 text-center">
            <i class="fas fa-comments text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400">Henüz yorum yapılmamış</p>
        </div>

        <!-- Comments -->
        <div x-show="!loading && comments.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
            <template x-for="comment in comments" :key="comment.id">
                <div class="p-4" :class="{'bg-yellow-50 dark:bg-yellow-900/20': comment.is_pinned}">
                    <!-- Comment Header -->
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-primary-600 dark:text-primary-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="comment.full_name || comment.username"></p>
                                <p class="text-xs text-gray-500" x-text="formatDate(comment.created_at)"></p>
                            </div>
                            <div x-show="comment.is_internal" class="px-2 py-1 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 text-xs rounded-full">
                                İç Yorum
                            </div>
                            <div x-show="comment.is_pinned" class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 text-xs rounded-full">
                                <i class="fas fa-thumbtack mr-1"></i>
                                Sabitlenmiş
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button @click="toggleReaction(comment.id, 'like')" 
                                    class="text-gray-400 hover:text-red-500 transition-colors">
                                <i class="fas fa-heart" :class="{'text-red-500': comment.reactions && comment.reactions.includes('like')}"></i>
                            </button>
                            <button @click="toggleReaction(comment.id, 'helpful')" 
                                    class="text-gray-400 hover:text-green-500 transition-colors">
                                <i class="fas fa-thumbs-up" :class="{'text-green-500': comment.reactions && comment.reactions.includes('helpful')}"></i>
                            </button>
                            <button @click="toggleReaction(comment.id, 'urgent')" 
                                    class="text-gray-400 hover:text-orange-500 transition-colors">
                                <i class="fas fa-exclamation-triangle" :class="{'text-orange-500': comment.reactions && comment.reactions.includes('urgent')}"></i>
                            </button>
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div x-show="open" @click.away="open = false" 
                                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-10">
                                    <div class="py-1">
                                        <button @click="replyToComment(comment.id)" 
                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <i class="fas fa-reply mr-2"></i>
                                            Yanıtla
                                        </button>
                                        <button x-show="comment.user_id == <?= Auth::id() ?>" 
                                                @click="editComment(comment.id)" 
                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <i class="fas fa-edit mr-2"></i>
                                            Düzenle
                                        </button>
                                        <button x-show="comment.user_id == <?= Auth::id() ?>" 
                                                @click="deleteComment(comment.id)" 
                                                class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                                            <i class="fas fa-trash mr-2"></i>
                                            Sil
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comment Content -->
                    <div class="ml-11">
                        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap" x-text="comment.content"></p>
                        
                        <!-- Attachments -->
                        <div x-show="comment.attachments && comment.attachments.length > 0" class="mt-3">
                            <div class="flex flex-wrap gap-2">
                                <template x-for="attachment in comment.attachments" :key="attachment.id">
                                    <a :href="'/app/file-upload/view/' + attachment.file_id" 
                                       target="_blank"
                                       class="inline-flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                                        <i class="fas fa-paperclip mr-2"></i>
                                        <span x-text="attachment.original_name"></span>
                                    </a>
                                </template>
                            </div>
                        </div>

                        <!-- Reply Button -->
                        <button @click="replyToComment(comment.id)" 
                                class="mt-2 text-sm text-primary-600 hover:text-primary-700">
                            <i class="fas fa-reply mr-1"></i>
                            Yanıtla
                        </button>

                        <!-- Replies -->
                        <div x-show="comment.replies && comment.replies.length > 0" class="mt-3 ml-4 border-l-2 border-gray-200 dark:border-gray-700 pl-4">
                            <template x-for="reply in comment.replies" :key="reply.id">
                                <div class="py-2">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="reply.full_name || reply.username"></span>
                                        <span class="text-xs text-gray-500" x-text="formatDate(reply.created_at)"></span>
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap" x-text="reply.content"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Comment Form -->
    <?php if ($allowCreate): ?>
    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
        <form @submit.prevent="submitComment()" class="space-y-4">
            <div>
                <textarea x-model="newComment.content" 
                          placeholder="Yorumunuzu yazın..."
                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                          rows="3"
                          required></textarea>
            </div>
            
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="newComment.isInternal" 
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">İç yorum</span>
                    </label>
                </div>
                
                <div class="flex items-center space-x-2">
                    <button type="button" @click="cancelReply()" 
                            x-show="replyingTo"
                            class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                        İptal
                    </button>
                    <button type="submit" 
                            :disabled="!newComment.content.trim() || submitting"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-paper-plane mr-2"></i>
                        <span x-text="submitting ? 'Gönderiliyor...' : 'Gönder'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
function commentsSystem(entityId, entityType, showInternal) {
    return {
        entityId: entityId,
        entityType: entityType,
        showInternal: showInternal,
        comments: [],
        total: 0,
        loading: false,
        submitting: false,
        replyingTo: null,
        newComment: {
            content: '',
            isInternal: false
        },

        async loadComments() {
            this.loading = true;
            try {
                const response = await fetch(`/app/comments/get-by-entity?entity_type=${this.entityType}&entity_id=${this.entityId}&include_internal=${this.showInternal}`);
                const result = await response.json();
                
                if (result.success) {
                    this.comments = result.data.comments;
                    this.total = result.data.total;
                }
            } catch (error) {
                console.error('Yorumlar yüklenemedi:', error);
            } finally {
                this.loading = false;
            }
        },

        async submitComment() {
            if (!this.newComment.content.trim()) return;
            
            this.submitting = true;
            try {
                const formData = new FormData();
                formData.append('entity_type', this.entityType);
                formData.append('entity_id', this.entityId);
                formData.append('content', this.newComment.content);
                formData.append('is_internal', this.newComment.isInternal ? '1' : '0');
                if (this.replyingTo) {
                    formData.append('parent_id', this.replyingTo);
                }
                formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

                const response = await fetch('/app/comments/create', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    this.newComment.content = '';
                    this.newComment.isInternal = false;
                    this.replyingTo = null;
                    await this.loadComments();
                } else {
                    alert(result.message || 'Yorum eklenemedi');
                }
            } catch (error) {
                console.error('Yorum ekleme hatası:', error);
                alert('Yorum eklenirken hata oluştu');
            } finally {
                this.submitting = false;
            }
        },

        async toggleReaction(commentId, reactionType) {
            try {
                const formData = new FormData();
                formData.append('reaction_type', reactionType);
                formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

                const response = await fetch(`/app/comments/toggle-reaction/${commentId}`, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    await this.loadComments();
                }
            } catch (error) {
                console.error('Reaksiyon hatası:', error);
            }
        },

        async deleteComment(commentId) {
            if (!confirm('Bu yorumu silmek istediğinizden emin misiniz?')) return;

            try {
                const formData = new FormData();
                formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

                const response = await fetch(`/app/comments/delete/${commentId}`, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    await this.loadComments();
                } else {
                    alert(result.message || 'Yorum silinemedi');
                }
            } catch (error) {
                console.error('Yorum silme hatası:', error);
                alert('Yorum silinirken hata oluştu');
            }
        },

        replyToComment(commentId) {
            this.replyingTo = commentId;
            this.newComment.content = '';
        },

        cancelReply() {
            this.replyingTo = null;
        },

        async refreshComments() {
            await this.loadComments();
        },

        async toggleInternal() {
            this.showInternal = !this.showInternal;
            await this.loadComments();
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) { // 1 dakika
                return 'Az önce';
            } else if (diff < 3600000) { // 1 saat
                return Math.floor(diff / 60000) + ' dakika önce';
            } else if (diff < 86400000) { // 1 gün
                return Math.floor(diff / 3600000) + ' saat önce';
            } else {
                return date.toLocaleDateString('tr-TR');
            }
        }
    }
}
</script>
