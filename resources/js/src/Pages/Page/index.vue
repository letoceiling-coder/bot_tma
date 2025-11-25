<template>
  <div class="load-screen">
<!--    <div-->
<!--      class="backdrop"-->
<!--      :style="{ backgroundImage: `url(${asset('figma/page1/page.png')})` }"-->
<!--    ></div>-->
    <div class="content">
      <header class="headline">
        <span class="headline-label">ВЫИГРЫВАЙ</span>
        <span class="headline-amount">до 5000 ₽</span>
      </header>

      <div class="badges">
        <span class="badge">призы от 300 ₽</span>
        <span class="badge accent">до 5000 ₽</span>
        <span class="badge wide">подарки от партнера iwowwe</span>
        <span class="badge wide secondary">секретный бокс</span>
      </div>

      <section class="hero">
        <div class="hero-circle"></div>
        <img
          class="hero-main"
          :src="asset('figma/page1/ChatGPT Image 7 нояб. 2025 г., 16_42_37 (фон удален) 1.png')"
          alt="Игровой персонаж"
          draggable="false"
        />
<!--        <img-->
<!--          class="hero-side hero-side&#45;&#45;left"-->
<!--          :src="asset('figma/page1/ChatGPT Image 6 нояб. 2025 г., 18_54_45 (фон удален) 14.png')"-->
<!--          alt="Приз"-->
<!--          draggable="false"-->
<!--        />-->
        <img
          class="hero-decor hero-decor--ticket"
          :src="asset('figma/page1/image 314.png')"
          alt="Билет"
          draggable="false"
        />
        <img
          class="hero-decor hero-decor--coin"
          :src="asset('figma/page1/Group.png')"
          alt="Монеты"
          draggable="false"
        />
      </section>

    </div>
  </div>
</template>

<script>
export default {
  name: 'LoadScreen',
  props: {
    message: {
      type: String,
      default: 'Подключаем игру…'
    }
  }
}
</script>

<style scoped>
.load-screen {
  position: relative;
  width: 100%;
  max-width: 375px;
  margin: 0 auto;
  padding: 0 12px;
  background: linear-gradient(180deg, #f8a575 0%, #fdb083 100%);
  font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  /*overflow: hidden;*/
  display: flex;
  align-items: stretch;
  justify-content: center;
}

.backdrop {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
  opacity: 0.25;
  pointer-events: none;
}

.content {
  position: relative;
  width: 100%;
  max-width: 343px;
  padding: 24px 0 16px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  height: 100%;
  min-height: 0;
  z-index: 1;
}

.headline {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  text-align: center;
  color: #fff;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  animation: fadeSlide 0.8s ease forwards;
  flex-shrink: 0;
}

.headline-label {
  font-size: 22px;
  font-weight: 700;
}

.headline-amount {
  font-size: 30px;
  font-weight: 700;
  line-height: 1;
}

.badges {
  width: 100%;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
  flex-shrink: 0;
}

.badge {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 6px 10px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(8deg, rgba(231, 125, 101, 0.85) 0%, rgba(239, 182, 108, 0.85) 100%);
  backdrop-filter: blur(4px);
  box-shadow: 0 3px 10px rgba(231, 125, 101, 0.25);
  animation: pulse 3s ease-in-out infinite;
}

.badge:nth-child(2) {
  animation-delay: 0.4s;
}

.badge:nth-child(3) {
  animation-delay: 0.8s;
}

.badge:nth-child(4) {
  animation-delay: 1.2s;
}

.badge.wide {
  grid-column: span 2;
}

.badge.accent {
  background: linear-gradient(8deg, #ffdfa4 0%, #ffb97e 100%);
  color: #d0503f;
}

.badge.secondary {
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.4);
}

.hero {
  position: relative;
  width: calc(100% + 24px);
  margin-left: -12px;
  flex: 1 1 auto;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 4px 0 16px;
  min-height: 0;
}

.hero-circle {
  position: absolute;
  width: 420px;
  height: 420px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, rgba(255, 183, 126, 0.15) 55%, transparent 70%);
  border-radius: 50%;
  filter: blur(2px);
  animation: breathe 6s ease-in-out infinite;
}

.hero-main {
  width: min(300px, 85%);
  max-height: 420px;
  object-fit: contain;
  filter: drop-shadow(0 10px 30px rgba(0, 0, 0, 0.35));
  animation: float 4.2s ease-in-out infinite;
  pointer-events: none;
  user-select: none;
}

.hero-side {
  position: absolute;
  width: 110px;
  pointer-events: none;
  animation: float 5s ease-in-out infinite;
}

.hero-side--left {
  left: 0;
  bottom: 120px;
  transform: rotate(-6deg);
  animation-delay: 0.7s;
}

.hero-decor {
  position: absolute;
  pointer-events: none;
  filter: drop-shadow(0 6px 18px rgba(0, 0, 0, 0.2));
}

.hero-decor--ticket {
  width: 110px;
  left: -24px;
  top: 55%;
  transform: rotate(-10deg);
}

.hero-decor--coin {
  width: 85px;
  right: -18px;
  top: 24%;
  transform: rotate(15deg);
}

@keyframes float {
  0% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-12px);
  }
  100% {
    transform: translateY(0);
  }
}

@keyframes pulse {
  0%, 100% {
    transform: translateY(0);
    opacity: 0.95;
  }
  50% {
    transform: translateY(-3px);
    opacity: 1;
  }
}

@keyframes fadeSlide {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes breathe {
  0%, 100% {
    transform: scale(0.95);
    opacity: 0.4;
  }
  50% {
    transform: scale(1.05);
    opacity: 0.8;
  }
}

@media (max-height: 700px) {
  .content {
    padding-top: 12px;
    gap: 10px;
  }

  .hero {
    padding-bottom: 8px;
    width: calc(100% + 16px);
    margin-left: -8px;
  }

  .hero-main {
    max-height: 360px;
  }
}
</style>

