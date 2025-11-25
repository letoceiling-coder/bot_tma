<template>
    <div class="main-redesign">
        <FortuneWheelRedesign
            :wheel-data="customWheelData"
            :timer-text="timerText"
            :timer-minutes="timerMinutes"
            :required="wheelRequired"
            @spin-complete="handleSpinComplete"
            @how-to-play="handleHowToPlay"
            @tab-change="handleTabChange"
        />
    </div>
</template>

<script>
import axios from 'axios'
import FortuneWheelRedesign from '/resources/js/src/Pages/Redesign/FortuneWheelRedesign.vue'

export default {
    name: 'FortuneWheelExample',
    components: {
        FortuneWheelRedesign
    },
    emits: ['spin-result'],
    data() {
        return {
            timerText: 'Новый билет через',
            timerMinutes: 24 * 60, // 24 часа в минутах
            customWheelData: [],
            isLoading: false,
            wheelRequired: false
        }
    },
    mounted() {
        this.loadWheelData()
    },
    methods: {
        handleSpinComplete(section) {
            this.$emit('spin-result', section)
        },
        handleHowToPlay() {
            console.log('Показать инструкцию');
            // Здесь можно показать модальное окно с инструкцией
        },
        async loadWheelData() {
            this.isLoading = true
            try {
                const { data } = await axios.get('/api/v1/wheel-sections')
                const sections = data?.data ?? []
                this.customWheelData = sections.length ? sections : this.getFallbackSections()
                this.wheelRequired = data?.settings?.required ?? false
            } catch (error) {
                console.error(error)
                this.customWheelData = this.getFallbackSections()
                this.wheelRequired = false
            } finally {
                this.isLoading = false
            }
        },
        getFallbackSections() {
            return [
                { text: '1', type: 'number', image: 'figma/image 338.png', answer: '' },
                { text: '2', type: 'gift', image: 'figma/fartune.png', answer: '' },
                { text: '3', type: 'number', image: 'figma/main.png', answer: '' },
                { text: '4', type: 'number', image: 'figma/image 338.png', answer: '' },
                { text: '5', type: 'number', image: 'figma/gift_.png', answer: '' },
                { text: '6', type: 'number', image: 'figma/image 338.png', answer: '' },
                { text: '7', type: 'gift', image: 'figma/image 338.png', answer: '' },
                { text: '8', type: 'number', image: 'figma/image 338.png', answer: '' },
                { text: '9', type: 'number', image: 'figma/image 338.png', answer: '' },
                { text: '10', type: 'number', image: 'figma/image 338.png', answer: '' },
                { text: '11', type: 'number', image: 'figma/image 338.png', answer: '' },
                { text: '12', type: 'number', image: 'figma/image 338.png', answer: '' }
            ]
        },
        handleTabChange(tab) {
            console.log('Активная вкладка:', tab);
            // Здесь можно обработать смену вкладки
        }
    }
}
</script>

<style scoped>
.main-redesign {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
</style>

