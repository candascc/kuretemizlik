<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-cloud-upload-alt mr-3 text-primary-600"></i>
                Dosya Yükle
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Güvenli ve hızlı dosya yükleme</p>
        </div>
    </div>

    <!-- Upload Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            <!-- Upload Area -->
            <div id="upload-area" 
                 class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:border-primary-500 transition-colors duration-200 cursor-pointer"
                 x-data="fileUpload()" 
                 x-init="init()"
                 @click="$refs.fileInput.click()"
                 @dragover.prevent="dragOver = true"
                 @dragleave.prevent="dragOver = false"
                 @drop.prevent="handleDrop($event)">
                
                <input type="file" 
                       x-ref="fileInput" 
                       @change="handleFiles($event.target.files)"
                       multiple
                       class="hidden">
                
                <div x-show="!dragOver && files.length === 0" class="space-y-4">
                    <i class="fas fa-cloud-upload-alt text-6xl text-gray-400"></i>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Dosyaları buraya sürükleyin</h3>
                        <p class="text-gray-500 dark:text-gray-400">veya tıklayarak seçin</p>
                    </div>
                    <div class="text-sm text-gray-400">
                        <p>Maksimum dosya boyutu: <span class="font-medium"><?= $maxFileSize ?? '50MB' ?></span></p>
                        <p>Desteklenen formatlar: <?= implode(', ', $allowedTypes ?? ['PDF', 'DOC', 'DOCX', 'JPG', 'PNG', 'GIF']) ?></p>
                    </div>
                </div>

                <div x-show="dragOver" class="space-y-4">
                    <i class="fas fa-cloud-upload-alt text-6xl text-primary-500"></i>
                    <h3 class="text-lg font-medium text-primary-600">Dosyaları bırakın</h3>
                </div>
            </div>

            <!-- File List -->
            <div x-show="files.length > 0" class="mt-6 space-y-4">
                <h4 class="text-lg font-medium text-gray-900 dark:text-white">Seçilen Dosyalar</h4>
                
                <template x-for="(file, index) in files" :key="index">
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-file text-gray-400"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="file.name"></p>
                                <p class="text-xs text-gray-500" x-text="formatFileSize(file.size)"></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div x-show="file.status === 'uploading'" class="flex items-center space-x-2">
                                <div class="w-4 h-4 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
                                <span class="text-sm text-gray-500" x-text="file.progress + '%'"></span>
                            </div>
                            <div x-show="file.status === 'completed'" class="text-green-500">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div x-show="file.status === 'error'" class="text-red-500">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <button @click="removeFile(index)" 
                                    class="text-red-500 hover:text-red-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Upload Progress -->
            <div x-show="uploading" class="mt-6">
                <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-primary-600 h-2 rounded-full transition-all duration-300" 
                         :style="'width: ' + totalProgress + '%'"></div>
                </div>
                <p class="text-sm text-gray-500 mt-2" x-text="totalProgress + '% tamamlandı'"></p>
            </div>

            <!-- Upload Options -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Kategori
                    </label>
                    <select x-model="options.category" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        <option value="documents">Dokümanlar</option>
                        <option value="images">Resimler</option>
                        <option value="contracts">Sözleşmeler</option>
                        <option value="jobs">İşler</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Maksimum Dosya Boyutu
                    </label>
                    <select x-model="options.maxSize" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        <option value="10485760">10 MB</option>
                        <option value="52428800">50 MB</option>
                        <option value="104857600">100 MB</option>
                    </select>
                </div>
            </div>

            <!-- Advanced Options -->
            <div class="mt-6 space-y-4">
                <h4 class="text-lg font-medium text-gray-900 dark:text-white">Gelişmiş Seçenekler</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex items-center">
                        <input type="checkbox" 
                               x-model="options.generateThumbnails" 
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Resimler için thumbnail oluştur</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" 
                               x-model="options.compressImages" 
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Resimleri sıkıştır</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" 
                               x-model="options.watermark" 
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Resimlere watermark ekle</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" 
                               x-model="options.scanAntivirus" 
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Antivirus taraması yap</span>
                    </label>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex justify-end space-x-4">
                <button @click="clearFiles()" 
                        class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Temizle
                </button>
                <button @click="uploadFiles()" 
                        :disabled="files.length === 0 || uploading"
                        class="px-6 py-2 bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                        style="color: white !important; background: #4f46e5 !important;">
                    <i class="fas fa-upload mr-2" style="color: white !important;"></i>
                    <span style="color: white !important;">Yükle</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Recent Uploads -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Son Yüklenen Dosyalar</h3>
            <div id="recent-uploads" class="space-y-2">
                <!-- Recent uploads will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function fileUpload() {
    return {
        files: [],
        uploading: false,
        dragOver: false,
        totalProgress: 0,
        options: {
            category: '<?= $category ?? 'documents' ?>',
            maxSize: 52428800, // 50MB
            generateThumbnails: true,
            compressImages: true,
            watermark: false,
            scanAntivirus: false
        },

        init() {
            this.loadRecentUploads();
        },

        handleFiles(fileList) {
            Array.from(fileList).forEach(file => {
                this.addFile(file);
            });
        },

        handleDrop(event) {
            this.dragOver = false;
            const files = event.dataTransfer.files;
            this.handleFiles(files);
        },

        addFile(file) {
            // Dosya doğrulama
            if (!this.validateFile(file)) {
                return;
            }

            this.files.push({
                name: file.name,
                size: file.size,
                file: file,
                status: 'pending',
                progress: 0
            });
        },

        validateFile(file) {
            const maxSize = this.options.maxSize;
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            
            if (file.size > maxSize) {
                this.showError(`Dosya boyutu çok büyük. Maksimum: ${this.formatFileSize(maxSize)}`);
                return false;
            }

            if (!allowedTypes.includes(file.type)) {
                this.showError('Geçersiz dosya türü');
                return false;
            }

            return true;
        },

        removeFile(index) {
            this.files.splice(index, 1);
        },

        clearFiles() {
            this.files = [];
        },

        async uploadFiles() {
            if (this.files.length === 0) return;

            this.uploading = true;
            this.totalProgress = 0;

            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                file.status = 'uploading';

                try {
                    const formData = new FormData();
                    formData.append('file', file.file);
                    formData.append('category', this.options.category);
                    formData.append('max_size', this.options.maxSize);
                    formData.append('generate_thumbnails', this.options.generateThumbnails);
                    formData.append('compress_images', this.options.compressImages);
                    formData.append('watermark', this.options.watermark);
                    formData.append('scan_antivirus', this.options.scanAntivirus);
                    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

                    const response = await fetch('/app/file-upload/upload', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        file.status = 'completed';
                        file.progress = 100;
                    } else {
                        file.status = 'error';
                        this.showError(result.message || 'Upload hatası');
                    }
                } catch (error) {
                    file.status = 'error';
                    this.showError('Upload hatası: ' + error.message);
                }

                this.totalProgress = Math.round(((i + 1) / this.files.length) * 100);
            }

            this.uploading = false;
            this.loadRecentUploads();
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        showError(message) {
            // Toast notification göster
            if (window.showToast) {
                window.showToast(message, 'error');
            } else {
                alert(message);
            }
        },

        async loadRecentUploads() {
            try {
                const response = await fetch('/app/file-upload/list?limit=5');
                const result = await response.json();
                
                if (result.success) {
                    this.displayRecentUploads(result.data.files);
                }
            } catch (error) {
                console.error('Recent uploads yüklenemedi:', error);
            }
        },

        displayRecentUploads(files) {
            const container = document.getElementById('recent-uploads');
            container.innerHTML = '';

            files.forEach(file => {
                const fileElement = document.createElement('div');
                fileElement.className = 'flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg';
                fileElement.innerHTML = `
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-file text-gray-400"></i>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${file.original_name}</p>
                            <p class="text-xs text-gray-500">${this.formatFileSize(file.file_size)}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <a href="/app/file-upload/view/${file.id}" class="text-primary-600 hover:text-primary-700">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="/app/file-upload/download/${file.id}" class="text-gray-600 hover:text-gray-700">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                `;
                container.appendChild(fileElement);
            });
        }
    }
}
</script>
