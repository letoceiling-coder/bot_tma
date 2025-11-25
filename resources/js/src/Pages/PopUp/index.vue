<template>
  <div class="popup-overlay" v-if="visible">
    <div class="popup-backdrop" @click="handleClose"></div>
    <div class="popup-card">
      <button class="close-btn" @click="handleClose" aria-label="Закрыть">
        <span></span>
      </button>

      <div class="popup-content">
        <h2 class="popup-title">
          {{ title }}
        </h2>
        <p class="popup-text">
          {{ description }}
        </p>

        <div class="popup-cta">
          <button class="primary-btn" @click="$emit('confirm')">
            <span>Получить подарок</span>
          </button>
          <p class="cta-note">Звёзды спишутся автоматически</p>
        </div>
      </div>

      <div class="popup-illustration">
        <img
          :src="asset('figma/popup/image 332.png')"
          alt="Секретный подарок"
          draggable="false"
        />
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'GiftPopUp',
  props: {
    visible: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: 'Секретный подарок от кролика'
    },
    description: {
      type: String,
      default:
        'Обменяй 50 звёзд на 20 прокруток рулетки и приблизься к своему призу'
    }
  },
  emits: ['close', 'confirm'],
  methods: {
    handleClose() {
      this.$emit('close');
    }
  }
}
</script>

<style scoped>
.popup-overlay {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 30px 16px;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(6px);
  z-index: 999;
}

.popup-backdrop {
  position: absolute;
  inset: 0;
}

.popup-card {
  position: relative;
  width: 100%;
  max-width: 325px;
  border-radius: 18px;
  padding: 28px 22px 24px;
  background: linear-gradient(
    180deg,
    rgba(247, 167, 117, 0.98) 0%,
    rgba(231, 125, 101, 0.95) 100%
  );
  box-shadow: 0 20px 55px rgba(0, 0, 0, 0.35);
  overflow: hidden;
  animation: popIn 0.35s ease forwards;
}

.close-btn {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.25);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s ease, background 0.2s ease;
}

.close-btn span {
  position: relative;
  width: 14px;
  height: 2px;
  background: transparent;
}

.close-btn span::before,
.close-btn span::after {
  content: '';
  position: absolute;
  width: 14px;
  height: 2px;
  background: #fff;
  border-radius: 999px;
  left: 0;
}

.close-btn span::before {
  transform: rotate(45deg);
}

.close-btn span::after {
  transform: rotate(-45deg);
}

.close-btn:active {
  transform: scale(0.9);
}

.popup-content {
  text-align: center;
  color: #fff;
  font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI',
    Roboto, sans-serif;
  padding-bottom: 140px;
}

.popup-title {
  font-size: 22px;
  line-height: 1.2;
  font-weight: 600;
  margin-bottom: 12px;
}

.popup-text {
  font-size: 14px;
  line-height: 1.4;
  opacity: 0.95;
}

.popup-cta {
  margin-top: 18px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: center;
}

.primary-btn {
  width: 100%;
  height: 48px;
  border: none;
  border-radius: 999px;
  background: linear-gradient(90deg, #ffdfb0 0%, #ffb97e 100%);
  color: #d0503f;
  font-size: 15px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  cursor: pointer;
  box-shadow: 0 8px 18px rgba(255, 212, 137, 0.45);
  transition: transform 0.2s ease;
}

.primary-btn:active {
  transform: scale(0.97);
}

.cta-note {
  font-size: 12px;
  opacity: 0.85;
}

.popup-illustration {
  position: absolute;
  width: 160px;
  right: -10px;
  bottom: -6px;
  pointer-events: none;
}

.popup-illustration img {
  width: 100%;
  height: auto;
  object-fit: contain;
  animation: float 4s ease-in-out infinite;
  filter: drop-shadow(0 12px 25px rgba(0, 0, 0, 0.35));
}

@keyframes popIn {
  from {
    opacity: 0;
    transform: translateY(20px) scale(0.96);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes float {
  0% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-8px);
  }
  100% {
    transform: translateY(0);
  }
}

@media (max-height: 640px) {
  .popup-card {
    padding-bottom: 18px;
  }

  .popup-content {
    padding-bottom: 120px;
  }
}
</style>

