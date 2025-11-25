<template>
  <div class="load-screen">
    <div class="load-content">
      <div class="branding">
        <div class="logo"></div>
        <div class="brand-text">
          <span class="brand-title">Fortune Wheel</span>
          <span class="brand-subtitle">Connecting game...</span>
        </div>
      </div>

      <div class="illustration">
        <img
          :src="asset('figma/load/load.png')"
          alt="Loading illustration"
          class="illustration-image"
          draggable="false"
        />
      </div>

      <div class="progress-block">
        <div class="progress-track">
          <div class="progress-fill"></div>
        </div>
        <p class="progress-text">{{ message }}</p>
      </div>
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
    },
    redirectDelay: {
      type: Number,
      default: 2000
    },
    redirectTarget: {
      type: String,
      default: '/start'
    }
  },
  data() {
    return {
      redirectTimeout: null
    }
  },
  mounted() {
    this.scheduleRedirect()
  },
  beforeUnmount() {
    this.clearRedirect()
  },
  methods: {
    scheduleRedirect() {
      this.clearRedirect()
      const delay = Math.max(0, this.redirectDelay)
      this.redirectTimeout = setTimeout(() => {
        if (this.$router) {
          this.$router.push(this.redirectTarget)
        } else if (typeof window !== 'undefined') {
          window.location.href = this.redirectTarget
        }
      }, delay)
    },
    clearRedirect() {
      if (this.redirectTimeout) {
        clearTimeout(this.redirectTimeout)
        this.redirectTimeout = null
      }
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
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px 16px;
  background: linear-gradient(180deg, #f8a575 0%, #fdb083 100%);
  font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  overflow: hidden;
}

.load-content {
  width: 100%;
  max-width: 375px;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  padding: 20px 0 40px;
}

.branding {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  color: #ffffff;
}

.logo {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.6);
  background: rgba(0, 0, 0, 0.2);
  box-shadow: inset 0 0 10px rgba(255, 255, 255, 0.2);
}

.brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
}

.brand-title {
  font-size: 16px;
  font-weight: 600;
}

.brand-subtitle {
  font-size: 12px;
  opacity: 0.8;
}

.illustration {
  width: 100%;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px 0;
}

.illustration-image {
  width: 100%;
  max-width: 360px;
  height: auto;
  object-fit: contain;
  pointer-events: none;
  user-select: none;
  filter: drop-shadow(0 8px 30px rgba(231, 125, 101, 0.45));
}

.progress-block {
  width: 100%;
  max-width: 300px;
  text-align: center;
}

.progress-track {
  width: 100%;
  height: 8px;
  background: rgba(255, 255, 255, 0.4);
  border-radius: 999px;
  overflow: hidden;
  position: relative;
  box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.15);
}

.progress-fill {
  width: 40%;
  height: 100%;
  background: linear-gradient(90deg, #e77d65 0%, #ffb97e 100%);
  border-radius: 999px;
  animation: progressSlide 1.8s ease-in-out infinite;
}

.progress-text {
  margin-top: 14px;
  font-size: 16px;
  font-weight: 600;
  color: #da5c40;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

@keyframes progressSlide {
  0% {
    transform: translateX(-60%);
  }
  50% {
    transform: translateX(20%);
  }
  100% {
    transform: translateX(160%);
  }
}

@media (max-height: 700px) {
  .load-content {
    padding: 10px 0 20px;
  }

  .illustration {
    padding: 10px 0;
  }
}
</style>

