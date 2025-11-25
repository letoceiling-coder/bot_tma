<template>
    <!-- Модальное окно для перемещения файла (Bootstrap 5) -->
    <div ref="modalElement" class="modal fade" id="modal-move-file" tabindex="-1" aria-labelledby="modalMoveFileLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMoveFileLabel">
                        <i class="fa-solid fa-folder-open me-2"></i>
                        Переместить файл
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div v-if="file" class="mb-3">
                        <strong>Файл:</strong> {{ file.original_name }}
                    </div>

                    <div class="form-group">
                        <label>Выберите папку назначения:</label>
                        <select v-model="selectedFolderId" class="form-control">
                            <option :value="null">📁 Корневая папка</option>
                            <option
                                v-for="folder in availableFolders"
                                :key="folder.id"
                                :value="folder.id"
                                :disabled="folder.id === currentFolderId"
                            >
                                {{ '　'.repeat(folder.level || 0) }}📁 {{ folder.name }}
                            </option>
                        </select>
                        <small class="form-text text-muted">
                            Файл будет перемещен в выбранную папку. Корзина недоступна для выбора.
                        </small>
                    </div>

                    <!-- Превью выбранной папки -->
                    <div v-if="selectedFolderId" class="alert alert-info mt-3">
                        <i class="fa-solid fa-info-circle me-2"></i>
                        <strong>Путь:</strong> {{ targetFolderPath }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal">
                        Отмена
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="moveFile"
                        :disabled="moving"
                    >
                        <i class="fa-solid fa-folder-open me-2"></i>
                        {{ moving ? 'Перемещение...' : 'Переместить' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "MoveFileModal",
    props: {
        file: {
            type: Object,
            default: null
        },
        folders: {
            type: Array,
            default: () => []
        },
        currentFolderId: {
            type: [Number, String, null],
            default: null
        }
    },
    emits: ['move', 'close'],
    data() {
        return {
            selectedFolderId: null,
            moving: false,
            modalInstance: null  // Экземпляр Bootstrap Modal
        }
    },
    mounted() {
        // Инициализируем Bootstrap 5 Modal после монтирования компонента
        if (this.$refs.modalElement) {
            this.modalInstance = new window.bootstrap.Modal(this.$refs.modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            })
        }
    },
    beforeUnmount() {
        // Очищаем экземпляр модального окна при размонтировании
        if (this.modalInstance) {
            this.modalInstance.dispose()
            this.modalInstance = null
        }
    },
    computed: {
        /**
         * Фильтрованный список папок (исключая корзину)
         */
        availableFolders() {
            // Исключаем корзину (ID = 4) из списка доступных папок
            return this.folders.filter(folder => {
                // Проверяем по ID (корзина всегда имеет ID = 4)
                if (folder.id === 4) return false
                
                // Дополнительная проверка по флагу is_trash (если он есть)
                if (folder.is_trash === true) return false
                
                // Дополнительная проверка по имени (на всякий случай)
                if (folder.name === 'Корзина' || folder.slug === 'trash') return false
                
                return true
            })
        },
        
        targetFolderPath() {
            if (!this.selectedFolderId) {
                return '/ (Корневая папка)'
            }

            const folder = this.folders.find(f => f.id === this.selectedFolderId)
            if (!folder) return '/'

            // Строим путь из иерархии
            let path = folder.name
            let currentFolder = folder

            while (currentFolder.parent_id) {
                const parent = this.folders.find(f => f.id === currentFolder.parent_id)
                if (parent) {
                    path = parent.name + ' / ' + path
                    currentFolder = parent
                } else {
                    break
                }
            }

            return '/ ' + path
        }
    },
    watch: {
        file(newFile) {
            if (newFile) {
                this.selectedFolderId = newFile.folder_id
            }
        }
    },
    methods: {
        /**
         * Показать модальное окно (Bootstrap 5 API)
         */
        show() {
            if (this.modalInstance) {
                this.modalInstance.show()
            }
        },
        
        /**
         * Закрыть модальное окно (Bootstrap 5 API)
         */
        closeModal() {
            if (this.modalInstance) {
                this.modalInstance.hide()
            }
            this.$emit('close')
        },

        async moveFile() {
            if (!this.file) return

            // Проверка что папка изменилась
            if (this.selectedFolderId === this.file.folder_id) {
                this.$notify({
                    type: 'warning',
                    title: 'Предупреждение',
                    text: 'Файл уже находится в этой папке'
                })
                return
            }

            this.moving = true

            try {
                await axios.put(`/api/v1/media/${this.file.id}`, {
                    folder_id: this.selectedFolderId
                })

                this.$notify({
                    type: 'success',
                    title: 'Успешно',
                    text: `Файл ${this.file.original_name} перемещён`
                })

                this.closeModal()
                this.$emit('move', this.selectedFolderId)

            } catch (error) {
                console.error('Ошибка перемещения файла:', error)

                this.$notify({
                    type: 'error',
                    title: 'Ошибка',
                    text: 'Не удалось переместить файл'
                })
            } finally {
                this.moving = false
            }
        }
    }
}
</script>

<style scoped lang="scss">
// Стили для модального окна можно добавить здесь при необходимости
</style>

