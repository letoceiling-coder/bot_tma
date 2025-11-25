<template>
  <section class="cta">
    <div v-if="$slots.card" class="cta-card">
      <slot name="card"></slot>
    </div>

    <div class="cta-actions">
      <button
        class="arrow-btn arrow-btn--left"
        aria-label="Назад"
        :disabled="disablePrev"
        @click="$emit('prev')"
      >
        <span></span>
      </button>
      <div class="pager">
        <span
          v-for="(dot, index) in dots"
          :key="index"
          class="dot"
          :class="{ active: index === activeDot }"
        ></span>
      </div>
      <button
        class="arrow-btn arrow-btn--right"
        aria-label="Вперёд"
        :disabled="disableNext"
        @click="$emit('next')"
      >
        <span></span>
      </button>
    </div>

    <p class="load-hint">{{ message }}</p>
  </section>
</template>

<script>
export default {
  name: 'PageCta',
  props: {
    message: {
      type: String,
      default: 'Подключаем игру…'
    },
    dots: {
      type: Number,
      default: 3
    },
    activeDot: {
      type: Number,
      default: 0
    },
    disablePrev: {
      type: Boolean,
      default: false
    },
    disableNext: {
      type: Boolean,
      default: false
    }
  },
  emits: ['prev', 'next']
}
</script>

<style scoped>
.cta {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding-bottom: 12px;
}

.cta-card {
  width: 100%;
  padding: 16px 18px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 8px 20px rgba(226, 113, 83, 0.35);
  backdrop-filter: blur(10px);
  color: #fff;
}

.cta-actions {
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

.arrow-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  box-shadow: none;
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

.arrow-btn:active:not(:disabled) {
  transform: scale(0.95);
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
  background: transparent;
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
</style>


