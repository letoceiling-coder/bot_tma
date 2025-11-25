<template>
  <div>
    <FortuneWheel
      :wheel-data="customWheelData"
      :username="userName"
      :timer-text="timerText"
      @spin-complete="handleSpinComplete"
      @how-to-play="handleHowToPlay"
      @tab-change="handleTabChange"
    />
  </div>
</template>

<script>
import FortuneWheel from './FortuneWheel.vue'

export default {
  name: 'FortuneWheelExample',
  components: {
    FortuneWheel
  },
  data() {
    return {
      userName: 'Игрок',
      timerText: 'Новый билет через 17:12',
      customWheelData: [
        { text: '0', type: 'number', image: 'figma/image 338.png' },
        { text: '250', type: 'gift', image: 'figma/image 338.png' },
        { text: '300', type: 'number', image: 'figma/image 338.png' },
        { text: '0', type: 'number', image: 'figma/image 338.png' },
        { text: '500', type: 'number', image: 'figma/image 338.png' },
        { text: '0', type: 'number', image: 'figma/image 338.png' },
        { text: '350', type: 'gift', image: 'figma/image 338.png' },
        { text: '0', type: 'number', image: 'figma/image 338.png' },
        { text: '500', type: 'number', image: 'figma/image 338.png' },
        { text: '0', type: 'number', image: 'figma/image 338.png' },
        { text: '0', type: 'number', image: 'figma/image 338.png' },
        { text: '0', type: 'number', image: 'figma/image 338.png' }
      ]
    }
  },
  methods: {
    handleSpinComplete(section) {
      console.log('Колесо остановилось на:', section);
      // Здесь можно добавить логику обработки результата
      if (window.Telegram && window.Telegram.WebApp) {
        window.Telegram.WebApp.showAlert(`Вы выиграли: ${section.text}`);
      } else {
        alert(`Вы выиграли: ${section.text}`);
      }
    },
    handleHowToPlay() {
      console.log('Показать инструкцию');
      // Здесь можно показать модальное окно с инструкцией
    },
    handleTabChange(tab) {
      console.log('Активная вкладка:', tab);
      // Здесь можно обработать смену вкладки
    }
  }
}
</script>

<style scoped>
/* Стили для примера использования */
</style>

