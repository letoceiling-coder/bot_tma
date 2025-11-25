<template>
    <!-- Модальное окно медиа-менеджера (Bootstrap 5) -->
    <div ref="modalElement" class="modal fade" id="modal-media-file" tabindex="-1" aria-labelledby="modalMediaFileLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMediaFileLabel">
                        <i class="fa-solid fa-folder-open me-2"></i>
                        Медиа менеджер
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <MediaManager :modal="true"/>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "modal-media",
    data() {
        return {
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
        }
    }
}
</script>

<style scoped>
.modal-xl{
    max-width: 90%;
}
</style>
