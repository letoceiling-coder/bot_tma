<template>
    <!-- Полноэкранный редактор изображений -->
    <div class="crop-editor-fullscreen">
        <div class="crop-editor-container">
            <div class="crop-editor-header">
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary mr-3" @click="closeEditor">
                        <i class="fa-solid fa-arrow-left mr-1"></i> Назад к списку
                    </button>
                    <h5 class="mb-0">
                        <i class="fa-solid fa-crop mr-2"></i>
                        Редактор изображения
                        <span v-if="image" class="text-muted ml-2">
                            <small>{{ image.original_name }}</small>
                        </span>
                    </h5>
                </div>
                <button 
                    type="button" 
                    class="btn btn-success" 
                    @click="saveCroppedImage"
                    :disabled="saving"
                >
                    <i class="fa-solid fa-save mr-2"></i>
                    {{ saving ? 'Сохранение...' : 'Сохранить' }}
                </button>
            </div>
            
            <div class="crop-editor-body">
                    <div class="row no-gutters">
                        <!-- Панель инструментов слева -->
                        <div class="col-md-3 tools-panel">
                            <div class="p-3">
                                <h6 class="mb-3"><i class="fa-solid fa-sliders mr-2"></i>Инструменты</h6>
                                
                                <!-- Пресеты соотношений -->
                                <div class="tool-group mb-3">
                                    <label class="font-weight-bold">Соотношение сторон:</label>
                                    <div class="btn-group-vertical w-100" role="group">
                                        <button @click="setAspectRatio(null)" class="btn btn-sm btn-outline-secondary" :class="{active: !aspectRatio}">
                                            Свободное
                                        </button>
                                        <button @click="setAspectRatio(1)" class="btn btn-sm btn-outline-secondary">
                                            1:1 (Квадрат)
                                        </button>
                                        <button @click="setAspectRatio(16/9)" class="btn btn-sm btn-outline-secondary">
                                            16:9 (Широкий)
                                        </button>
                                        <button @click="setAspectRatio(4/3)" class="btn btn-sm btn-outline-secondary">
                                            4:3 (Стандарт)
                                        </button>
                                        <button @click="setAspectRatio(3/4)" class="btn btn-sm btn-outline-secondary">
                                            3:4 (Портрет)
                                        </button>
                                        <button @click="setAspectRatio(9/16)" class="btn btn-sm btn-outline-secondary">
                                            9:16 (Stories)
                                        </button>
                                    </div>
                                    <button 
                                        @click="fillImageWithAspectRatio" 
                                        class="btn btn-sm btn-success w-100 mt-2"
                                        :disabled="!aspectRatio"
                                    >
                                        <i class="fa-solid fa-expand mr-1"></i>
                                        Полный захват
                                    </button>
                                </div>

                                <!-- Трансформации -->
                                <div class="tool-group mb-3">
                                    <label class="font-weight-bold">Трансформации:</label>
                                    <div class="d-flex gap-2 mb-2">
                                        <button @click="rotate(90)" class="btn btn-sm btn-primary flex-fill">
                                            <i class="fa-solid fa-rotate-right"></i> 90°
                                        </button>
                                        <button @click="rotate(-90)" class="btn btn-sm btn-primary flex-fill">
                                            <i class="fa-solid fa-rotate-left"></i> -90°
                                        </button>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button @click="flip(true, false)" class="btn btn-sm btn-secondary flex-fill">
                                            <i class="fa-solid fa-arrows-left-right"></i> Гориз.
                                        </button>
                                        <button @click="flip(false, true)" class="btn btn-sm btn-secondary flex-fill">
                                            <i class="fa-solid fa-arrows-up-down"></i> Верт.
                                        </button>
                                    </div>
                                </div>

                                <!-- Зум -->
                                <div class="tool-group mb-3">
                                    <label class="font-weight-bold">Масштаб: {{ Math.round(zoom * 100) }}%</label>
                                    <input 
                                        v-model.number="zoom" 
                                        type="range" 
                                        min="0.1" 
                                        max="3" 
                                        step="0.1" 
                                        class="custom-range"
                                        @input="handleZoomChange"
                                    >
                                    <div class="d-flex gap-2 mt-2">
                                        <button @click="zoom = 1; handleZoomChange()" class="btn btn-sm btn-outline-primary flex-fill">
                                            Сбросить
                                        </button>
                                        <button @click="zoomIn" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-search-plus"></i>
                                        </button>
                                        <button @click="zoomOut" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-search-minus"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Размер вывода -->
                                <div class="tool-group mb-3">
                                    <label class="font-weight-bold">Размер вывода:</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Ширина</span>
                                        </div>
                                        <input v-model.number="outputWidth" type="number" class="form-control" min="1">
                                        <div class="input-group-append">
                                            <span class="input-group-text">px</span>
                                        </div>
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Высота</span>
                                        </div>
                                        <input v-model.number="outputHeight" type="number" class="form-control" min="1">
                                        <div class="input-group-append">
                                            <span class="input-group-text">px</span>
                                        </div>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input v-model="maintainAspectRatio" type="checkbox" class="custom-control-input" id="maintainAspect">
                                        <label class="custom-control-label" for="maintainAspect">
                                            Сохранять пропорции
                                        </label>
                                    </div>
                                </div>

                                <!-- Качество -->
                                <div class="tool-group mb-3">
                                    <label class="font-weight-bold">Качество: {{ quality }}%</label>
                                    <input 
                                        v-model.number="quality" 
                                        type="range" 
                                        min="1" 
                                        max="100" 
                                        class="custom-range"
                                    >
                                </div>

                                <!-- Формат -->
                                <div class="tool-group mb-3">
                                    <label class="font-weight-bold">Формат:</label>
                                    <select v-model="outputFormat" class="form-control form-control-sm">
                                        <option value="image/jpeg">JPEG</option>
                                        <option value="image/png">PNG</option>
                                        <option value="image/webp">WebP</option>
                                    </select>
                                </div>

                                <!-- Действия -->
                                <div class="tool-group">
                                    <button @click="applyCrop" class="btn btn-primary btn-block mb-2">
                                        <i class="fa-solid fa-crop mr-2"></i> Обрезать
                                    </button>
                                    <button @click="reset" class="btn btn-warning btn-block mb-2">
                                        <i class="fa-solid fa-undo mr-2"></i> Сбросить
                                    </button>
                                    <button @click="downloadCropped" class="btn btn-info btn-block mb-2">
                                        <i class="fa-solid fa-download mr-2"></i> Скачать
                                    </button>
                                </div>
                                
                                <!-- Превью обрезанного изображения -->
                                <div v-if="croppedPreview" class="tool-group mt-3">
                                    <label class="font-weight-bold">Превью:</label>
                                    <div class="preview-wrapper">
                                        <img :src="croppedPreview" alt="Preview" class="img-fluid">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Область редактирования -->
                        <div class="col-md-9 editor-area">
                            <div class="cropper-hint">
                                <div class="alert alert-info mb-2 py-2 px-3">
                                    <i class="fa-solid fa-info-circle mr-2"></i>
                                    <small>
                                        <strong>Подсказка:</strong> 
                                        Перетаскивайте область мышкой для перемещения. 
                                        Используйте маркеры по краям для изменения размера.
                                    </small>
                                </div>
                            </div>
                            <div class="cropper-wrapper">
                                <Cropper
                                    ref="cropper"
                                    :src="imageUrl"
                                    :stencil-props="{
                                        aspectRatio: aspectRatio,
                                        movable: true,
                                        resizable: true,
                                        handlers: {
                                            eastNorth: true,
                                            north: true,
                                            westNorth: true,
                                            west: true,
                                            westSouth: true,
                                            south: true,
                                            eastSouth: true,
                                            east: true
                                        },
                                        lines: {
                                            north: true,
                                            west: true,
                                            south: true,
                                            east: true
                                        },
                                        handlersClasses: {
                                            default: 'handler',
                                            hover: 'handler--hover',
                                            eastNorth: 'handler--east-north',
                                            north: 'handler--north',
                                            westNorth: 'handler--west-north',
                                            west: 'handler--west',
                                            westSouth: 'handler--west-south',
                                            south: 'handler--south',
                                            eastSouth: 'handler--east-south',
                                            east: 'handler--east'
                                        },
                                        linesClasses: {
                                            default: 'line',
                                            hover: 'line--hover',
                                            north: 'line--north',
                                            west: 'line--west',
                                            south: 'line--south',
                                            east: 'line--east'
                                        }
                                    }"
                                    :resize-image="{
                                        adjustStencil: false,
                                        touch: true,
                                        wheel: {
                                            ratio: 0.1
                                        }
                                    }"
                                    :move-image="{
                                        touch: true,
                                        mouse: true
                                    }"
                                    :default-size="{
                                        width: outputWidth,
                                        height: outputHeight
                                    }"
                                    image-restriction="stencil"
                                    @change="onChange"
                                    class="cropper"
                                />
                            </div>

                            <!-- Информация о кропе -->
                            <div class="crop-info p-3 bg-light">
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <strong>Исходный размер:</strong> 
                                            {{ image?.width }} × {{ image?.height }} px
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <strong>Обрезанный размер:</strong> 
                                            {{ croppedWidth }} × {{ croppedHeight }} px
                                        </small>
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
import { Cropper } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'

export default {
    name: "CropImage",
    components: {
        Cropper
    },
    props: {
        image: {
            type: Object,
            default: null
        }
    },
    emits: ['saved', 'close'],
    data() {
        return {
            imageUrl: '',
            aspectRatio: null,
            zoom: 1,
            outputWidth: 800,
            outputHeight: 600,
            maintainAspectRatio: true,
            quality: 90,
            outputFormat: 'image/jpeg',
            croppedWidth: 0,
            croppedHeight: 0,
            saving: false,
            coordinates: null,
            croppedPreview: null  // Превью обрезанного изображения
        }
    },
    watch: {
        image: {
            immediate: true,
            handler(newImage) {
                if (newImage && newImage.url) {
                    this.imageUrl = newImage.url
                    this.outputWidth = newImage.width || 800
                    this.outputHeight = newImage.height || 600
                    
                    // Определяем формат по расширению
                    if (newImage.extension === 'png') {
                        this.outputFormat = 'image/png'
                    } else if (newImage.extension === 'webp') {
                        this.outputFormat = 'image/webp'
                    } else {
                        this.outputFormat = 'image/jpeg'
                    }
                }
            }
        },
        maintainAspectRatio(newVal) {
            if (newVal && this.outputWidth && this.outputHeight) {
                this.aspectRatio = this.outputWidth / this.outputHeight
            } else {
                this.aspectRatio = null
            }
        }
    },
    methods: {
        onChange({ coordinates, canvas }) {
            this.coordinates = coordinates
            if (canvas) {
                this.croppedWidth = Math.round(canvas.width)
                this.croppedHeight = Math.round(canvas.height)
            }
        },

        setAspectRatio(ratio) {
            this.aspectRatio = ratio
            if (this.$refs.cropper) {
                this.$refs.cropper.refresh()
            }
        },
        
        // Полный захват изображения с соотношением сторон
        fillImageWithAspectRatio() {
            if (!this.$refs.cropper || !this.aspectRatio) return
            
            const { imageSize } = this.$refs.cropper.getResult()
            if (!imageSize) return
            
            const imageWidth = imageSize.width
            const imageHeight = imageSize.height
            const imageRatio = imageWidth / imageHeight
            
            let cropWidth, cropHeight, left, top
            
            // Если соотношение изображения больше целевого - ограничиваем по высоте
            if (imageRatio > this.aspectRatio) {
                cropHeight = imageHeight
                cropWidth = cropHeight * this.aspectRatio
                left = (imageWidth - cropWidth) / 2
                top = 0
            } else {
                // Иначе ограничиваем по ширине
                cropWidth = imageWidth
                cropHeight = cropWidth / this.aspectRatio
                left = 0
                top = (imageHeight - cropHeight) / 2
            }
            
            // Устанавливаем координаты для максимального захвата
            this.$refs.cropper.setCoordinates({
                left: left,
                top: top,
                width: cropWidth,
                height: cropHeight
            })
            
            this.$notify({
                type: 'success',
                title: 'Готово',
                text: 'Область обрезки подогнана под изображение'
            })
        },
        
        // Применить обрезку и показать превью
        applyCrop() {
            if (!this.$refs.cropper) return
            
            const { canvas } = this.$refs.cropper.getResult()
            if (!canvas) return
            
            // Создаём превью
            this.croppedPreview = canvas.toDataURL(this.outputFormat, this.quality / 100)
            
            this.$notify({
                type: 'success',
                title: 'Готово',
                text: 'Изображение обрезано. Проверьте превью'
            })
        },

        rotate(angle) {
            if (this.$refs.cropper) {
                this.$refs.cropper.rotate(angle)
            }
        },

        flip(horizontal, vertical) {
            if (this.$refs.cropper) {
                if (horizontal) {
                    this.$refs.cropper.flip(true, false)
                }
                if (vertical) {
                    this.$refs.cropper.flip(false, true)
                }
            }
        },

        handleZoomChange() {
            if (this.$refs.cropper) {
                this.$refs.cropper.zoom(this.zoom)
            }
        },

        zoomIn() {
            this.zoom = Math.min(3, this.zoom + 0.1)
            this.handleZoomChange()
        },

        zoomOut() {
            this.zoom = Math.max(0.1, this.zoom - 0.1)
            this.handleZoomChange()
        },

        reset() {
            this.zoom = 1
            this.aspectRatio = null
            this.outputWidth = this.image?.width || 800
            this.outputHeight = this.image?.height || 600
            this.quality = 90
            this.croppedPreview = null
            
            if (this.$refs.cropper) {
                this.$refs.cropper.reset()
            }
            
            this.$notify({
                type: 'info',
                title: 'Сброс',
                text: 'Все настройки сброшены'
            })
        },

        async downloadCropped() {
            if (!this.$refs.cropper) return

            const { canvas } = this.$refs.cropper.getResult()
            if (!canvas) return

            canvas.toBlob((blob) => {
                const url = URL.createObjectURL(blob)
                const link = document.createElement('a')
                link.href = url
                link.download = `cropped_${this.image.original_name}`
                document.body.appendChild(link)
                link.click()
                document.body.removeChild(link)
                URL.revokeObjectURL(url)
            }, this.outputFormat, this.quality / 100)
        },

        async saveCroppedImage() {
            if (!this.$refs.cropper || !this.image) {
                this.$notify({
                    type: 'warning',
                    title: 'Предупреждение',
                    text: 'Изображение не загружено'
                })
                return
            }

            this.saving = true

            // Показываем уведомление о начале сохранения
            this.$notify({
                type: 'info',
                title: 'Обработка',
                text: 'Сохранение изображения...'
            })

            try {
                const { canvas } = this.$refs.cropper.getResult()
                if (!canvas) {
                    throw new Error('Не удалось получить результат обрезки')
                }

                // Изменяем размер если указано
                let finalCanvas = canvas
                if (this.outputWidth && this.outputHeight && 
                    (canvas.width !== this.outputWidth || canvas.height !== this.outputHeight)) {
                    
                    const resizedCanvas = document.createElement('canvas')
                    resizedCanvas.width = this.outputWidth
                    resizedCanvas.height = this.outputHeight
                    
                    const ctx = resizedCanvas.getContext('2d')
                    ctx.drawImage(canvas, 0, 0, this.outputWidth, this.outputHeight)
                    finalCanvas = resizedCanvas
                }

                // Конвертируем в blob
                const blob = await new Promise((resolve) => {
                    finalCanvas.toBlob(resolve, this.outputFormat, this.quality / 100)
                })

                // Формируем FormData
                const formData = new FormData()
                const extension = this.outputFormat.split('/')[1]
                const fileName = `edited_${Date.now()}.${extension}`
                formData.append('file', blob, fileName)
                
                if (this.image.folder_id) {
                    formData.append('folder_id', this.image.folder_id)
                }

                // Отправляем на сервер
                const response = await axios.post('/api/v1/media', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })

                console.log('Файл успешно сохранен:', response.data)

                // Показываем успешное уведомление
                this.$notify({
                    type: 'success',
                    title: 'Успешно',
                    text: `Изображение "${response.data.data?.original_name || fileName}" сохранено`
                })

                // Отправляем событие с данными нового изображения
                this.$emit('saved', response.data.data)

            } catch (error) {
                console.error('Ошибка сохранения:', error)
                
                let errorMessage = 'Не удалось сохранить изображение'
                
                if (error.response) {
                    // Ошибка от сервера
                    errorMessage = error.response.data?.message || errorMessage
                    
                    if (error.response.status === 401) {
                        errorMessage = 'Необходима авторизация'
                    } else if (error.response.status === 413) {
                        errorMessage = 'Файл слишком большой'
                    } else if (error.response.status === 422) {
                        errorMessage = 'Неверный формат файла'
                    }
                }
                
                this.$notify({
                    type: 'error',
                    title: 'Ошибка сохранения',
                    text: errorMessage
                })
            } finally {
                this.saving = false
            }
        },

        closeEditor() {
            // Сбрасываем превью при закрытии
            this.croppedPreview = null
            this.$emit('close')
        }
    }
}
</script>

<style scoped lang="scss">
// Полноэкранный редактор
.crop-editor-fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: white;
    z-index: 9999;
    display: flex;
    flex-direction: column;
}

.crop-editor-container {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.crop-editor-header {
    background: #fff;
    border-bottom: 1px solid #dee2e6;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    
    h5 {
        margin: 0;
    }
}

.crop-editor-body {
    flex: 1;
    overflow: hidden;
    display: flex;
    
    .row {
        width: 100%;
        height: 100%;
        margin: 0;
    }
}

.tools-panel {
    background-color: #f8f9fa;
    border-right: 1px solid #dee2e6;
    height: 100%;
    overflow-y: auto;
}

.tool-group {
    label {
        font-size: 13px;
        margin-bottom: 8px;
        display: block;
    }

    .btn-group-vertical {
        .btn {
            text-align: left;
            
            &.active {
                background-color: #007bff;
                color: white;
                border-color: #007bff;
            }
        }
    }
}

.gap-2 {
    gap: 0.5rem;
}

.editor-area {
    background: #e9ecef;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.cropper-hint {
    padding: 10px 15px 0;
    
    .alert {
        margin-bottom: 5px;
        font-size: 13px;
        border-radius: 4px;
    }
}

.cropper-wrapper {
    flex: 1;
    min-height: 400px;
    position: relative;
    overflow: hidden;
}

.cropper {
    height: 100%;
    width: 100%;
}

.crop-info {
    border-top: 1px solid #dee2e6;
}

.custom-range {
    width: 100%;
}

// Стили для cropper handlers (маркеры изменения размера)
:deep(.handler) {
    background: #007bff;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    width: 12px;
    height: 12px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s ease;
}

:deep(.handler--hover) {
    background: #0056b3;
    transform: scale(1.3);
    box-shadow: 0 3px 6px rgba(0,0,0,0.5);
}

// Угловые маркеры больше для удобства
:deep(.handler--east-north),
:deep(.handler--west-north),
:deep(.handler--west-south),
:deep(.handler--east-south) {
    width: 14px;
    height: 14px;
}

// Линии границ области обрезки
:deep(.line) {
    border-color: rgba(255, 255, 255, 0.6);
    border-width: 1px;
    transition: border-color 0.2s ease;
}

:deep(.line--hover) {
    border-color: rgba(255, 255, 255, 1);
    border-width: 2px;
}

// Специфичные стили для каждой линии
:deep(.line--north),
:deep(.line--south) {
    cursor: ns-resize;
}

:deep(.line--west),
:deep(.line--east) {
    cursor: ew-resize;
}

// Улучшенная видимость области обрезки
:deep(.vue-advanced-cropper__foreground) {
    cursor: move;
}

:deep(.vue-advanced-cropper__background) {
    background: rgba(0, 0, 0, 0.5);
}

// Сетка для удобства (правило третей)
:deep(.vue-advanced-cropper__stretcher)::before,
:deep(.vue-advanced-cropper__stretcher)::after {
    content: '';
    position: absolute;
    background: rgba(255, 255, 255, 0.2);
}

:deep(.vue-advanced-cropper__stretcher)::before {
    left: 33.33%;
    right: 33.33%;
    top: 0;
    bottom: 0;
    border-left: 1px dashed rgba(255, 255, 255, 0.3);
    border-right: 1px dashed rgba(255, 255, 255, 0.3);
}

:deep(.vue-advanced-cropper__stretcher)::after {
    top: 33.33%;
    bottom: 33.33%;
    left: 0;
    right: 0;
    border-top: 1px dashed rgba(255, 255, 255, 0.3);
    border-bottom: 1px dashed rgba(255, 255, 255, 0.3);
}

// Превью обрезанного изображения
.preview-wrapper {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 10px;
    background: white;
    max-height: 200px;
    overflow: auto;
    
    img {
        max-width: 100%;
        height: auto;
        display: block;
        margin: 0 auto;
    }
}

// Адаптивность
@media (max-width: 768px) {
    .crop-editor-body {
        .row {
            flex-direction: column;
        }
    }

    .tools-panel {
        max-height: 250px;
        border-right: none;
        border-bottom: 1px solid #dee2e6;
    }

    .editor-area {
        flex: 1;
    }

    .cropper-wrapper {
        min-height: 300px;
    }
}
</style>
