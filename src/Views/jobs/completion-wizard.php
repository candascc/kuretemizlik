<?php
/**
 * Job Completion Enhanced - WORKFLOW-001
 * 
 * Checklist, photos, signature capture
 * Replaces simple completion with professional workflow
 */

$job = $job ?? null;
if (!$job) {
    header('Location: ' . base_url('/jobs'));
    exit;
}

$checklist_items = [
    'Temizlik tamamlandı',
    'Tüm alanlar kontrol edildi',
    'Malzemeler toplandı',
    'Müşteri ile görüşüldü',
    'Özel talepler yerine getirildi'
];
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8" x-data="jobCompletionWizard()">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-check-double mr-4 text-green-600"></i>
                        İşi Tamamla
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">
                        İş #<?= $job['id'] ?> - <?= htmlspecialchars($job['customer_name'] ?? '') ?>
                    </p>
                </div>
                <a href="<?= base_url('/jobs/show/' . $job['id']) ?>" 
                   class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </a>
            </div>
        </div>

        <!-- Completion Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden">
            
            <!-- Progress Bar -->
            <div class="bg-gray-200 dark:bg-gray-700 h-2">
                <div class="bg-gradient-to-r from-green-600 to-emerald-600 h-full transition-all duration-300"
                     :style="`width: ${progress}%`"></div>
            </div>

            <form @submit.prevent="submitCompletion()" class="p-8 space-y-8">
                
                <!-- 1. Checklist -->
                <section>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <span class="w-8 h-8 bg-primary-600 text-white rounded-full flex items-center justify-center mr-3 text-sm">1</span>
                        Kontrol Listesi
                    </h2>
                    
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6 space-y-3">
                        <?php foreach ($checklist_items as $index => $item): ?>
                        <label class="flex items-center space-x-3 cursor-pointer group">
                            <input type="checkbox" 
                                   x-model="checklist[<?= $index ?>]"
                                   @change="updateProgress()"
                                   class="w-6 h-6 text-green-600 border-gray-300 rounded focus:ring-green-500 focus:ring-2">
                            <span class="text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white font-medium">
                                <?= e($item) ?>
                            </span>
                            <i class="fas fa-check-circle text-green-600 text-xl opacity-0 transition-opacity"
                               :class="checklist[<?= $index ?>] ? 'opacity-100' : ''"></i>
                        </label>
                        <?php endforeach; ?>
                        
                        <!-- Custom Items -->
                        <template x-for="(item, index) in customChecklist" :key="'custom-' + index">
                            <div class="flex items-center space-x-3">
                                <input type="checkbox" 
                                       x-model="item.checked"
                                       @change="updateProgress()"
                                       class="w-6 h-6 text-green-600 border-gray-300 rounded">
                                <input type="text" 
                                       x-model="item.text"
                                       placeholder="Özel kontrol maddesi..."
                                       class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <button type="button" @click="customChecklist.splice(index, 1)"
                                        class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </template>
                        
                        <button type="button" 
                                @click="customChecklist.push({text: '', checked: false})"
                                class="mt-3 text-primary-600 hover:text-primary-700 font-medium text-sm">
                            <i class="fas fa-plus mr-2"></i>
                            Özel Madde Ekle
                        </button>
                    </div>
                </section>

                <!-- 2. Photos -->
                <section>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <span class="w-8 h-8 bg-primary-600 text-white rounded-full flex items-center justify-center mr-3 text-sm">2</span>
                        Fotoğraflar
                    </h2>
                    
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6">
                        <!-- Photo Upload -->
                        <div class="text-center mb-6">
                            <label class="cursor-pointer inline-flex flex-col items-center">
                                <i class="fas fa-cloud-upload-alt text-6xl text-gray-400 mb-4"></i>
                                <span class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Fotoğraf Yükle
                                </span>
                                <span class="text-sm text-gray-500">
                                    veya sürükle bırak (En fazla 10 fotoğraf)
                                </span>
                                <input type="file" 
                                       accept="image/*" 
                                       multiple 
                                       @change="handlePhotoUpload($event)"
                                       class="hidden">
                            </label>
                        </div>

                        <!-- Photo Preview Grid -->
                        <div x-show="photos.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 mt-6">
                            <template x-for="(photo, index) in photos" :key="index">
                                <div class="relative group">
                                    <img :src="photo.preview" 
                                         :alt="'Photo ' + (index + 1)"
                                         class="w-full h-32 object-cover rounded-lg shadow-md">
                                    <button type="button" 
                                            @click="removePhoto(index)"
                                            class="absolute top-2 right-2 w-8 h-8 bg-red-600 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-xs p-1 text-center rounded-b-lg">
                                        <span x-text="(photo.file.size / 1024).toFixed(0) + ' KB'"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </section>

                <!-- 3. Signature -->
                <section>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <span class="w-8 h-8 bg-primary-600 text-white rounded-full flex items-center justify-center mr-3 text-sm">3</span>
                        Müşteri İmzası
                    </h2>
                    
                    <div class="border-2 border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden">
                        <canvas id="signature-pad" 
                                width="800" 
                                height="200"
                                class="w-full bg-white cursor-crosshair"
                                @mousedown="startSignature"
                                @mousemove="drawSignature"
                                @mouseup="endSignature"
                                @touchstart="startSignature"
                                @touchmove="drawSignature"
                                @touchend="endSignature"></canvas>
                        
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                <i class="fas fa-info-circle mr-2"></i>
                                Yukarıda müşteri imzasını alın
                            </span>
                            <button type="button" 
                                    @click="clearSignature()"
                                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-all">
                                <i class="fas fa-eraser mr-2"></i>
                                Temizle
                            </button>
                        </div>
                    </div>
                </section>

                <!-- 4. Notes -->
                <section>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <span class="w-8 h-8 bg-primary-600 text-white rounded-full flex items-center justify-center mr-3 text-sm">4</span>
                        Notlar
                    </h2>
                    
                    <textarea x-model="notes"
                              rows="4"
                              placeholder="İş tamamlama notları, ek bilgiler..."
                              class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white resize-none"></textarea>
                </section>

                <!-- Submit -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="<?= base_url('/jobs/show/' . $job['id']) ?>" 
                       class="px-6 py-3 text-gray-700 dark:text-gray-300 font-semibold rounded-xl border-2 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
                        <i class="fas fa-arrow-left mr-2"></i>
                        İptal
                    </a>
                    <button type="submit"
                            :disabled="!isValid || isSubmitting"
                            :class="isValid && !isSubmitting ? 'bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700' : 'bg-gray-300 cursor-not-allowed'"
                            class="px-8 py-4 text-white font-bold text-lg rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:scale-105 disabled:transform-none">
                        <i class="fas" :class="isSubmitting ? 'fa-spinner fa-spin' : 'fa-check-double'"></i>
                        <span x-text="isSubmitting ? 'İşleniyor...' : 'İşi Tamamla'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function jobCompletionWizard() {
    return {
        checklist: <?= json_encode(array_fill(0, count($checklist_items), false)) ?>,
        customChecklist: [],
        photos: [],
        signature: null,
        notes: '',
        isSubmitting: false,
        canvas: null,
        ctx: null,
        isDrawing: false,
        lastX: 0,
        lastY: 0,
        
        init() {
            this.canvas = document.getElementById('signature-pad');
            if (this.canvas) {
                this.ctx = this.canvas.getContext('2d');
                this.ctx.strokeStyle = '#000';
                this.ctx.lineWidth = 2;
                this.ctx.lineCap = 'round';
            }
        },
        
        get progress() {
            const totalSteps = 4;
            let completed = 0;
            
            if (this.checklist.some(c => c)) completed++;
            if (this.photos.length > 0) completed++;
            if (this.signature) completed++;
            if (this.notes.trim()) completed++;
            
            return (completed / totalSteps) * 100;
        },
        
        get isValid() {
            // At least checklist should be complete
            return this.checklist.filter(c => c).length >= 3;
        },
        
        updateProgress() {
            // Force reactive update
            this.$nextTick();
        },
        
        handlePhotoUpload(event) {
            const files = Array.from(event.target.files);
            const maxPhotos = 10;
            
            if (this.photos.length + files.length > maxPhotos) {
                alert(`En fazla ${maxPhotos} fotoğraf yükleyebilirsiniz`);
                return;
            }
            
            files.forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.photos.push({
                            file: file,
                            preview: e.target.result
                        });
                    };
                    reader.readAsDataURL(file);
                }
            });
        },
        
        removePhoto(index) {
            this.photos.splice(index, 1);
        },
        
        startSignature(e) {
            e.preventDefault();
            this.isDrawing = true;
            const rect = this.canvas.getBoundingClientRect();
            const x = (e.clientX || e.touches[0].clientX) - rect.left;
            const y = (e.clientY || e.touches[0].clientY) - rect.top;
            this.lastX = x;
            this.lastY = y;
        },
        
        drawSignature(e) {
            if (!this.isDrawing) return;
            e.preventDefault();
            
            const rect = this.canvas.getBoundingClientRect();
            const x = (e.clientX || e.touches[0].clientX) - rect.left;
            const y = (e.clientY || e.touches[0].clientY) - rect.top;
            
            this.ctx.beginPath();
            this.ctx.moveTo(this.lastX, this.lastY);
            this.ctx.lineTo(x, y);
            this.ctx.stroke();
            
            this.lastX = x;
            this.lastY = y;
            this.signature = true;
        },
        
        endSignature() {
            this.isDrawing = false;
        },
        
        clearSignature() {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            this.signature = null;
        },
        
        async submitCompletion() {
            if (!this.isValid) return;
            
            this.isSubmitting = true;
            
            try {
                const formData = new FormData();
                formData.append('job_id', <?= $job['id'] ?>);
                formData.append('checklist', JSON.stringify(this.checklist));
                formData.append('custom_checklist', JSON.stringify(this.customChecklist));
                formData.append('notes', this.notes);
                
                // Photos
                this.photos.forEach((photo, index) => {
                    formData.append(`photos[${index}]`, photo.file);
                });
                
                // Signature
                if (this.signature) {
                    const signatureBlob = await new Promise(resolve => 
                        this.canvas.toBlob(resolve, 'image/png')
                    );
                    formData.append('signature', signatureBlob, 'signature.png');
                }
                
                const response = await fetch('<?= base_url('/jobs/complete/' . $job['id']) ?>', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    // Success
                    if (typeof confetti !== 'undefined') {
                        confetti({
                            particleCount: 200,
                            spread: 90,
                            origin: { y: 0.6 }
                        });
                    }
                    
                    setTimeout(() => {
                        window.location.href = '<?= base_url('/jobs/show/' . $job['id']) ?>';
                    }, 1500);
                } else {
                    alert('Hata: İş tamamlanamadı');
                }
                
            } catch (error) {
                console.error('Completion error:', error);
                alert('Bağlantı hatası: ' + error.message);
            } finally {
                this.isSubmitting = false;
            }
        }
    }
}
</script>

