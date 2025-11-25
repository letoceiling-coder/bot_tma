<template>
    <div class="card w-100">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 col-sm-12 col-12">
                    <div class="upload-header mb-4">
                        <h4>Загрузка файлов до {{ uploadSettings.maxFiles }} шт. до {{ uploadSettings.maxFilesize }} МБ</h4>
                        <div class="upload-info text-muted mb-2">
                            <small>
                                <i class="fa-solid fa-info-circle mr-1"></i>
                                Поддерживаются: изображения, видео, аудио, документы, архивы
                            </small>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button
                                @click="triggerFileSelect"
                                class="btn btn-success"
                                :disabled="uploading || queuedFiles.length >= uploadSettings.maxFiles"
                            >
                                <i class="fa-solid fa-plus mr-2"></i> Файлы
                            </button>
                            <button
                                @click="uploadAllFiles"
                                class="btn btn-primary"
                                :disabled="uploading || validFiles.length === 0"
                            >
                                <i class="fa-solid fa-upload mr-2"></i> Загрузить ({{ validFiles.length }})
                            </button>
                            <button
                                @click="cancelAll"
                                class="btn btn-warning"
                                :disabled="validFiles.length === 0"
                            >
                                <i class="fa-solid fa-times mr-2"></i> Отменить
                            </button>
                            <span class="text-muted ml-3">
                        <small>{{ validFiles.length }} / {{ uploadSettings.maxFiles }} файлов</small>
                    </span>
                        </div>
                    </div>

                    <!-- Dropzone Area -->
                    <div ref="dropzoneElement" class="dropzone-custom">
                        <div class="dz-message text-center py-5">
                            <i class="fa-solid fa-cloud-arrow-up fa-5x mb-4 text-primary"></i>
                            <h4>Перетащите файлы сюда</h4>
                            <p class="text-muted">или нажмите для выбора</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-12 col-12">
                    <!-- Список файлов -->
                    <div v-if="validFiles.length > 0" class="files-list mt-4">
                        <div
                            v-for="(file, index) in validFiles"
                            :key="file.customId || file.upload?.uuid || index"
                            class="file-item d-flex align-items-center mb-3"
                        >
                            <!-- Превью -->
                            <div class="file-preview mr-3">
                                <img
                                    v-if="file.dataURL && isImageFile(file)"
                                    :src="file.dataURL"
                                    alt="preview"
                                    class="img-thumbnail"
                                    style="width: 60px; height: 60px; object-fit: cover;"
                                >
                                <div v-else class="file-icon">
                                    <i :class="getFileIcon(file)" class="fa-2x"></i>
                                </div>
                            </div>

                            <!-- Информация о файле -->
                            <div class="file-info flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong>{{ file.name }}</strong>
                                        <small class="text-muted ml-2">({{ formatFileSize(file.size) }})</small>
                                    </div>
                                    <div class="file-actions">
                                        <button
                                            v-if="file.status === 'queued'"
                                            @click="uploadFile(file)"
                                            class="btn btn-sm btn-primary mr-2"
                                        >
                                            <i class="fa-solid fa-upload"></i> Загрузить
                                        </button>
                                        <button
                                            v-if="file.status === 'uploading'"
                                            @click="cancelFile(file)"
                                            class="btn btn-sm btn-warning mr-2"
                                        >
                                            <i class="fa-solid fa-times"></i> Отменить
                                        </button>
                                        <button
                                            @click="removeFile(file)"
                                            class="btn btn-sm btn-danger"
                                        >
                                            <i class="fa-solid fa-trash"></i> Удалить
                                        </button>
                                    </div>
                                </div>

                                <!-- Прогресс бар -->
                                <div class="progress" style="height: 20px;">
                                    <div
                                        class="progress-bar"
                                        :class="{
                                    'bg-success': file.status === 'success',
                                    'bg-danger': file.status === 'error',
                                    'progress-bar-striped progress-bar-animated': file.status === 'uploading'
                                }"
                                        :style="{ width: file.progress + '%' }"
                                    >
                                        {{ file.progress }}%
                                    </div>
                                </div>

                                <!-- Статус -->
                                <div v-if="file.status === 'success'" class="text-success mt-1">
                                    <i class="fa-solid fa-check"></i> Загружено
                                </div>
                                <div v-if="file.status === 'error'" class="text-danger mt-1">
                                    <i class="fa-solid fa-exclamation-circle"></i> Ошибка: {{ file.errorMessage }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



        </div>
    </div>
</template>

<script>
import Dropzone from 'dropzone'
import 'dropzone/dist/dropzone.css'

export default {
    name: "download",
    props: {
        currentFolderId: {
            type: [Number, String, null],
            default: null
        }
    },
    data() {
        return {
            dropzone: null,
            uploading: false,
            queuedFiles: []
        }
    },
    computed: {
        uploadSettings() {
            // Получаем настройки из store
            return this.storage?.media?.uploadSettings || {
                maxFiles: 20,
                maxFilesize: 50, // Увеличено до 50 МБ для видео
                parallelUploads: 5,
                // Разрешаем все типы медиа: изображения, видео, аудио, документы
                acceptedFiles: 'image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar,.mp4,.avi,.mov,.wmv,.flv,.mkv,.webm,.mp3,.wav,.ogg,.m4a',
                acceptedExtensions: [
                    // Изображения
                    'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg',
                    // Видео
                    'mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm', 'mpeg', 'mpg',
                    // Аудио
                    'mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac',
                    // Документы
                    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt',
                    // Архивы
                    'zip', 'rar', '7z', 'tar', 'gz'
                ]
            }
        },
        validFiles() {
            // Фильтруем только валидные файлы с корректными данными
            const valid = this.queuedFiles.filter(file => {
                const isValid = file.name &&
                    file.size !== undefined &&
                    file.size !== null &&
                    !isNaN(file.size) &&
                    file.customId &&
                    file.status !== 'canceled' // Исключаем отменённые файлы

                if (!isValid) {
                    console.log('Файл не прошёл валидацию:', {
                        name: file.name,
                        size: file.size,
                        customId: file.customId,
                        status: file.status
                    })
                }

                return isValid
            })

            console.log('validFiles computed:', valid.length, 'из', this.queuedFiles.length)
            return valid
        }
    },
    mounted() {
        this.initDropzone()
    },
    beforeUnmount() {
        if (this.dropzone) {
            this.dropzone.destroy()
        }
    },
    methods: {
        initDropzone() {
            console.log('Инициализация Dropzone...')
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            const vm = this

            // Получаем токен из storage
            const accessToken = this.$root?.storage?.settings?.accessToken || this.storage?.settings?.accessToken

            console.log('dropzoneElement:', this.$refs.dropzoneElement)
            console.log('CSRF Token:', csrfToken)
            console.log('Access Token:', accessToken)

            // Получаем настройки из store
            const settings = vm.uploadSettings

            console.log('Настройки загрузки из store:', settings)

            this.dropzone = new Dropzone(this.$refs.dropzoneElement, {
                url: '/api/v1/media',
                method: 'post',
                autoProcessQueue: false,
                uploadMultiple: false,
                parallelUploads: settings.parallelUploads,
                maxFiles: settings.maxFiles,
                maxFilesize: settings.maxFilesize,
                acceptedFiles: settings.acceptedFiles,
                addRemoveLinks: false,
                previewsContainer: false,
                clickable: true,
                createImageThumbnails: true,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                params: {
                    // Добавляем folder_id через params
                },
                dictInvalidFileType: 'Недопустимый тип файла. Разрешены: ' + settings.acceptedExtensions.join(', '),
                dictFileTooBig: 'Файл слишком большой ({{filesize}}MB). Максимальный размер: {{maxFilesize}}MB.',
                dictMaxFilesExceeded: 'Превышено максимальное количество файлов ({{maxFiles}}).',
                accept: function(file, done) {
                    // Всегда принимаем файл, проверка дубликатов в addedfile
                    console.log('Accept функция: принимаем файл', file.name)
                    done()
                }
            })

            console.log('Dropzone инициализирован:', this.dropzone)

            // События Dropzone
            this.dropzone.on('addedfile', (file) => {
                console.log('Dropzone: addedfile event', file)

                // Проверяем что файл имеет валидные данные
                if (!file.name || file.size === undefined || file.size === null) {
                    console.warn('Файл с невалидными данными:', file)
                    vm.dropzone.removeFile(file)
                    return
                }

                // Проверка на максимальное количество файлов
                if (vm.queuedFiles.length >= vm.uploadSettings.maxFiles) {
                    console.log('Достигнуто максимальное количество файлов')
                    vm.dropzone.removeFile(file)
                    vm.$notify({
                        type: 'warning',
                        title: 'Предупреждение',
                        text: `Максимальное количество файлов: ${vm.uploadSettings.maxFiles}`
                    })
                    return
                }

                // Проверка на дубликаты ПЕРЕД добавлением в массив
                const duplicate = vm.queuedFiles.find(f =>
                    f.name === file.name &&
                    f.size === file.size &&
                    f.status !== 'error' &&
                    f.status !== 'canceled'
                )

                if (duplicate) {
                    console.log('Дубликат найден, удаляем файл')
                    vm.dropzone.removeFile(file)
                    vm.$notify({
                        type: 'warning',
                        title: 'Предупреждение',
                        text: `Файл ${file.name} уже добавлен`
                    })
                    return
                }

                // Создаем wrapper объект для реактивности Vue
                // Используем более уникальный ID
                const customId = `${Date.now()}_${Math.random()}_${vm.queuedFiles.length}`
                file.customId = customId

                const fileWrapper = {
                    file: file,              // Оригинальный File объект
                    name: file.name,         // Копируем свойства для реактивности
                    size: file.size,
                    type: file.type,
                    lastModified: file.lastModified,
                    dataURL: null,
                    progress: 0,
                    status: file.status || 'added',
                    customId: customId,
                    errorMessage: null
                }

                console.log('Добавляем файл в список:', fileWrapper.name, fileWrapper.size, fileWrapper.customId)
                vm.queuedFiles.push(fileWrapper)

                console.log('Текущий список файлов:', vm.queuedFiles.length)
                console.log('Valid files:', vm.validFiles.length)
            })

            // Очищаем input после добавления ВСЕХ файлов
            this.dropzone.on('addedfiles', (files) => {
                console.log('Все файлы добавлены, очищаем input. Всего файлов:', files.length)
                if (vm.dropzone.hiddenFileInput) {
                    vm.dropzone.hiddenFileInput.value = ''
                }
            })

            this.dropzone.on('thumbnail', (file, dataUrl) => {
                console.log('Thumbnail создан для:', file.name, 'customId:', file.customId)
                const index = vm.queuedFiles.findIndex(f => f.customId === file.customId)
                console.log('Индекс файла в массиве:', index)
                if (index !== -1) {
                    vm.queuedFiles[index].dataURL = dataUrl
                    console.log('Файл обновлён с thumbnail, dataURL длина:', dataUrl.length)
                    console.log('queuedFiles[index]:', vm.queuedFiles[index])
                } else {
                    console.error('Файл не найден в массиве по customId:', file.customId)
                }
            })

            this.dropzone.on('uploadprogress', (file, progress) => {
                const index = vm.queuedFiles.findIndex(f => f.customId === file.customId)
                console.log('Upload progress:', file.name, Math.round(progress) + '%', 'index:', index)
                if (index !== -1) {
                    vm.queuedFiles[index].progress = Math.round(progress)
                }
            })

            this.dropzone.on('success', (file, response) => {
                const index = vm.queuedFiles.findIndex(f => f.customId === file.customId)
                console.log('Upload success:', file.name, 'index:', index, 'response:', response)
                if (index !== -1) {
                    vm.queuedFiles[index].status = 'success'
                    vm.queuedFiles[index].progress = 100

                    vm.$notify({
                        type: 'success',
                        title: 'Успешно',
                        text: `Файл ${vm.queuedFiles[index].name} загружен`
                    })
                }

                // НЕ эмитим событие после каждого файла - ждём завершения всех
                // vm.$emit('file-uploaded', response.data)

                // Проверяем есть ли ещё файлы в очереди
                vm.checkQueueStatus()
            })
            
            // Событие завершения обработки одного файла
            this.dropzone.on('complete', (file) => {
                console.log('Complete:', file.name, 'status:', file.status)
                
                // Продолжаем обработку очереди если есть еще файлы
                const queuedFiles = vm.dropzone.getQueuedFiles()
                console.log('После complete - файлов в очереди:', queuedFiles.length)
                
                if (queuedFiles.length > 0 && vm.uploading) {
                    console.log('Продолжаем обработку очереди...')
                    vm.dropzone.processQueue()
                }
            })

            // Событие завершения всех загрузок
            this.dropzone.on('queuecomplete', () => {
                console.log('Все файлы обработаны')
                vm.uploading = false
                
                // Эмитим событие только после завершения всех загрузок
                const successCount = vm.queuedFiles.filter(f => f.status === 'success').length
                if (successCount > 0) {
                    console.log(`Загружено ${successCount} файлов, перезагружаем список`)
                    vm.$emit('file-uploaded')
                }
            })

            this.dropzone.on('error', (file, errorMessage, xhr) => {
                const index = vm.queuedFiles.findIndex(f => f.customId === file.customId)
                console.log('Dropzone error event:', file.name, errorMessage, 'index:', index)

                const errorMsg = typeof errorMessage === 'string' ? errorMessage : 'Ошибка загрузки'

                if (index !== -1) {
                    vm.queuedFiles[index].status = 'error'
                    vm.queuedFiles[index].errorMessage = errorMsg

                    vm.$notify({
                        type: 'error',
                        title: 'Ошибка',
                        text: `Не удалось загрузить ${vm.queuedFiles[index].name}`
                    })
                }
            })

            this.dropzone.on('sending', (file, xhr, formData) => {
                // Добавляем токен авторизации
                const accessToken = vm.$root?.storage?.settings?.accessToken || vm.storage?.settings?.accessToken
                if (accessToken) {
                    xhr.setRequestHeader('Authorization', `Bearer ${accessToken}`)
                    console.log('Добавлен Authorization header')
                }

                // Добавляем folder_id
                if (vm.currentFolderId) {
                    formData.append('folder_id', vm.currentFolderId)
                }

                const index = vm.queuedFiles.findIndex(f => f.customId === file.customId)
                console.log('Sending file:', file.name, 'index:', index)
                if (index !== -1) {
                    vm.queuedFiles[index].status = 'uploading'
                }
            })

            this.dropzone.on('canceled', (file) => {
                const index = vm.queuedFiles.findIndex(f => f.customId === file.customId)
                console.log('Canceled file:', file.name, 'index:', index)
                if (index !== -1) {
                    vm.queuedFiles[index].status = 'canceled'
                    vm.queuedFiles[index].progress = 0
                }
            })
        },

        updateFileInList(file) {
            // Этот метод больше не нужен, обновления происходят напрямую
            // Оставляю для совместимости
            console.log('updateFileInList вызван для:', file.name)
        },

        triggerFileSelect() {
            console.log('triggerFileSelect вызван')
            console.log('Dropzone объект:', this.dropzone)
            console.log('hiddenFileInput:', this.dropzone?.hiddenFileInput)

            if (this.dropzone && this.dropzone.hiddenFileInput) {
                this.dropzone.hiddenFileInput.click()
            } else {
                console.error('Dropzone или hiddenFileInput не найден!')
            }
        },

        uploadFile(fileWrapper) {
            // Проверяем что файл в очереди (имеет правильный статус)
            console.log('uploadFile вызван, статус:', fileWrapper.status)
            const file = fileWrapper.file // Получаем оригинальный File объект
            if (fileWrapper.status === Dropzone.QUEUED || fileWrapper.status === 'queued' || fileWrapper.status === 'added') {
                this.dropzone.processFile(file)
            }
        },

        uploadAllFiles() {
            this.uploading = true

            // Обрабатываем только файлы в очереди
            const queuedFiles = this.queuedFiles.filter(f =>
                f.status === Dropzone.QUEUED || f.status === 'queued' || f.status === 'added'
            )

            console.log('Загружаем файлов:', queuedFiles.length)
            console.log('Dropzone files:', this.dropzone.files)
            console.log('Dropzone getQueuedFiles:', this.dropzone.getQueuedFiles())

            if (queuedFiles.length > 0) {
                // Обрабатываем всю очередь
                this.dropzone.processQueue()
            } else {
                this.uploading = false
            }
        },

        checkQueueStatus() {
            const remainingFiles = this.dropzone.getQueuedFiles()
            console.log('Осталось файлов в очереди:', remainingFiles.length)

            if (remainingFiles.length === 0) {
                this.uploading = false
                console.log('Все файлы загружены!')
            }
        },

        cancelFile(fileWrapper) {
            const file = fileWrapper.file
            this.dropzone.cancelUpload(file)
            fileWrapper.status = 'canceled'
            fileWrapper.progress = 0
        },

        removeFile(fileWrapper) {
            const index = this.queuedFiles.findIndex(f => f.customId === fileWrapper.customId)
            if (index > -1) {
                this.queuedFiles.splice(index, 1)
            }

            // Удаляем файл из Dropzone
            const file = fileWrapper.file
            if (this.dropzone.files.includes(file)) {
                this.dropzone.removeFile(file)
            }
        },

        cancelAll() {
            // Копируем массив для безопасной итерации
            const filesToRemove = [...this.queuedFiles]

            filesToRemove.forEach(fileWrapper => {
                if (fileWrapper.status === 'uploading') {
                    this.dropzone.cancelUpload(fileWrapper.file)
                }
            })

            // Очищаем массив
            this.queuedFiles = []

            // Удаляем все файлы из Dropzone
            this.dropzone.removeAllFiles(true)

            // Очищаем input
            if (this.dropzone.hiddenFileInput) {
                this.dropzone.hiddenFileInput.value = ''
            }
        },

        formatFileSize(bytes) {
            // Проверка на валидность
            if (!bytes || bytes === undefined || bytes === null || isNaN(bytes)) {
                return '0 Bytes'
            }
            if (bytes === 0) return '0 Bytes'

            const k = 1024
            const sizes = ['Bytes', 'KB', 'MB', 'GB']
            const i = Math.floor(Math.log(bytes) / Math.log(k))
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
        },
        
        isImageFile(file) {
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']
            const extension = file.name.split('.').pop().toLowerCase()
            return imageExtensions.includes(extension) || (file.type && file.type.startsWith('image/'))
        },
        
        getFileIcon(file) {
            const extension = file.name.split('.').pop().toLowerCase()
            const type = file.type || ''
            
            // Видео
            if (type.startsWith('video/') || ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm', 'mpeg', 'mpg'].includes(extension)) {
                return 'fa-solid fa-file-video text-danger'
            }
            // Аудио
            if (type.startsWith('audio/') || ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac'].includes(extension)) {
                return 'fa-solid fa-file-audio text-info'
            }
            // PDF
            if (extension === 'pdf' || type === 'application/pdf') {
                return 'fa-solid fa-file-pdf text-danger'
            }
            // Word
            if (['doc', 'docx'].includes(extension)) {
                return 'fa-solid fa-file-word text-primary'
            }
            // Excel
            if (['xls', 'xlsx'].includes(extension)) {
                return 'fa-solid fa-file-excel text-success'
            }
            // PowerPoint
            if (['ppt', 'pptx'].includes(extension)) {
                return 'fa-solid fa-file-powerpoint text-warning'
            }
            // Архивы
            if (['zip', 'rar', '7z', 'tar', 'gz'].includes(extension)) {
                return 'fa-solid fa-file-zipper text-warning'
            }
            // Текст
            if (extension === 'txt' || type.startsWith('text/')) {
                return 'fa-solid fa-file-lines text-secondary'
            }
            // Изображения
            if (this.isImageFile(file)) {
                return 'fa-solid fa-file-image text-success'
            }
            
            // По умолчанию
            return 'fa-solid fa-file text-secondary'
        }
    }
}
</script>

<style scoped lang="scss">
.upload-header {
    h4 {
        margin: 0;
        font-weight: 600;
    }
}

.dropzone-custom {
    border: 2px dashed #007bff;
    border-radius: 8px;
    background-color: #f8f9fa;
    cursor: pointer;
    transition: all 0.3s ease;

    &:hover {
        border-color: #0056b3;
        background-color: #e9ecef;
    }

    .dz-message {
        margin: 0;

        i {
            transition: transform 0.3s ease;
        }
    }

    &:hover .dz-message i {
        transform: scale(1.1);
    }
}

.files-list {
    max-height: 500px;
    overflow-y: auto;
}

.file-item {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    transition: all 0.3s ease;

    &:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
}

.file-preview {
    .img-thumbnail {
        border-radius: 8px;
    }

    .file-icon {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 8px;
    }
}

.file-info {
    strong {
        font-size: 14px;
    }
}

.file-actions {
    .btn {
        padding: 4px 8px;
        font-size: 12px;
    }
}

.progress {
    border-radius: 10px;
}

.gap-2 {
    gap: 0.5rem;
}
</style>
