<template>
    <div id="component-files" class="mt-4">
        <!-- Lightbox для просмотра изображений и видео -->
        <FsLightbox
            v-if="lightboxSources.length > 0"
            :toggler="lightboxToggler"
            :sources="lightboxSources"
            :slide="lightboxSlide"
        />
        
        <!-- Панель управления файлами -->
        <div v-if="totalFiles > 0" class="files-toolbar mb-3 d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <h5 class="mb-0">
                    <i class="fa-solid fa-file mr-2"></i>
                    Файлы ({{ totalFiles }})
                </h5>
            </div>

            <div class="d-flex align-items-center gap-3 flex-wrap">
                <!-- Поиск -->
                <div class="search-box">
                    <i class="fa-solid fa-search"></i>
                    <input
                        v-model="localSearchQuery"
                        type="text"
                        placeholder="Поиск файла..."
                        class="form-control form-control-sm"
                        @input="handleSearch"
                    >
                </div>

                <!-- Сортировка -->
                <div class="sort-box">
                    <label class="mb-0 mr-2"><small>Сортировка:</small></label>
                    <select v-model="localSortBy" @change="handleSort" class="form-control form-control-sm">
                        <option value="newest">Новые</option>
                        <option value="oldest">Старые</option>
                        <option value="date_asc">По дате (возр.)</option>
                        <option value="date_desc">По дате (убыв.)</option>
                        <option value="name_asc">По имени (А-Я)</option>
                        <option value="name_desc">По имени (Я-А)</option>
                        <option value="size_asc">По размеру (возр.)</option>
                        <option value="size_desc">По размеру (убыв.)</option>
                    </select>
                </div>

                <!-- Количество на странице -->
                <div class="perpage-box">
                    <label class="mb-0 mr-2"><small>Показывать:</small></label>
                    <select v-model="localPerPage" @change="handlePerPageChange" class="form-control form-control-sm">
                        <option :value="10">10</option>
                        <option :value="20">20</option>
                        <option :value="30">30</option>
                        <option :value="40">40</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        <div v-if="loading" class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Загрузка...</span>
            </div>
        </div>

        <div v-else-if="files.length === 0 && !hasFilters" class="text-center py-4 text-muted">
            <i class="fa-solid fa-folder-open fa-3x mb-3"></i>
            <p>Нет файлов в этой папке</p>
        </div>

        <div v-else-if="files.length === 0 && hasFilters" class="text-center py-4 text-muted">
            <i class="fa-solid fa-search fa-3x mb-3"></i>
            <p>Файлы не найдены</p>
            <button @click="clearFilters" class="btn btn-sm btn-outline-secondary mt-2">
                <i class="fa-solid fa-times mr-1"></i> Очистить фильтры
            </button>
        </div>

        <div v-else class="files-grid">
            <transition-group name="file-list" tag="div" class="files-grid-wrapper">
                <div
                    v-for="file in files"
                    :key="file.id"
                    class="file-card m-2"
                    @click="handleFileClick(file)"
                >
                <!-- Превью -->
                <div class="file-preview">
                    <img
                        v-if="file.type === 'photo' && file.thumbnail"
                        :src="file.url"
                        :alt="file.original_name"
                        class="file-image"
                    >
                    <div v-else class="file-icon-wrapper">
                        <i
                            class="file-icon"
                            :class="{
                                'fa-solid fa-file-image text-primary': file.type === 'photo',
                                'fa-solid fa-file-video text-danger': file.type === 'video',
                                'fa-solid fa-file-pdf text-danger': file.extension === 'pdf',
                                'fa-solid fa-file-word text-info': ['doc', 'docx'].includes(file.extension),
                                'fa-solid fa-file-excel text-success': ['xls', 'xlsx'].includes(file.extension),
                                'fa-solid fa-file text-secondary': !['photo', 'video'].includes(file.type)
                            }"
                        ></i>
                    </div>
                    <!-- Иконка просмотра или кнопка выбора для файлов -->
                    <div v-if="modal || file.type === 'photo' || file.type === 'video' || isPreviewable(file)" class="preview-overlay">
                        <button 
                            v-if="modal"
                            @click.stop="$emit('file-selected', file)"
                            class="btn btn-select"
                            :class="isFileSelected(file) ? 'btn-warning' : 'btn-success'"
                        >
                            <i class="fa-solid mr-1" :class="isFileSelected(file) ? 'fa-check-double' : 'fa-check'"></i>
                            {{ isFileSelected(file) ? 'Выбрано' : 'Выбрать' }}
                        </button>
                        <i v-else class="fa-solid fa-eye"></i>
                    </div>
                </div>

                <!-- Информация о файле -->
                <div class="file-info">
                    <div class="file-name" :title="file.original_name">
                        {{ truncateFileName(file.original_name, 15) }}
                    </div>
                    <div class="file-details">
                        <span class="file-size">{{ formatFileSize(file.size) }}</span>
                        <span class="file-ext">.{{ file.extension.toUpperCase() }}</span>
                    </div>
                    <div class="file-dimensions" v-if="file.width && file.height">
                        <small class="text-muted">{{ file.width }} × {{ file.height }}</small>
                    </div>
                </div>

                <!-- Действия -->
                <div v-if="!isTrashFolder" class="file-actions" :class="{'actions-4': file.type === 'photo'}">
                    <!-- Обычные действия для файлов НЕ в корзине -->
                    <button
                        @click.stop="$emit('download', file)"
                        class="btn btn-sm btn-outline-primary"
                        title="Скачать"
                    >
                        <i class="fa-solid fa-download"></i>
                    </button>
                    <button
                        v-if="file.type === 'photo'"
                        @click.stop="$emit('edit', file)"
                        class="btn btn-sm btn-outline-success"
                        title="Редактировать"
                    >
                        <i class="fa-solid fa-crop"></i>
                    </button>
                    <button
                        @click.stop="$emit('move', file)"
                        class="btn btn-sm btn-outline-warning"
                        title="Переместить"
                    >
                        <i class="fa-solid fa-folder-open"></i>
                    </button>
                    <button
                        @click.stop="$emit('delete', file, $event)"
                        class="btn btn-sm btn-outline-danger"
                        title="Удалить"
                    >
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
                
                <!-- Действия для файлов В КОРЗИНЕ -->
                <div v-else class="file-actions actions-2">
                    <button
                        @click.stop="$emit('restore', file)"
                        class="btn btn-sm btn-success w-100"
                        title="Восстановить файл"
                    >
                        <i class="fa-solid fa-undo me-1"></i>
                        Восстановить
                    </button>
                    <button
                        @click.stop="$emit('delete', file)"
                        class="btn btn-sm btn-danger w-100"
                        title="Удалить безвозвратно"
                    >
                        <i class="fa-solid fa-trash-can me-1"></i>
                        Удалить
                    </button>
                </div>
            </div>
            </transition-group>
        </div>
    </div>
</template>

<script>
import FsLightbox from 'fslightbox-vue'

export default {
    name: "FilesList",
    components: {
        FsLightbox
    },
    props: {
        files: {
            type: Array,
            default: () => []
        },
        loading: {
            type: Boolean,
            default: false
        },
        totalFiles: {
            type: Number,
            default: 0
        },
        searchQuery: {
            type: String,
            default: ''
        },
        sortBy: {
            type: String,
            default: 'newest'
        },
        perPage: {
            type: Number,
            default: 20
        },
        modal: {
            type: Boolean,
            default: false
        },
        isTrashFolder: {
            type: Boolean,
            default: false
        },
        maxFiles: {
            type: Number,
            default: 1
        },
        selectedFiles: {
            type: Array,
            default: () => []
        }
    },
    emits: ['file-click', 'download', 'edit', 'move', 'delete', 'search', 'sort', 'per-page-change', 'file-selected'],
    data() {
        return {
            localSearchQuery: this.searchQuery,
            localSortBy: this.sortBy,
            localPerPage: this.perPage,
            searchTimeout: null,
            lightboxToggler: false,
            lightboxSources: [],
            lightboxSlide: 1
        }
    },
    computed: {
        hasFilters() {
            return this.searchQuery.trim() !== ''
        },
        
        // Медиа файлы (изображения и видео) для lightbox
        mediaFiles() {
            return this.files.filter(file => file.type === 'photo' || file.type === 'video')
        }
    },
    watch: {
        searchQuery(newVal) {
            this.localSearchQuery = newVal
        },
        sortBy(newVal) {
            this.localSortBy = newVal
        },
        perPage(newVal) {
            this.localPerPage = newVal
        },
        files: {
            handler() {
                // Очищаем lightbox при изменении списка файлов
                this.lightboxSources = []
                this.lightboxSlide = 1
            },
            deep: true
        }
    },
    methods: {
        // Проверка, выбран ли файл
        isFileSelected(file) {
            return this.selectedFiles.some(f => f.id === file.id)
        },
        
        handleFileClick(file) {
            // В модальном режиме - emit file-selected
            if (this.modal) {
                this.$emit('file-selected', file)
                return
            }
            
            // Если это изображение или видео - открываем в lightbox
            if (file.type === 'photo' || file.type === 'video') {
                this.openLightbox(file)
            } 
            // Если это PDF, Word, Excel - открываем предпросмотр
            else if (this.isPreviewable(file)) {
                this.openDocumentPreview(file)
            } 
            else {
                // Для остальных файлов - emit события file-click
                this.$emit('file-click', file)
            }
        },
        
        /**
         * Проверяет, можно ли предпросмотреть документ
         */
        isPreviewable(file) {
            const previewableExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']
            return previewableExtensions.includes(file.extension?.toLowerCase())
        },
        
        /**
         * Открывает предпросмотр документа (PDF, Word, Excel) в модальном окне
         */
        openDocumentPreview(file) {
            const fullUrl = window.location.origin + file.url
            let viewerUrl = ''
            
            // Для PDF используем встроенный просмотр браузера
            if (file.extension === 'pdf') {
                viewerUrl = fullUrl
            } 
            // Для Word, Excel, PowerPoint используем Google Docs Viewer
            else if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(file.extension?.toLowerCase())) {
                viewerUrl = `https://docs.google.com/viewer?url=${encodeURIComponent(fullUrl)}&embedded=true`
            }
            
            if (viewerUrl) {
                // Создаём модальное окно для предпросмотра
                this.showDocumentModal(file, viewerUrl)
            }
        },
        
        /**
         * Показывает модальное окно с iframe для документа
         */
        showDocumentModal(file, viewerUrl) {
            // Создаём backdrop
            const backdrop = document.createElement('div')
            backdrop.className = 'document-preview-backdrop'
            backdrop.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            `
            
            // Создаём контейнер модального окна
            const modal = document.createElement('div')
            modal.className = 'document-preview-modal'
            modal.style.cssText = `
                position: relative;
                background: white;
                border-radius: 8px;
                width: 100%;
                max-width: 1200px;
                height: 90vh;
                display: flex;
                flex-direction: column;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            `
            
            // Заголовок
            const header = document.createElement('div')
            header.style.cssText = `
                padding: 15px 20px;
                border-bottom: 1px solid #dee2e6;
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #f8f9fa;
                border-radius: 8px 8px 0 0;
            `
            
            const title = document.createElement('h5')
            title.textContent = file.original_name
            title.style.cssText = `
                margin: 0;
                font-size: 1.1rem;
                color: #212529;
                flex: 1;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                padding-right: 20px;
            `
            
            const closeBtn = document.createElement('button')
            closeBtn.innerHTML = '×'
            closeBtn.className = 'btn-close-preview'
            closeBtn.style.cssText = `
                background: none;
                border: none;
                font-size: 2rem;
                line-height: 1;
                cursor: pointer;
                color: #6c757d;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                transition: all 0.2s;
            `
            closeBtn.onmouseover = () => {
                closeBtn.style.background = '#dee2e6'
                closeBtn.style.color = '#212529'
            }
            closeBtn.onmouseout = () => {
                closeBtn.style.background = 'none'
                closeBtn.style.color = '#6c757d'
            }
            
            header.appendChild(title)
            header.appendChild(closeBtn)
            
            // Контейнер для iframe с загрузкой
            const iframeContainer = document.createElement('div')
            iframeContainer.style.cssText = `
                flex: 1;
                position: relative;
                background: #f8f9fa;
            `
            
            // Спиннер загрузки
            const spinner = document.createElement('div')
            spinner.innerHTML = `
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="sr-only">Загрузка...</span>
                    </div>
                    <p style="margin-top: 15px; color: #6c757d;">Загрузка предпросмотра...</p>
                </div>
            `
            iframeContainer.appendChild(spinner)
            
            // iframe для документа
            const iframe = document.createElement('iframe')
            iframe.src = viewerUrl
            iframe.style.cssText = `
                width: 100%;
                height: 100%;
                border: none;
                display: none;
            `
            
            iframe.onload = () => {
                spinner.remove()
                iframe.style.display = 'block'
            }
            
            iframe.onerror = () => {
                spinner.innerHTML = `
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                        <i class="fa-solid fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <p style="color: #6c757d;">Не удалось загрузить предпросмотр</p>
                        <button class="btn btn-primary mt-2" onclick="window.open('${file.url}', '_blank')">
                            <i class="fa-solid fa-download me-2"></i>Скачать файл
                        </button>
                    </div>
                `
            }
            
            iframeContainer.appendChild(iframe)
            
            // Собираем модальное окно
            modal.appendChild(header)
            modal.appendChild(iframeContainer)
            backdrop.appendChild(modal)
            
            // Закрытие модального окна
            const close = () => {
                backdrop.remove()
            }
            
            closeBtn.onclick = close
            backdrop.onclick = (e) => {
                if (e.target === backdrop) close()
            }
            
            // ESC для закрытия
            const handleEsc = (e) => {
                if (e.key === 'Escape') {
                    close()
                    document.removeEventListener('keydown', handleEsc)
                }
            }
            document.addEventListener('keydown', handleEsc)
            
            // Добавляем в DOM
            document.body.appendChild(backdrop)
            
            // Анимация появления
            requestAnimationFrame(() => {
                backdrop.style.opacity = '0'
                backdrop.style.transition = 'opacity 0.3s ease'
                requestAnimationFrame(() => {
                    backdrop.style.opacity = '1'
                })
            })
        },
        
        openLightbox(file) {
            // Находим индекс текущего файла
            const currentIndex = this.mediaFiles.findIndex(f => f.id === file.id)
            
            if (currentIndex === -1) {
                console.error('Файл не найден в mediaFiles:', file)
                return
            }
            
            // Подготавливаем источники для lightbox
            const sources = this.mediaFiles.map(f => {
                // Для всех файлов просто используем URL
                // fslightbox автоматически определит тип
                return f.url
            })
            
            console.log('Opening lightbox:', {
                currentIndex,
                slide: currentIndex + 1,
                totalSources: sources.length,
                sources: sources
            })
            
            // Устанавливаем параметры lightbox
            this.lightboxSources = sources
            this.lightboxSlide = currentIndex + 1 // fslightbox использует 1-based индексацию
            
            // Переключаем lightbox с небольшой задержкой
            this.$nextTick(() => {
                this.lightboxToggler = !this.lightboxToggler
            })
        },
        
        handleSearch() {
            // Debounce для поиска
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout)
            }

            this.searchTimeout = setTimeout(() => {
                this.$emit('search', this.localSearchQuery)
            }, 500)
        },

        handleSort() {
            this.$emit('sort', this.localSortBy)
        },

        handlePerPageChange() {
            this.$emit('per-page-change', this.localPerPage)
        },

        clearFilters() {
            this.localSearchQuery = ''
            this.$emit('search', '')
        },

        truncateFileName(fileName, maxLength) {
            if (fileName.length <= maxLength) return fileName

            const ext = fileName.split('.').pop()
            const nameWithoutExt = fileName.substring(0, fileName.lastIndexOf('.'))
            const truncated = nameWithoutExt.substring(0, maxLength - ext.length - 4)

            return `${truncated}...${ext}`
        },

        formatFileSize(bytes) {
            if (!bytes || bytes === 0) return '0 Bytes'

            const k = 1024
            const sizes = ['Bytes', 'KB', 'MB', 'GB']
            const i = Math.floor(Math.log(bytes) / Math.log(k))
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
        }
    }
}
</script>

<style scoped lang="scss">
#component-files {
    width: 100%;
    border-top: 1px solid #e0e0e0;
    padding-top: 20px;
    margin-top: 20px;
}

.files-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.file-card {
    width: 160px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 12px;
    background: #fff;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;

    &:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transform: translateY(-3px);
    }
}

.file-preview {
    width: 100%;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    overflow: hidden;
    background: #f8f9fa;
    margin-bottom: 10px;
    position: relative;
}

.file-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.file-icon-wrapper {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.file-icon {
    font-size: 3rem;
}

.preview-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    cursor: pointer;
    
    i {
        color: white;
        font-size: 2rem;
    }
    
    .btn-select {
        font-size: 13px;
        font-weight: 600;
        padding: 8px 16px;
        border: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        transition: all 0.2s ease;
        
        &:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }
        
        &:active {
            transform: scale(0.95);
        }
    }
}

.file-card:hover .preview-overlay {
    opacity: 1;
}

.file-info {
    width: 100%;
    text-align: center;
    margin-bottom: 10px;
}

.file-name {
    font-size: 13px;
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
    word-break: break-word;
}

.file-details {
    display: flex;
    justify-content: center;
    gap: 8px;
    font-size: 11px;
    color: #666;
}

.file-size {
    font-weight: 500;
}

.file-ext {
    color: #007bff;
    font-weight: 600;
}

.file-dimensions {
    margin-top: 2px;
}

.file-actions {
    display: flex;
    gap: 5px;
    width: 100%;

    .btn {
        flex: 1;
        padding: 4px 8px;
        font-size: 12px;
    }
    
    &.actions-4 {
        // Для файлов с 4 кнопками делаем их меньше
        .btn {
            padding: 4px 6px;
            font-size: 11px;
        }
    }
    
    &.actions-2 {
        // Для корзины - 2 большие кнопки
        flex-direction: column;
        gap: 8px;
        
        .btn {
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 600;
            
            i {
                margin-right: 4px;
            }
        }
    }
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}

// Панель управления файлами
.files-toolbar {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;

    .gap-2 {
        gap: 0.5rem;
    }

    .gap-3 {
        gap: 1rem;
    }
}

.search-box {
    position: relative;

    i {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        z-index: 1;
    }

    input {
        padding-left: 35px;
        min-width: 200px;
    }
}

.sort-box,
.perpage-box {
    display: flex;
    align-items: center;

    label {
        white-space: nowrap;
    }

    select {
        min-width: 150px;
    }
}

// Адаптивность
@media (max-width: 768px) {
    .files-toolbar {
        flex-direction: column;
        align-items: flex-start !important;

        > div {
            width: 100%;
            margin-bottom: 10px;

            &:last-child {
                margin-bottom: 0;
            }
        }
    }

    .search-box input {
        min-width: 100%;
    }

    .sort-box,
    .perpage-box {
        width: 100%;

        select {
            flex: 1;
        }
    }
}

// ============================================================
// Vue Transition Анимации для списка файлов
// ============================================================

// Wrapper для transition-group
.files-grid-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    width: 100%;
}

// Анимация входа элемента (появление)
.file-list-enter-active {
    transition: all 0.4s ease-out;
}

.file-list-enter-from {
    opacity: 0;
    transform: translateY(20px) scale(0.9);
}

.file-list-enter-to {
    opacity: 1;
    transform: translateY(0) scale(1);
}

// Анимация выхода элемента (исчезновение)
.file-list-leave-active {
    transition: all 0.3s ease-in;
    position: absolute;
}

.file-list-leave-from {
    opacity: 1;
    transform: scale(1);
}

.file-list-leave-to {
    opacity: 0;
    transform: scale(0.8);
}

// Анимация перемещения элементов (при изменении порядка)
.file-list-move {
    transition: transform 0.4s ease;
}
</style>

