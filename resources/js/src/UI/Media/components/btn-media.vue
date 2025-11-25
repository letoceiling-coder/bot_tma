<template>
    <div class="media-button-wrapper">
        <!-- Кнопка для открытия медиа менеджера -->
        <button 
            @click="openMediaManager" 
            type="button" 
            class="btn btn-primary"
            :class="buttonClass"
        >
            <i class="fa-solid fa-images me-2"></i>
            {{ buttonText }}
        </button>
        
        <!-- Превью выбранных файлов -->
        <div v-if="selectedFiles.length > 0" class="selected-files-preview mt-3">
            <div class="d-flex flex-wrap gap-2">
                <div 
                    v-for="file in selectedFiles" 
                    :key="file.id"
                    class="selected-file-item"
                >
                    <img 
                        v-if="file.type === 'photo'" 
                        :src="file.url" 
                        :alt="file.original_name"
                        class="selected-file-thumb"
                    >
                    <div v-else class="selected-file-icon">
                        <i class="fa-solid fa-file"></i>
                    </div>
                    <button 
                        @click="removeFile(file)" 
                        class="btn-remove"
                        type="button"
                    >
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Модальное окно с медиа менеджером (Bootstrap 5) -->
        <div 
            ref="modalElement"
            class="modal fade" 
            :id="modalId" 
            tabindex="-1" 
            :aria-labelledby="`${modalId}Label`"
            aria-hidden="true"
            data-bs-backdrop="static"
        >
            <div class="modal-dialog modal-xl" style="max-width: 95%;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" :id="`${modalId}Label`">
                            <i class="fa-solid fa-folder-open me-2"></i>
                            Выбор файлов
                            <span v-if="countFile > 1" class="badge bg-primary ms-2">
                                {{ selectedFiles.length }} / {{ countFile }}
                            </span>
                        </h5>
                        <button type="button" class="btn-close" @click="closeMediaManager" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0" style="min-height: 70vh;">
                        <MediaManager
                            :modal="true"
                            :max-files="countFile"
                            :selected-files="selectedFiles"
                            @file-selected="handleFileSelected"
                        />
                    </div>
                    <div v-if="countFile > 1" class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeMediaManager">
                            Отмена
                        </button>
                        <button 
                            type="button" 
                            class="btn btn-success" 
                            @click="confirmSelection"
                            :disabled="selectedFiles.length === 0"
                        >
                            <i class="fa-solid fa-check me-2"></i>
                            Выбрать ({{ selectedFiles.length }})
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import MediaManager from '../index.vue'

export default {
    name: "BtnMedia",
    components: {
        MediaManager
    },
    props: {
        modelValue: {
            type: [Object, Array, String, null],
            default: null
        },
        countFile: {
            type: Number,
            default: 1
        },
        buttonText: {
            type: String,
            default: 'Выбрать файл'
        },
        buttonClass: {
            type: String,
            default: ''
        },
        fields: {
            type: String,
            default: null
        }
    },
    emits: ['update:modelValue'],
    data() {
        return {
            selectedFiles: [],
            modalId: `media-modal-${Date.now()}`,
            modalInstance: null  // Экземпляр Bootstrap Modal
        }
    },
    mounted() {
        // Инициализируем Bootstrap 5 Modal после монтирования компонента
        this.$nextTick(() => {
            if (this.$refs.modalElement) {
                this.modalInstance = new window.bootstrap.Modal(this.$refs.modalElement, {
                    backdrop: 'static',
                    keyboard: true,
                    focus: true
                })
            }
        })
    },
    beforeUnmount() {
        // Очищаем экземпляр модального окна при размонтировании
        if (this.modalInstance) {
            this.modalInstance.dispose()
            this.modalInstance = null
        }
    },
    watch: {
        modelValue: {
            immediate: true,
            handler(newValue) {
                if (newValue) {
                    // Если передан массив
                    if (Array.isArray(newValue)) {
                        this.selectedFiles = [...newValue]
                    } 
                    // Если передан объект (один файл)
                    else if (typeof newValue === 'object') {
                        this.selectedFiles = [newValue]
                    }
                } else {
                    this.selectedFiles = []
                }
            }
        }
    },
    methods: {
        /**
         * Открыть медиа менеджер (Bootstrap 5 API)
         */
        openMediaManager() {
            if (this.modalInstance) {
                this.modalInstance.show()
            }
        },
        
        /**
         * Закрыть медиа менеджер (Bootstrap 5 API)
         */
        closeMediaManager() {
            if (this.modalInstance) {
                this.modalInstance.hide()
            }
        },
        
        handleFileSelected(file) {
            console.log('Файл выбран:', file, 'countFile:', this.countFile, 'fields:', this.fields)
            
            // Если можно выбрать только один файл
            if (this.countFile === 1) {
                this.selectedFiles = [file]
                
                // Если указан fields, возвращаем только значение этого поля
                const valueToEmit = this.fields && file[this.fields] !== undefined 
                    ? file[this.fields] 
                    : file
                
                // Эмитим один файл (не массив) или значение поля
                this.$emit('update:modelValue', valueToEmit)
                
                // Закрываем модальное окно
                this.closeMediaManager()
                
                this.$notify({
                    type: 'success',
                    title: 'Готово',
                    text: `Файл "${file.original_name}" выбран`
                })
            } 
            // Если можно выбрать несколько файлов
            else {
                const fileIndex = this.selectedFiles.findIndex(f => f.id === file.id)
                
                // Если файл уже выбран - убираем его
                if (fileIndex !== -1) {
                    this.selectedFiles.splice(fileIndex, 1)
                    console.log('Файл убран из выбранных')
                } 
                // Если файл не выбран - добавляем
                else {
                    // Проверяем лимит
                    if (this.selectedFiles.length >= this.countFile) {
                        this.$notify({
                            type: 'warning',
                            title: 'Превышен лимит',
                            text: `Можно выбрать не более ${this.countFile} файлов`
                        })
                        return
                    }
                    
                    this.selectedFiles.push(file)
                    console.log('Файл добавлен в выбранные:', this.selectedFiles.length)
                }
            }
        },
        
        confirmSelection() {
            if (this.selectedFiles.length === 0) {
                this.$notify({
                    type: 'warning',
                    title: 'Предупреждение',
                    text: 'Выберите хотя бы один файл'
                })
                return
            }
            
            // Если указан fields, возвращаем массив значений этого поля
            const valueToEmit = this.fields 
                ? this.selectedFiles.map(file => file[this.fields] !== undefined ? file[this.fields] : file)
                : [...this.selectedFiles]
            
            // Эмитим массив файлов или массив значений поля
            this.$emit('update:modelValue', valueToEmit)
            
            this.closeMediaManager()
            
            this.$notify({
                type: 'success',
                title: 'Готово',
                text: `Выбрано файлов: ${this.selectedFiles.length}`
            })
        },
        
        removeFile(file) {
            const index = this.selectedFiles.findIndex(f => f.id === file.id)
            if (index !== -1) {
                this.selectedFiles.splice(index, 1)
                
                // Обновляем v-model
                if (this.countFile === 1) {
                    this.$emit('update:modelValue', null)
                } else {
                    // Если указан fields, возвращаем массив значений этого поля
                    const valueToEmit = this.fields 
                        ? this.selectedFiles.map(file => file[this.fields] !== undefined ? file[this.fields] : file)
                        : [...this.selectedFiles]
                    
                    this.$emit('update:modelValue', valueToEmit)
                }
            }
        }
    }
}
</script>

<style scoped lang="scss">
.media-button-wrapper {
    .btn {
        transition: all 0.3s ease;
    }
}

.selected-files-preview {
    .gap-2 {
        gap: 0.5rem;
    }
}

.selected-file-item {
    position: relative;
    width: 80px;
    height: 80px;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    
    &:hover .btn-remove {
        opacity: 1;
    }
}

.selected-file-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.selected-file-icon {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    
    i {
        font-size: 2rem;
        color: #6c757d;
    }
}

.btn-remove {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 24px;
    height: 24px;
    padding: 0;
    background: rgba(220, 53, 69, 0.9);
    border: none;
    border-radius: 50%;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: all 0.2s ease;
    
    &:hover {
        background: rgba(220, 53, 69, 1);
        transform: scale(1.1);
    }
    
    i {
        font-size: 12px;
    }
}

.modal-xl {
    max-width: 95%;
}
</style>
