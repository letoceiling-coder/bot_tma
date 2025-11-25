<template>
  <div class="page3-screen">
<!--    <div-->
<!--      class="backdrop"-->
<!--      :style="{ backgroundImage: `url(${asset('figma/page3/page.png')})` }"-->
<!--    ></div>-->
    <div class="content">
      <section class="headline">
        <div class="headline-card">
          <span>зови друзей</span>
        </div>
        <div class="headline-card secondary">
          <span>занимай топ</span>
        </div>
        <div class="headline-badge">получай больше подарков</div>
      </section>

      <section class="rewards">
        <div class="reward-card reward-card--accent">
          <span>1 место = 1 500 ₽</span>
        </div>
        <div class="reward-card">
          <span>2 место = 1 000 ₽</span>
        </div>
        <div class="reward-card">
          <span>3 место = 500 ₽</span>
        </div>
      </section>

      <section class="hero">
        <div class="hero-sun"></div>
<!--        <div class="hero-ring"></div>-->
        <img
          class="hero-characters"
          :src="asset('figma/page3/image 318.png')"
          alt="Игроки в топе"
          draggable="false"
        />
        <img
          class="hero-foreground"
          :src="asset('figma/page3/image 319.png')"
          alt="Бонусы"
          draggable="false"
        />
        <img
          class="hero-label hero-label--left"
          :src="asset('figma/page3/зови друзей.png')"
          alt="зови друзей"
          draggable="false"
        />
        <img
          class="hero-label hero-label--right"
          :src="asset('figma/page3/занимай топ.png')"
          alt="занимай топ"
          draggable="false"
        />
      </section>


    </div>
  </div>
</template>

<script>


export default {
  name: 'PageThree',

  props: {
    message: {
      type: String,
      default: 'Подключаем игру…'
    }
  }
}
</script>

<style scoped>
.page3-screen {
  position: relative;
  width: 100%;
  max-width: 375px;
  margin: 0 auto;
  padding: 0 16px;
  background: linear-gradient(180deg, #f8a575 0%, #fdb083 100%);
  font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  overflow: hidden;
  display: flex;
  align-items: stretch;
  justify-content: center;
}

.backdrop {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
  opacity: 0.2;
  pointer-events: none;
}

.content {
  position: relative;
  width: 100%;
  max-width: 343px;
  height: 100%;
  padding: 24px 0 16px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 12px;
  z-index: 1;
}

.headline {
  display: flex;
  flex-direction: column;
  gap: 10px;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: #fff;
  animation: fadeSlide 0.8s ease forwards;
  flex-shrink: 0;
}

.headline-card {
  padding: 12px 14px;
  border-radius: 12px;
  text-align: center;
  font-size: 22px;
  font-weight: 700;
  background: linear-gradient(8deg, #e77d65 0%, #efb66c 100%);
  box-shadow: 0 6px 14px rgba(231, 125, 101, 0.3);
  backdrop-filter: blur(6px);
}

.headline-card.secondary {
  background: linear-gradient(8deg, #ffbb83 0%, #ffe0b4 100%);
  color: #c94f35;
}

.headline-badge {
  align-self: center;
  padding: 6px 16px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.25);
  border: 1px solid rgba(255, 255, 255, 0.4);
  font-size: 12px;
  font-weight: 600;
  text-align: center;
  letter-spacing: 0.04em;
}

.rewards {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.reward-card {
  width: 100%;
  border-radius: 10px;
  padding: 8px 14px;
  text-align: center;
  font-weight: 700;
  font-size: 14px;
  color: #fff;
  background: rgba(231, 125, 101, 0.85);
  box-shadow: 0 5px 16px rgba(231, 125, 101, 0.25);
  animation: pulse 4s ease-in-out infinite;
}

.reward-card:nth-child(2) {
  animation-delay: 0.3s;
}

.reward-card:nth-child(3) {
  animation-delay: 0.6s;
}

.reward-card--accent {
  background: linear-gradient(8deg, #ffdfa4 0%, #ffb97e 100%);
  color: #c94f35;
}

.hero {
  position: relative;
  flex: 1 1 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4px 0 12px;
  width: calc(100% + 24px);
  margin-left: -12px;
  min-height: 0;
}

.hero-sun {
  position: absolute;
  width: 430px;
  height: 430px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, rgba(252, 173, 127, 0.45) 40%, transparent 70%);
  border-radius: 50%;
  animation: breathe 6s ease-in-out infinite;
}

.hero-ring {
  position: absolute;
  width: 360px;
  height: 360px;
  border-radius: 50%;
  border: 2px dashed rgba(255, 255, 255, 0.35);
  animation: rotate 18s linear infinite;
}

.hero-characters {
  width: min(280px, 90%);
  object-fit: contain;
  filter: drop-shadow(0 12px 25px rgba(0, 0, 0, 0.3));
  animation: float 4.8s ease-in-out infinite;
  pointer-events: none;
}

.hero-foreground {
  position: absolute;
  width: min(300px, 90%);
  pointer-events: none;
  filter: drop-shadow(0 15px 25px rgba(0, 0, 0, 0.35));
  animation: float 5.6s ease-in-out infinite;
  animation-delay: 0.7s;
}

.hero-label {
  position: absolute;
  width: 110px;
  pointer-events: none;
  animation: float 4.5s ease-in-out infinite;
  filter: drop-shadow(0 10px 18px rgba(0, 0, 0, 0.25));
}

.hero-label--left {
  left: -10px;
  top: 22%;
  animation-delay: 0.4s;
}

.hero-label--right {
  right: -10px;
  top: 32%;
  animation-delay: 0.9s;
}

.controls {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.info {
  width: 100%;
  padding: 16px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.25);
  box-shadow: 0 10px 22px rgba(226, 113, 83, 0.35);
  color: #fff;
  font-size: 14px;
  line-height: 1.4;
  text-align: center;
}

.controls-row {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.arrow-btn {
  width: 60px;
  height: 40px;
  border-radius: 8px;
  border: none;
  background: rgba(231, 125, 101, 0.9);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: transform 0.2s ease, opacity 0.2s ease;
  box-shadow: 0 6px 14px rgba(231, 125, 101, 0.35);
}

.arrow-btn span {
  display: block;
  width: 28px;
  height: 2px;
  background: #fff;
  position: relative;
}

.arrow-btn span::after {
  content: '';
  position: absolute;
  width: 10px;
  height: 10px;
  border-top: 2px solid #fff;
  border-right: 2px solid #fff;
  top: 50%;
  right: 0;
  transform: translateY(-50%) rotate(45deg);
}

.arrow-btn--left span::after {
  right: auto;
  left: 0;
  transform: translateY(-50%) rotate(-135deg);
}

.arrow-btn:active {
  transform: scale(0.94);
}

.pager {
  display: flex;
  gap: 10px;
  align-items: center;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.6);
  background: rgba(255, 255, 255, 0.25);
}

.dot.active {
  background: #e77d65;
  border-color: #fff;
}

.load-hint {
  font-size: 14px;
  font-weight: 600;
  color: #da5c40;
  text-shadow: 0 2px 6px rgba(255, 255, 255, 0.6);
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
    opacity: 0.85;
  }
}

@keyframes rotate {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

@media (max-height: 700px) {
  .content {
    padding-top: 14px;
    gap: 10px;
  }

  .hero {
    padding-bottom: 6px;
    width: calc(100% + 16px);
    margin-left: -8px;
  }

  .hero-characters {
    max-height: 320px;
  }
}
</style>

