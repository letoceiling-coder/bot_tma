<template>
    <div class="bg-light min-vh-100">
        <!-- Заголовок страницы -->
        <div class="bg-white border-bottom">
            <div class="container-fluid py-3">
                <div class="row">
                    <div class="col">
                        <h1 class="h3 mb-0 fw-bold">
                            <i class="fa-solid fa-images me-2 text-primary"></i>
                            Медиа менеджер
                        </h1>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Основной контент -->
        <div class="container-fluid py-4">
            <!-- Панель действий -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <!-- Показываем кнопки только если не в корзине -->
                                <template v-if="!isTrashFolder">
                                    <!-- Кнопка "Папки" -->
                                    <button 
                                        @click="currentComponent = 'folders'" 
                                        type="button" 
                                        class="btn btn-sm"
                                        :class="currentComponent === 'folders' ? 'btn-primary' : 'btn-outline-secondary'"
                                    >
                                        <i class="fa-solid fa-folder me-2"></i>
                                        Папки
                                    </button>

                                    <!-- Кнопка "Загрузка файлов" -->
                                    <button 
                                        @click="currentComponent = 'download'" 
                                        type="button" 
                                        class="btn btn-sm"
                                        :class="currentComponent === 'download' ? 'btn-primary' : 'btn-outline-secondary'"
                                    >
                                        <i class="fa-solid fa-upload me-2"></i>
                                        Загрузка файлов
                                    </button>

                                    <!-- Кнопка "Создать папку" -->
                                    <button 
                                        v-if="currentComponent === 'folders'" 
                                        @click="showModal('modal-new-folder')" 
                                        type="button" 
                                        class="btn btn-success btn-sm ms-auto"
                                    >
                                        <i class="fa-solid fa-plus me-2"></i>
                                        Создать папку
                                    </button>
                                </template>
                                
                                <!-- Кнопка "Очистить корзину" для корзины -->
                                <template v-else>
                                    <div class="alert alert-warning mb-0 me-3">
                                        <i class="fa-solid fa-trash me-2"></i>
                                        <strong>Корзина</strong> - удаленные файлы можно восстановить
                                    </div>
                                    <button 
                                        @click="emptyTrash" 
                                        type="button" 
                                        class="btn btn-danger btn-sm ms-auto"
                                    >
                                        <i class="fa-solid fa-trash-can me-2"></i>
                                        Очистить корзину
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Breadcrumbs (навигация) -->
            <div class="row mb-3" v-if="breadcrumbs.length > 0">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body py-2">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0 bg-transparent">
                                    <li class="breadcrumb-item">
                                        <a 
                                            href="#" 
                                            @click.prevent="navigateToRoot" 
                                            class="text-decoration-none"
                                        >
                                            <i class="fa-solid fa-home me-1"></i>
                                            Главная
                                        </a>
                                    </li>
                                    <li
                                        v-for="(crumb, index) in breadcrumbs"
                                        :key="crumb.id"
                                        class="breadcrumb-item"
                                        :class="{ active: index === breadcrumbs.length - 1 }"
                                    >
                                        <a
                                            v-if="index < breadcrumbs.length - 1"
                                            href="#"
                                            @click.prevent="navigateToFolder(crumb.id)"
                                            class="text-decoration-none"
                                        >
                                            {{ crumb.name }}
                                        </a>
                                        <span v-else class="text-muted">{{ crumb.name }}</span>
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Динамический компонент (Папки или Загрузка) -->
            <div class="row">
                <div class="col-12">
                    <component
                        :is="currentComponent"
                        :folders="currentFolders"
                        :current-folder-id="storage.media?.current || null"
                        :modal="modal"
                        :max-files="maxFiles"
                        :selected-files="selectedFiles"
                        @open-folder="openFolder"
                        @go-back="goBack"
                        @file-click="handleFileClick"
                        @file-uploaded="handleFileUploaded"
                        @folders-reordered="handleFoldersReordered"
                        @file-selected="handleFileSelected"
                        ref="currentComponentRef"
                    ></component>
                </div>
            </div>
        </div>
        
        <!-- Модальное окно "Новая папка" -->
        <div 
            class="modal fade" 
            id="modal-new-folder" 
            tabindex="-1" 
            aria-labelledby="modalNewFolderLabel" 
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalNewFolderLabel">
                            <i class="fa-solid fa-folder-plus me-2"></i>
                            Новая папка
                        </h5>
                        <button 
                            type="button" 
                            class="btn-close btn-close-white" 
                            data-bs-dismiss="modal" 
                            aria-label="Close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="folderName" class="form-label">
                                Название папки <span class="text-danger">*</span>
                            </label>
                            <mc-input 
                                v-model="form.name" 
                                class="form-control" 
                                keys="name"
                                placeholder="Введите название папки"
                                id="folderName"
                            />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button 
                            type="button" 
                            class="btn btn-secondary" 
                            data-bs-dismiss="modal"
                        >
                            <i class="fa-solid fa-times me-2"></i>
                            Отмена
                        </button>
                        <button 
                            @click="createFolder" 
                            type="button" 
                            class="btn btn-primary"
                        >
                            <i class="fa-solid fa-check me-2"></i>
                            Создать папку
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Folders from './components/folders.vue'
import Download from './components/download.vue'


export default {
    name: "index",
    components: {
        folders:Folders,
        download:Download,

    },
    props:{
        modal: {
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
    data() {
        return {
            form:{
                name:''
            },
            currentComponent:'folders',
            breadcrumbs: [], // Хлебные крошки для навигации
            navigationHistory: [], // История навигации для кнопки "Назад"
            localFolders: [] // Локальная копия папок для реактивности
        }
    },
    computed: {
        currentFolders() {
            // Используем локальную копию для лучшей реактивности
            return this.localFolders.length > 0 ? this.localFolders : (this.storage.media?.folders || [])
        },
        
        // Проверка, находимся ли мы в корзине (ID корзины = 4)
        isTrashFolder() {
            return this.storage.media?.current === 4
        }
    },
    mounted() {
        // Инициализируем storage.media если его нет
        if (!this.storage.media) {
            this.storage.media = {
                folders: [],
                current: null,
                filter: {}
            }
        }
        this.getFolders()
    },
    methods: {
        showModal(){
            // Bootstrap 5 нативный API (без jQuery)
            const modalEl = document.getElementById('modal-new-folder');
            if (modalEl) {
                const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.toggle();
            }
        },

        async createFolder(){
            let url = '/api/v1/folders'
            this.form.parent_id = this.storage.media?.current || null

            await axios.post(url, this.form).then(data => {
                if (this.storage.media) {
                    this.storage.media.folders = data.data.data
                }
                this.showModal()
                this.form.name = ''
            }).catch((error) => {
                console.log(error)
            })
        },

        async getFolders(parentId = null) {
            let url = '/api/v1/folders'

            console.log('Запрос папок с parent_id:', parentId)

            await axios.get(url, {
                params: {
                    ...(this.storage.media?.filter || {}),
                    parent_id: parentId
                }
            }).then(response => {
                console.log('Ответ API:', response.data)
                console.log('Полученные папки:', response.data.data)

                // Обновляем оба массива для гарантии реактивности
                let folders = response.data.data || []

                // Сортируем папки по position (по возрастанию), затем по id
                folders.sort((a, b) => {
                    // Сначала сравниваем по position
                    if (a.position !== b.position) {
                        return (a.position || 0) - (b.position || 0)
                    }
                    // Если position одинаковые, сравниваем по id
                    return a.id - b.id
                })

                if (this.storage.media) {
                    this.storage.media.folders = folders
                }
                this.localFolders = [...folders]

                console.log('Папки установлены и отсортированы:', this.localFolders.length, 'папок')
                console.table(folders.map(f => ({
                    id: f.id,
                    name: f.name,
                    position: f.position,
                    parent_id: f.parent_id
                })))
            }).catch((error) => {
                console.error('Ошибка загрузки папок:', error)
            })
        },

        // Открыть папку по клику
        async openFolder(folder) {
            console.log('=== Открываем папку ===')
            console.log('Папка:', folder)
            console.log('Текущий parent_id:', this.storage.media?.current)

            // Сохраняем текущее состояние в историю
            this.navigationHistory.push({
                parentId: this.storage.media?.current || null,
                breadcrumbs: [...this.breadcrumbs],
                folders: [...this.localFolders] // Сохраняем текущие папки
            })

            // Устанавливаем текущую папку
            if (this.storage.media) {
                this.storage.media.current = folder.id
            }
            console.log('Новый parent_id:', this.storage.media?.current)

            // Добавляем в breadcrumbs
            this.breadcrumbs.push({
                id: folder.id,
                name: folder.name
            })

            // Очищаем текущие папки перед загрузкой новых
            this.localFolders = []
            if (this.storage.media) {
                this.storage.media.folders = []
            }

            // Загружаем вложенные папки
            await this.getFolders(folder.id)

            console.log('=== Папки после загрузки ===')
            console.log('localFolders:', this.localFolders)
            console.log('Количество:', this.localFolders.length)
        },

        // Вернуться назад
        async goBack() {
            if (this.navigationHistory.length === 0) {
                // Если истории нет, возвращаемся в корень
                await this.navigateToRoot()
                return
            }

            // Восстанавливаем предыдущее состояние
            const previousState = this.navigationHistory.pop()
            if (this.storage.media) {
                this.storage.media.current = previousState.parentId
            }
            this.breadcrumbs = previousState.breadcrumbs

            // Восстанавливаем папки из истории или загружаем заново
            if (previousState.folders && previousState.folders.length > 0) {
                this.localFolders = [...previousState.folders]
                if (this.storage.media) {
                    this.storage.media.folders = previousState.folders
                }
            } else {
                await this.getFolders(previousState.parentId)
            }
        },

        // Перейти в корневую папку
        async navigateToRoot() {
            if (this.storage.media) {
                this.storage.media.current = null
            }
            this.breadcrumbs = []
            this.navigationHistory = []
            await this.getFolders(null)
        },

        // Перейти к конкретной папке из breadcrumbs
        async navigateToFolder(folderId) {
            // Находим индекс папки в breadcrumbs
            const folderIndex = this.breadcrumbs.findIndex(crumb => crumb.id === folderId)

            if (folderIndex !== -1) {
                // Обрезаем breadcrumbs до выбранной папки
                this.breadcrumbs = this.breadcrumbs.slice(0, folderIndex + 1)

                // Обрезаем историю навигации
                this.navigationHistory = this.navigationHistory.slice(0, folderIndex + 1)

                // Устанавливаем текущую папку
                if (this.storage.media) {
                    this.storage.media.current = folderId
                }

                // Загружаем папки
                await this.getFolders(folderId)
            }
        },

        // Обработка клика по файлу
        handleFileClick(file) {
            console.log('Клик по файлу в index.vue:', file)
            // Можно добавить открытие файла в модальном окне
        },

        // Обработка после загрузки файла
        handleFileUploaded() {
            console.log('Файл загружен, перезагружаем список')
            // Перезагружаем файлы в компоненте folders
            if (this.$refs.currentComponentRef && this.$refs.currentComponentRef.loadFiles) {
                this.$refs.currentComponentRef.loadFiles(this.storage.media?.current || null)
            }
        },

        // Обработка после изменения порядка папок
        async handleFoldersReordered() {
            console.log('Папки переупорядочены, перезагружаем список')
            // Перезагружаем папки с сервера, чтобы получить их в правильном порядке
            await this.getFolders(this.storage.media?.current || null)
        },

        // Обработка выбора файла в модальном режиме
        handleFileSelected(file) {
            console.log('Файл выбран в модальном режиме:', file)
            this.$emit('file-selected', file)
        },
        
        /**
         * Очистить корзину (удалить все файлы безвозвратно)
         */
        async emptyTrash() {
            if (!confirm('Вы уверены, что хотите безвозвратно удалить все файлы из корзины? Это действие нельзя отменить!')) {
                return
            }
            
            try {
                const response = await axios.delete('/api/v1/media/trash/empty')
                
                if (response.data.success) {
                    this.$notify({
                        type: 'success',
                        title: 'Успешно',
                        text: response.data.message
                    })
                    
                    // Перезагружаем список файлов в корзине
                    if (this.$refs.currentComponentRef && this.$refs.currentComponentRef.loadFiles) {
                        this.$refs.currentComponentRef.loadFiles(this.storage.media?.current || null)
                    }
                    
                    // Перезагружаем счётчик папок
                    await this.getFolders(this.storage.media?.current || null)
                } else {
                    this.$notify({
                        type: 'error',
                        title: 'Ошибка',
                        text: response.data.message || 'Не удалось очистить корзину'
                    })
                }
            } catch (error) {
                console.error('Ошибка очистки корзины:', error)
                this.$notify({
                    type: 'error',
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось очистить корзину'
                })
            }
        }
    }
}
</script>

<style scoped lang="scss">
/**
 * ============================================================
 * Стили для медиа-менеджера (Bootstrap 5)
 * ============================================================
 * 
 * Использует утилитарные классы Bootstrap 5
 * Минимум кастомных стилей для лучшей поддержки
 */

// Дополнительная тень для карточек (опционально)
.card {
    transition: box-shadow 0.2s ease-in-out;
    
    &:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
    }
}

// Плавные переходы для кнопок
.btn {
    transition: all 0.2s ease-in-out;
}

// Стили для хлебных крошек (breadcrumbs)
.breadcrumb {
    a {
        transition: color 0.2s ease-in-out;
        
        &:hover {
            color: var(--bs-primary) !important;
        }
    }
}

// Модальное окно - анимация появления
.modal {
    .modal-content {
        animation: modalSlideIn 0.3s ease-out;
    }
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>
