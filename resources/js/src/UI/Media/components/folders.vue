<template>
    <div class="card w-100">
        <div class="card-body" id="component-folders">
            <!-- Редактор изображений (полноэкранный) -->
            <template v-if="showCropEditor">
                <CropImage
                    :image="selectedImageForEdit"
                    @saved="handleImageSaved"
                    @close="closeCropEditor"
                />
            </template>
            
            <!-- Список папок и файлов -->
            <template v-else>
                <!-- Блок с папками (показывается только если есть папки) -->
                <div v-if="folders.length > 0" class="folders-section mb-4">
                    <Sortable
                        v-model:list="localFolders"
                        item-key="id"
                        tag="div"
                        class="row g-3"
                        :options="sortableOptions"
                        @end="onFolderSortEnd"
                    >
                        <template #item="{ element: folder, index }">
                            <div 
                                class="col-6 col-sm-4 col-md-3 col-lg-2"
                                :key="folder.id"
                            >
                                <div
                                    :title="`${folder.countFolder ? pluralizeRu(folder.countFolder,'папка','папки','папок') : ''} ${folder.count} файлов`"
                                    @click="handleFolderClick(folder)"
                                    class="folder-card card h-100 text-center position-relative sortable-item"
                                    :data-id="folder.id"
                                >
                                    <!-- Ручка для перетаскивания -->
                                    <div class="drag-handle position-absolute top-0 end-0 m-2" title="Перетащить для сортировки">
                                        <i class="fa-solid fa-arrows-up-down-left-right"></i>
                                    </div>
                                    
                                    <!-- Иконка папки -->
                                    <div class="card-body d-flex flex-column align-items-center justify-content-center p-3">
                                        <img 
                                            :src="`/img/system/${folder.src}.png`" 
                                            :alt="folder.name"
                                            class="folder-icon mb-2"
                                        >
                                        
                                        <!-- Название папки -->
                                        <h6 class="card-title mb-1 fw-bold text-truncate w-100">
                                            {{ folder.name }}
                                        </h6>
                                        
                                        <!-- Информация о содержимом -->
                                        <small class="text-muted">
                                            <div class="d-flex flex-column gap-1">
                                                <span v-if="folder.countFolder">
                                                    {{ pluralizeRu(folder.countFolder,'папка','папки','папок') }}
                                                </span>
                                                <span v-if="folder.count">
                                                    {{ pluralizeRu(folder.count,'файл','файла','файлов') }}
                                                </span>
                                                <span v-if="!folder.countFolder && !folder.count" class="fst-italic">
                                                    нет файлов
                                                </span>
                                            </div>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Sortable>
                </div>

                <!-- Компонент списка файлов -->
                <FilesList
                    :files="files"
                    :loading="loadingFiles"
                    :total-files="totalFiles"
                    :search-query="searchQuery"
                    :sort-by="sortBy"
                    :per-page="perPage"
                    :modal="modal"
                    :max-files="maxFiles"
                    :selected-files="selectedFiles"
                    :is-trash-folder="currentFolderId === 4"
                    @file-click="handleFileClick"
                    @download="downloadFile"
                    @edit="openCropEditor"
                    @move="openMoveModal"
                    @delete="deleteFile"
                    @restore="restoreFile"
                    @search="handleSearch"
                    @sort="handleSort"
                    @per-page-change="handlePerPageChange"
                    @file-selected="handleFileSelected"
                />
            </template>
        </div>

        <div v-if="!showCropEditor" class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <button
                    v-if="currentFolderId"
                    @click="$emit('go-back')"
                    type="button"
                    class="btn btn-secondary btn-sm"
                >
                    <i class="fa-solid fa-arrow-left me-2"></i> Назад
                </button>
                <span v-else class="text-muted">
                    <i class="fa-solid fa-folder"></i> Корневая папка
                </span>

                <!-- Пагинация -->
                <nav v-if="totalPages > 1" aria-label="Pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ disabled: currentPage === 1 }">
                            <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        </li>

                        <li
                            v-for="page in visiblePages"
                            :key="page"
                            class="page-item"
                            :class="{ active: page === currentPage }"
                        >
                            <a class="page-link" href="#" @click.prevent="changePage(page)">
                                {{ page }}
                            </a>
                        </li>

                        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                            <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div v-if="filteredFiles.length > 0" class="text-muted">
                    <small>
                        Показано {{ ((currentPage - 1) * perPage) + 1 }}-{{ Math.min(currentPage * perPage, filteredFiles.length) }}
                        из {{ filteredFiles.length }}
                    </small>
                </div>
            </div>
        </div>

        <!-- Компонент модального окна для перемещения -->
        <MoveFileModal
            :file="selectedFile"
            :folders="allFolders"
            :current-folder-id="currentFolderId"
            @move="handleFileMoved"
            @close="handleModalClose"
        />
    </div>
</template>

<script>
import FilesList from './FilesList.vue'
import MoveFileModal from './MoveFileModal.vue'
import CropImage from './crop.vue'
import { Sortable } from 'sortablejs-vue3'

export default {
    name: "Folders",
    components: {
        FilesList,
        MoveFileModal,
        CropImage,
        Sortable
    },
    props: {
        folders: {
            type: Array,
            default: () => []
        },
        currentFolderId: {
            type: [Number, String, null],
            default: null
        },
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
    emits: ['open-folder', 'go-back', 'file-click', 'folders-reordered', 'file-selected'],
    data() {
        return {
            files: [],
            loadingFiles: false,
            searchQuery: '',
            sortBy: 'newest',
            perPage: 20,
            currentPage: 1,
            totalFilesCount: 0,  // Общее количество с сервера
            selectedFile: null,  // Выбранный файл для перемещения
            selectedImageForEdit: null, // Выбранное изображение для редактирования
            allFolders: [],      // Список всех папок для выбора
            showCropEditor: false, // Флаг для отображения редактора
            localFolders: [],    // Локальная копия папок для сортировки
            isSorting: false,    // Флаг процесса сортировки
            sortableOptions: {
                animation: 200,
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                chosenClass: 'sortable-chosen',
                forceFallback: true,
                fallbackTolerance: 3,
                disabled: false,
                preventOnFilter: false
            }
        }
    },
    computed: {
        // Файлы для отображения (приходят уже отфильтрованные и отсортированные с сервера)
        paginatedFiles() {
            return this.files
        },

        // Для обратной совместимости
        filteredFiles() {
            return this.files
        },

        // Общее количество файлов
        totalFiles() {
            return this.totalFilesCount || this.files.length
        },

        // Общее количество страниц
        totalPages() {
            return Math.ceil(this.totalFiles / this.perPage)
        },

        // Видимые страницы для пагинации
        visiblePages() {
            const pages = []
            const maxVisible = 5

            let startPage = Math.max(1, this.currentPage - Math.floor(maxVisible / 2))
            let endPage = Math.min(this.totalPages, startPage + maxVisible - 1)

            if (endPage - startPage < maxVisible - 1) {
                startPage = Math.max(1, endPage - maxVisible + 1)
            }

            for (let i = startPage; i <= endPage; i++) {
                pages.push(i)
            }

            return pages
        }
    },
    watch: {
        currentFolderId: {
            immediate: true,
            handler(newValue) {
                this.loadFiles(newValue)
            }
        },
        folders: {
            immediate: true,
            handler(newFolders) {
                // Не обновляем localFolders во время сортировки
                if (this.isSorting) {
                    console.log('Пропускаем обновление folders - идет сортировка')
                    return
                }
                
                // Синхронизируем localFolders с пропсами и сортируем
                let sortedFolders = [...newFolders]
                
                // Сортируем папки по position (по возрастанию), затем по id
                sortedFolders.sort((a, b) => {
                    // Сначала сравниваем по position
                    if (a.position !== b.position) {
                        return (a.position || 0) - (b.position || 0)
                    }
                    // Если position одинаковые, сравниваем по id
                    return a.id - b.id
                })
                
                this.localFolders = sortedFolders
                
                console.log('Folders synced and sorted:', this.localFolders.map(f => ({
                    id: f.id,
                    name: f.name,
                    position: f.position
                })))
            },
            deep: true
        }
    },
    methods: {
        // Обработчик клика на папку (предотвращает открытие при перетаскивании)
        handleFolderClick(folder) {
            this.$emit('open-folder', folder)
        },
        
        // Обработчик окончания сортировки папок
        async onFolderSortEnd(event) {
            const { oldIndex, newIndex, item, to } = event
            
            if (oldIndex === newIndex) return
            
            this.isSorting = true
            
            console.log('=== СОРТИРОВКА ПАПОК ===')
            console.log('Событие от Sortable:', { oldIndex, newIndex })
            console.log('Перемещенный элемент data-id:', item.dataset.id)
            
            // Получаем порядок напрямую из DOM
            const folderElements = to.querySelectorAll('.sortable-item')
            const updatedFolders = []
            
            console.log('Найдено элементов в DOM:', folderElements.length)
            
            folderElements.forEach((element, index) => {
                const folderId = parseInt(element.dataset.id)
                const folderData = this.localFolders.find(f => f.id === folderId)
                
                if (folderData) {
                    updatedFolders.push({
                        id: folderId,
                        position: index + 1
                    })
                    
                    console.log(`Позиция ${index + 1}: ${folderData.name} (id: ${folderId})`)
                }
            })
            
            // Обновляем localFolders в соответствии с порядком из DOM
            this.localFolders = updatedFolders.map(item => {
                return this.localFolders.find(f => f.id === item.id)
            }).filter(Boolean)
            
            console.log('Новые позиции для отправки на сервер:')
            console.table(updatedFolders)
            
            try {
                // Отправляем новые позиции на сервер
                const response = await axios.post('/api/v1/folders/update-positions', {
                    folders: updatedFolders
                })
                
                console.log('Ответ сервера:', response.data)
                
                this.$notify({
                    type: 'success',
                    title: 'Успешно',
                    text: 'Порядок папок обновлен'
                })
                
                // Ждем немного перед перезагрузкой
                await new Promise(resolve => setTimeout(resolve, 100))
                
                // Эмитим событие для перезагрузки папок в родительском компоненте
                console.log('Эмит события folders-reordered')
                this.$emit('folders-reordered')
                
                // Снимаем флаг после небольшой задержки
                await new Promise(resolve => setTimeout(resolve, 300))
                this.isSorting = false
            } catch (error) {
                console.error('=== ОШИБКА ОБНОВЛЕНИЯ ПОЗИЦИЙ ===')
                console.error('Детали ошибки:', error.response || error)
                
                this.$notify({
                    type: 'error',
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось обновить порядок папок'
                })
                
                // Возвращаем предыдущий порядок при ошибке
                this.localFolders = [...this.folders]
                this.isSorting = false
            }
        },
        
        pluralizeRu(number, one, few, many) {
            number = Math.abs(number) % 100;
            const n1 = number % 10;
            if (number > 10 && number < 20) return number + " " + many;
            if (n1 > 1 && n1 < 5) return number + " " + few;
            if (n1 === 1) return number + " " + one;
            return number + " " + many;
        },

        async loadFiles(folderId, resetPage = true) {
            this.loadingFiles = true
            if (resetPage) {
                this.currentPage = 1 // Сбрасываем на первую страницу
            }

            try {
                const params = {
                    // Сортировка
                    sort_by: this.sortBy,
                    
                    // Пагинация
                    page: this.currentPage,
                    per_page: this.perPage
                }
                
                // Добавляем folder_id только если он не null
                if (folderId !== null && folderId !== undefined) {
                    params.folder_id = folderId
                }
                
                // Добавляем search только если есть значение
                if (this.searchQuery && this.searchQuery.trim()) {
                    params.search = this.searchQuery.trim()
                }
                
                console.log('Запрос файлов с параметрами:', params)

                const response = await axios.get('/api/v1/media', { params })

                // Если ответ пагинирован
                if (response.data.data && response.data.meta) {
                    this.files = response.data.data
                    this.totalFilesCount = response.data.meta.total
                    console.log('Загружено файлов:', this.files.length, 'из', this.totalFilesCount)
                } else {
                    this.files = response.data.data || []
                    this.totalFilesCount = this.files.length
                    console.log('Загружено файлов:', this.files.length)
                }
            } catch (error) {
                console.error('Ошибка загрузки файлов:', error)
                this.files = []
                this.totalFilesCount = 0
            } finally {
                this.loadingFiles = false
            }
        },

        // Обработка изменения сортировки
        handleSort(sortBy) {
            this.sortBy = sortBy
            console.log('Сортировка изменена:', this.sortBy)
            this.loadFiles(this.currentFolderId, true)
        },

        // Обработка поиска
        handleSearch(searchQuery) {
            this.searchQuery = searchQuery
            console.log('Поиск:', this.searchQuery)
            this.loadFiles(this.currentFolderId, true)
        },

        // Обработка изменения количества на странице
        handlePerPageChange(perPage) {
            this.perPage = perPage
            console.log('Показывать по:', this.perPage)
            this.loadFiles(this.currentFolderId, true)
        },

        // Смена страницы
        changePage(page) {
            if (page < 1 || page > this.totalPages) return

            this.currentPage = page
            console.log('Переход на страницу:', page)

            // Загружаем файлы для новой страницы
            this.loadFiles(this.currentFolderId, false)

            // Прокрутка к началу списка файлов
            const filesElement = document.getElementById('component-files')
            if (filesElement) {
                filesElement.scrollIntoView({ behavior: 'smooth', block: 'start' })
            }
        },

        handleFileClick(file) {
            console.log('Клик по файлу:', file)
            this.$emit('file-click', file)
        },
        
        // Обработка выбора файла в модальном режиме
        handleFileSelected(file) {
            console.log('Файл выбран в модальном режиме:', file)
            this.$emit('file-selected', file)
        },

        async downloadFile(file) {
            console.log('Скачиваем файл:', file)
            
            try {
                // Создаём временную ссылку для скачивания
                const link = document.createElement('a')
                link.href = file.url
                link.download = file.original_name || file.name  // Имя файла при скачивании
                link.target = '_blank'
                
                // Добавляем в DOM, кликаем и удаляем
                document.body.appendChild(link)
                link.click()
                document.body.removeChild(link)
                
                this.$notify({
                    type: 'success',
                    title: 'Скачивание',
                    text: `Файл "${file.original_name}" загружается`
                })
            } catch (error) {
                console.error('Ошибка скачивания файла:', error)
                
                this.$notify({
                    type: 'error',
                    title: 'Ошибка',
                    text: 'Не удалось скачать файл'
                })
            }
        },

        async deleteFile(file, event) {
            try {
                // Получаем элемент карточки файла для анимации
                const fileCard = event?.target?.closest('.file-card')
                
                if (fileCard) {
                    // Запускаем анимацию перемещения в корзину
                    await this.animateFileToTrash(fileCard)
                }

                const response = await axios.delete(`/api/v1/media/${file.id}`)

                this.$notify({
                    type: 'success',
                    title: 'Успешно',
                    text: response.data.message || `Файл "${file.original_name}" перемещён в корзину`
                })

                // Перезагружаем список файлов
                await this.loadFiles(this.currentFolderId)
                
                // Уведомляем родительский компонент о необходимости перезагрузить папки
                this.$emit('folders-reordered')
            } catch (error) {
                console.error('Ошибка удаления файла:', error)

                this.$notify({
                    type: 'error',
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось удалить файл'
                })
            }
        },
        
        /**
         * Анимация перемещения файла в корзину
         */
        async animateFileToTrash(fileCard) {
            return new Promise((resolve) => {
                // Создаём иконку корзины в правом нижнем углу
                const trashIcon = document.createElement('div')
                trashIcon.className = 'trash-icon-animated'
                
                // Создаём изображение отдельно с явными стилями
                const img = document.createElement('img')
                img.src = '/img/system/basket.png'
                img.alt = 'Корзина'
                img.style.width = '40px'
                img.style.height = '40px'
                img.style.objectFit = 'contain'
                img.style.display = 'block'
                img.style.pointerEvents = 'none'
                
                trashIcon.appendChild(img)
                
                // Явно устанавливаем fixed позиционирование
                trashIcon.style.position = 'fixed'
                trashIcon.style.bottom = '20px'
                trashIcon.style.right = '20px'
                trashIcon.style.zIndex = '10000'
                trashIcon.style.width = '60px'
                trashIcon.style.height = '60px'
                trashIcon.style.background = 'rgba(255, 255, 255, 0.98)'
                trashIcon.style.borderRadius = '50%'
                trashIcon.style.boxShadow = '0 8px 24px rgba(0, 0, 0, 0.2), 0 4px 12px rgba(0, 0, 0, 0.15), 0 0 0 2px rgba(59, 130, 246, 0.3)'
                trashIcon.style.display = 'flex'
                trashIcon.style.alignItems = 'center'
                trashIcon.style.justifyContent = 'center'
                trashIcon.style.pointerEvents = 'none'
                
                // Начальное состояние - невидима и уменьшена
                trashIcon.style.opacity = '0'
                trashIcon.style.transform = 'scale(0.5)'
                
                document.body.appendChild(trashIcon)
                
                // Анимация появления корзины
                requestAnimationFrame(() => {
                    trashIcon.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out'
                    trashIcon.style.opacity = '1'
                    trashIcon.style.transform = 'scale(1)'
                })
                
                // Клонируем карточку файла для анимации
                const fileClone = fileCard.cloneNode(true)
                fileClone.className = 'file-card-flying'
                
                // Получаем позицию карточки
                const rect = fileCard.getBoundingClientRect()
                fileClone.style.position = 'fixed'
                fileClone.style.left = rect.left + 'px'
                fileClone.style.top = rect.top + 'px'
                fileClone.style.width = rect.width + 'px'
                fileClone.style.height = rect.height + 'px'
                fileClone.style.zIndex = '9999'
                fileClone.style.margin = '0'
                
                document.body.appendChild(fileClone)
                
                // Скрываем оригинальную карточку плавно
                requestAnimationFrame(() => {
                    fileCard.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out'
                    fileCard.style.opacity = '0'
                    fileCard.style.transform = 'scale(0.9)'
                })
                
                // Запускаем анимацию полёта через небольшую задержку
                setTimeout(() => {
                    const trashRect = trashIcon.getBoundingClientRect()
                    
                    // Вычисляем центр корзины
                    const trashCenterX = trashRect.left + (trashRect.width / 2)
                    const trashCenterY = trashRect.top + (trashRect.height / 2)
                    
                    // Перемещаем клон к корзине с более плавной анимацией
                    fileClone.style.transition = 'all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94)'
                    fileClone.style.left = (trashCenterX - rect.width / 2) + 'px'
                    fileClone.style.top = (trashCenterY - rect.height / 2) + 'px'
                    fileClone.style.transform = 'scale(0.1) rotate(10deg)'
                    fileClone.style.opacity = '0'
                    
                    // Анимация корзины (плавное увеличение)
                    setTimeout(() => {
                        trashIcon.style.transition = 'transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)'
                        trashIcon.style.transform = 'scale(1.15)'
                        
                        setTimeout(() => {
                            trashIcon.style.transform = 'scale(1)'
                        }, 400)
                    }, 400)
                    
                    // Удаляем элементы после анимации
                    setTimeout(() => {
                        // Анимация исчезновения корзины
                        trashIcon.style.transition = 'opacity 0.3s ease-in, transform 0.3s ease-in'
                        trashIcon.style.opacity = '0'
                        trashIcon.style.transform = 'scale(0.8)'
                        
                        setTimeout(() => {
                            fileClone.remove()
                            trashIcon.remove()
                            resolve()
                        }, 300)
                    }, 900)
                }, 350)
            })
        },

        // Открыть модальное окно для перемещения
        async openMoveModal(file) {
            this.selectedFile = file

            // Загружаем список всех папок
            await this.loadAllFolders()

            // Открываем модальное окно (Bootstrap 5 API)
            const modalEl = document.getElementById('modal-move-file')
            if (modalEl) {
                const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl)
                modal.show()
            }
        },
        
        // Обработка после перемещения файла
        async handleFileMoved(targetFolderId) {
            console.log('Файл перемещён в папку:', targetFolderId)
            // Перезагружаем список файлов текущей папки
            await this.loadFiles(this.currentFolderId)
        },
        
        // Обработка закрытия модального окна перемещения
        handleModalClose() {
            this.selectedFile = null
        },
        
        // Открыть редактор изображения
        openCropEditor(file) {
            console.log('Открываем редактор для:', file)
            this.selectedImageForEdit = file
            this.showCropEditor = true
        },
        
        // Обработка после сохранения отредактированного изображения
        async handleImageSaved(newImage) {
            console.log('Изображение сохранено:', newImage)
            
            // Закрываем редактор
            this.closeCropEditor()
            
            // Перезагружаем список файлов
            await this.loadFiles(this.currentFolderId, false)
            
            // Уведомляем о том, что список обновлен
            this.$notify({
                type: 'info',
                title: 'Обновлено',
                text: 'Список файлов обновлен'
            })
        },
        
        // Закрыть редактор и вернуться к списку файлов
        closeCropEditor() {
            this.showCropEditor = false
            this.selectedImageForEdit = null
        },
        
        /**
         * Восстановить файл из корзины
         */
        async restoreFile(file) {
            try {
                const response = await axios.post(`/api/v1/media/${file.id}/restore`)
                
                if (response.data.success) {
                    this.$notify({
                        type: 'success',
                        title: 'Успешно',
                        text: response.data.message || `Файл "${file.original_name}" восстановлен`
                    })
                    
                    // Перезагружаем список файлов в корзине
                    await this.loadFiles(this.currentFolderId)
                    
                    // Уведомляем родительский компонент о необходимости перезагрузить папки
                    this.$emit('folders-reordered')
                } else {
                    this.$notify({
                        type: 'error',
                        title: 'Ошибка',
                        text: response.data.message || 'Не удалось восстановить файл'
                    })
                }
            } catch (error) {
                console.error('Ошибка восстановления файла:', error)
                this.$notify({
                    type: 'error',
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось восстановить файл'
                })
            }
        },

        // Загрузить все папки для выбора
        async loadAllFolders() {
            try {
                const response = await axios.get('/api/v1/folders/tree/all')
                this.allFolders = this.flattenFolders(response.data.data || [])
                console.log('Загружено папок для перемещения:', this.allFolders.length)
            } catch (error) {
                // Если нет эндпоинта tree, используем обычный
                try {
                    const response = await axios.get('/api/v1/folders')
                    this.allFolders = response.data.data || []
                } catch (err) {
                    console.error('Ошибка загрузки папок:', err)
                    this.allFolders = []
                }
            }
        },

        // Превратить дерево папок в плоский список с уровнями
        flattenFolders(folders, level = 0) {
            let result = []

            folders.forEach(folder => {
                result.push({
                    ...folder,
                    level: level
                })

                if (folder.children && folder.children.length > 0) {
                    result = result.concat(this.flattenFolders(folder.children, level + 1))
                }
            })

            return result
        },

    }
}
</script>

<style scoped lang="scss">
/**
 * ============================================================
 * Стили компонента папок (Bootstrap 5)
 * ============================================================
 */

// Карточка папки
.folder-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    min-height: 160px;
    
    &:hover {
        background-color: #f8f9fa;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        transform: translateY(-3px);
        border-color: var(--bs-primary);
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .card-title {
        font-size: 0.9rem;
        color: #212529;
        line-height: 1.2;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

// Иконка папки
.folder-icon {
    width: 60px;
    height: 60px;
    object-fit: contain;
    transition: transform 0.3s ease;
    
    .folder-card:hover & {
        transform: scale(1.1);
    }
}

// Drag handle для сортировки (используется position-absolute из Bootstrap)
.drag-handle {
    cursor: grab;
    padding: 0.35rem 0.5rem;
    background: rgba(59, 130, 246, 0.1);  // Синий фон
    border-radius: 0.375rem;
    transition: all 0.2s ease;
    z-index: 10;
    border: 1px solid rgba(59, 130, 246, 0.2);
    
    i {
        color: #3b82f6;  // Синий цвет иконки
        font-size: 0.875rem;
    }
    
    &:hover {
        background: rgba(59, 130, 246, 0.15);
        border-color: rgba(59, 130, 246, 0.3);
        transform: scale(1.05);
        
        i {
            color: #2563eb;  // Более тёмный синий
        }
    }
    
    &:active {
        cursor: grabbing;
        background: rgba(59, 130, 246, 0.2);
        transform: scale(0.95);
    }
}

// Состояния sortable
.sortable-ghost {
    opacity: 0.4;
    background: #f0f0f0;
}

.sortable-drag {
    opacity: 0.8;
    transform: rotate(2deg);
    cursor: grabbing !important;
}

.sortable-chosen {
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

.sortable-item {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

// Стили для кнопки "Назад"
.card-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;

    .btn {
        transition: all 0.3s ease;

        &:hover {
            transform: translateX(-5px);
        }
    }
}

// Пагинация
.pagination {
    margin: 0;

    .page-link {
        border-radius: 4px;
        margin: 0 2px;
        color: #007bff;

        &:hover {
            background-color: #e9ecef;
        }
    }

    .page-item {
        &.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }

        &.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            cursor: not-allowed;
        }
    }
}

// Анимация перемещения файла в корзину
.trash-icon-animated {
    position: fixed !important;
    bottom: 20px !important;
    right: 20px !important;
    z-index: 10000 !important;
    width: 60px !important;
    height: 60px !important;
    background: rgba(255, 255, 255, 0.98);
    border-radius: 50%;
    box-shadow: 
        0 8px 24px rgba(0, 0, 0, 0.2), 
        0 4px 12px rgba(0, 0, 0, 0.15),
        0 0 0 2px rgba(59, 130, 246, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    pointer-events: none;
    
    img {
        width: 40px;
        height: 40px;
        object-fit: contain;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.15));
        user-select: none;
        -webkit-user-select: none;
    }
}

.file-card-flying {
    pointer-events: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    will-change: transform, opacity, left, top;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
    transform-style: preserve-3d;
    -webkit-transform-style: preserve-3d;
}

// Плавное исчезновение оригинальной карточки
.file-card {
    transition: opacity 0.4s ease-out, transform 0.4s ease-out;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
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
    
    // Корзина для мобильных устройств (те же размеры)
    .trash-icon-animated {
        bottom: 15px;
        right: 15px;
        width: 55px;
        height: 55px;
        
        img {
            width: 35px;
            height: 35px;
        }
    }
}
</style>
