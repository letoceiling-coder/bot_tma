<template>
  <div class="page2-screen">
<!--    <div-->
<!--      class="backdrop"-->
<!--      :style="{ backgroundImage: `url(${asset('figma/page2/page.jpg')})` }"-->
<!--    ></div>-->
    <div class="content">
      <section class="headline-block">
        <div class="headline-card primary">
          <span>приглашай друзей</span>
        </div>
        <div class="headline-ampersand">&amp;</div>
        <div class="headline-card secondary">
          <span>получай билеты</span>
        </div>
      </section>

      <section class="hero">
        <div class="hero-glow"></div>

        <img
          class="hero-image"
          :src="asset('figma/page2/image 316.png')"
          alt="Игровые призы"
          draggable="false"
        />
      </section>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PageTwo',
  props: {
    message: {
      type: String,
      default: 'Подключаем игру…'
    }
  }
}
</script>

<style scoped>
.page2-screen {
  position: relative;
  width: 100%;
  max-width: 375px;
  margin: 0 auto;
  padding: 0 12px;
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
  opacity: 0.25;
  pointer-events: none;
}

.content {
  position: relative;
  max-width: 343px;
  width: 100%;
  height: 100%;
  padding: 24px 0 16px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 12px;
  z-index: 1;
}

.headline-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  color: #fff;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  animation: fadeSlide 0.8s ease forwards;
  flex-shrink: 0;
}

.headline-card {
  width: 100%;
  border-radius: 12px;
  padding: 12px 14px;
  text-align: center;
  font-size: 20px;
  font-weight: 700;
  box-shadow: 0 6px 14px rgba(231, 125, 101, 0.25);
  backdrop-filter: blur(6px);
}

.headline-card.primary {
  background: linear-gradient(8deg, #e77d65 0%, #efb66c 100%);
}

.headline-card.secondary {
  background: linear-gradient(8deg, #ffbf86 0%, #ffd9aa 100%);
  color: #c94f35;
  margin-top: 20px;
}

.headline-ampersand {
  position: absolute;
  margin-top: 16px;
  font-size: 60px;
  font-weight: 700;
  transform: rotate(-10deg);
  text-shadow: 0 6px 18px rgba(218, 92, 64, 0.4);
  animation: swing 2.6s ease-in-out infinite;
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

.hero-glow {
  position: absolute;
  width: 420px;
  height: 420px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.35) 0%, rgba(252, 173, 127, 0.4) 40%, transparent 70%);
  border-radius: 50%;
  animation: breathe 6s ease-in-out infinite;
}

.hero-ring {
  position: absolute;
  width: 360px;
  height: 360px;
  border-radius: 50%;
  border: 1.5px dashed rgba(255, 255, 255, 0.35);
  animation: rotate 16s linear infinite;
}

.hero-image {
  position: relative;
  width: min(280px, 90%);
  max-height: 360px;
  object-fit: contain;
  filter: drop-shadow(0 12px 30px rgba(0, 0, 0, 0.35));
  animation: float 4.5s ease-in-out infinite;
  pointer-events: none;
  user-select: none;
}

.hero-chip {
  position: absolute;
  width: 100px;
  filter: drop-shadow(0 8px 18px rgba(0, 0, 0, 0.25));
  animation: float 5s ease-in-out infinite;
  pointer-events: none;
}

.hero-chip--left {
  left: -8px;
  bottom: 100px;
  animation-delay: 0.4s;
}

.hero-chip--right {
  right: -12px;
  top: 90px;
  animation-delay: 0.9s;
}

.info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.info-card {
  width: 100%;
  padding: 14px 14px 16px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.25);
  box-shadow: 0 10px 24px rgba(226, 113, 83, 0.3);
  backdrop-filter: blur(10px);
}

.info-text {
  font-size: 13px;
  line-height: 1.35;
  color: #fff;
  margin: 0;
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
    opacity: 0.35;
  }
  50% {
    transform: scale(1.05);
    opacity: 0.9;
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

@keyframes swing {
  0%, 100% {
    transform: rotate(-12deg) translateY(0);
  }
  50% {
    transform: rotate(-6deg) translateY(-4px);
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

  .hero-image {
    max-height: 320px;
  }
}
</style>

