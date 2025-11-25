<template>
  <div class="fortune-wheel-redesign">
    <!-- Header -->
    <FortuneHeader
      @action="handleHowToPlay"
    />

    <!-- Info Bar with Gift Icon -->
    <div class="info-bar-wrapper">
      <!-- Compact Info Bar -->
      <div class="info-bar">
        <div class="timer-compact">
          <span class="timer-label">{{ timerText }}</span>
          <span class="timer-value">{{ formattedTimer }}</span>
        </div>
        <div class="tickets-compact">
          <span class="tickets-label">У вас</span>
          <span class="tickets-count">{{ userTickets }}</span>
          <span class="tickets-word">{{ ticketsWord }}</span>
        </div>
      </div>

      <!-- Gift Icon -->
      <div class="gift-icon-container">
        <img :src="giftIconUrl" alt="Подарок" class="gift-icon" />
        <span class="gift-text">Подарок</span>
      </div>
    </div>

    <!-- Large Wheel Container -->
    <div class="wheel-container-large">
      <div class="wheel-wrapper-large">
        <canvas
          ref="wheelCanvas"
          class="wheel-canvas-large"
        ></canvas>
        <div class="wheel-center-large"></div>
        <div class="wheel-pointer-large"></div>
      </div>
    </div>

    <!-- Spin Button -->
    <button
      class="spin-button-large"
      :disabled="isSpinning || userTickets === 0 || !canSpin"
      @click="spin"
    >
      <span>Вращать колесо</span>
    </button>

    <!-- Bottom Navigation -->
    <BottomNav
      :active-tab="activeTab"
      @change="setActiveTab"
    />
  </div>
</template>

<script>
import BottomNav from '/resources/js/src/components/BottomNav.vue'
import FortuneHeader from '../Fartune/components/FortuneHeader.vue'
import asset from '/resources/js/src/utils/asset.js'

export default {
  name: 'FortuneWheelRedesign',
  components: {
    BottomNav,
    FortuneHeader
  },
  emits: ['spin-complete', 'how-to-play', 'tab-change'],
  props: {
    wheelData: {
      type: Array,
      default: () => [
        { text: '0', type: 'number', image: 'figma/image 338.png' },
        { text: '250', type: 'gift', image: 'figma/image 338.png' },
        { text: '300', type: 'number', image: 'figma/main.png' },
        { text: '0', type: 'number', image: 'figma/image 338.png' },
        { text: '500', type: 'number', image: 'figma/gift_.png' },
        { text: '0', type: 'number', image: 'figma/image 338.png' },
        { text: '350', type: 'gift', image: 'figma/image 338.png' },
        { text: '0', type: 'number', image: 'figma/image 338.png' },
        { text: '500', type: 'number', image: 'figma/image 338.png' },
        { text: '0', type: 'number', image: 'figma/image 338.png' },
        { text: '0', type: 'number', image: 'figma/image 338.png' },
        { text: '0', type: 'number', image: 'figma/image 338.png' }
      ]
    },
    timerText: {
      type: String,
      default: 'Новый билет через'
    },
    timerMinutes: {
      type: Number,
      default: 24 * 60
    },
    required: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      isSpinning: false,
      activeTab: 'wheel',
      wheel: null,
      remainingSeconds: 0,
      timerInterval: null,
      userTickets: 0,
      canSpin: true,
      ticketsCheckInterval: null,
      giftIconUrl: asset('figma/gift.png')
    }
  },
  mounted() {
    this.initWheel();
    this.initTelegram();
    this.startTimer();
    this.loadUserTickets();
    this.ticketsCheckInterval = setInterval(() => {
      this.loadUserTickets();
    }, 30000);
  },
  beforeUnmount() {
    if (this.wheel) {
      window.removeEventListener('resize', this.wheel.resize);
    }
    this.clearTimer();
    if (this.ticketsCheckInterval) {
      clearInterval(this.ticketsCheckInterval);
    }
  },
  methods: {
    initWheel() {
      this.wheel = new FortuneWheelClassRedesign(
        this.$refs.wheelCanvas,
        this.wheelData,
        {
          onSpinComplete: (section) => {
            this.$emit('spin-complete', section);
            this.isSpinning = false;
            this.loadUserTickets().then(() => {
              this.canSpin = this.userTickets > 0;
            });
          }
        }
      );
    },
    initTelegram() {
      if (window.Telegram && window.Telegram.WebApp) {
        window.Telegram.WebApp.ready();
        window.Telegram.WebApp.expand();
        if (window.Telegram.WebApp.setHeaderColor) {
          window.Telegram.WebApp.setHeaderColor('#F8A575');
        }
        if (window.Telegram.WebApp.setBackgroundColor) {
          window.Telegram.WebApp.setBackgroundColor('#F8A575');
        }
      }
    },
    async spin() {
      if (this.isSpinning || !this.wheel || this.userTickets === 0 || !this.canSpin) return;
      
      if (this.userTickets < 1) {
        return;
      }

      this.isSpinning = true;
      this.canSpin = false;

      try {
        const initData = this.getInitData();
        if (initData) {
          try {
            const axiosInstance = window.axios || (await import('axios')).default;
            await axiosInstance.post('/api/v1/telegram-users/spend-ticket', null, {
              params: { initData }
            });
            await this.loadUserTickets();
          } catch (error) {
            console.error('Ошибка при списании билета:', error);
            this.canSpin = true;
            this.isSpinning = false;
            return;
          }
        }
      } catch (error) {
        console.error('Ошибка при обработке билета:', error);
        this.canSpin = true;
        this.isSpinning = false;
        return;
      }

      const forcedIndex = this.required ? null : this.findZeroSectionIndex();
      this.wheel.spin(forcedIndex, this.required);
    },
    findZeroSectionIndex() {
      if (!Array.isArray(this.wheelData) || !this.wheelData.length) return null;
      const index = this.wheelData.findIndex(
        (section) => String(section?.text ?? '').trim() === '0'
      );
      return index >= 0 ? index : null;
    },
    handleHowToPlay() {
      if (this.$router) {
        this.$router.push('/pages');
      } else if (typeof window !== 'undefined') {
        window.location.href = '/pages';
      }
      this.$emit('how-to-play');
    },
    setActiveTab(tab) {
      if (this.activeTab === tab) {
        this.navigateByTab(tab);
        return;
      }
      this.activeTab = tab;
      this.$emit('tab-change', tab);
      this.navigateByTab(tab);
    },
    navigateByTab(tab) {
      const routeMap = {
        wheel: '/start',
        friends: '/friends',
        top: '/top'
      };
      const target = routeMap[tab];
      if (!target) return;
      if (this.$router) {
        this.$router.push(target);
      } else if (typeof window !== 'undefined') {
        window.location.href = target;
      }
    },
    async startTimer() {
      this.clearTimer();
      
      try {
        const initData = this.getInitData();
        if (initData) {
          const axiosInstance = window.axios || (await import('axios')).default;
          const response = await axiosInstance.get('/api/v1/telegram-users/tickets', {
            params: { initData }
          });
          
          if (response.data && response.data.success && response.data.seconds_until_next_ticket) {
            this.remainingSeconds = response.data.seconds_until_next_ticket;
          } else {
            this.remainingSeconds = Math.max(1, Math.round(this.timerMinutes * 60));
          }
        } else {
          this.remainingSeconds = Math.max(1, Math.round(this.timerMinutes * 60));
        }
      } catch (error) {
        console.error('Ошибка при получении таймера:', error);
        this.remainingSeconds = Math.max(1, Math.round(this.timerMinutes * 60));
      }
      
      this.timerInterval = setInterval(() => {
        if (this.remainingSeconds > 0) {
          this.remainingSeconds -= 1;
        } else {
          this.loadUserTickets();
          this.startTimer();
        }
      }, 1000);
    },
    clearTimer() {
      if (this.timerInterval) {
        clearInterval(this.timerInterval);
        this.timerInterval = null;
      }
    },
    getInitData() {
      if (window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.initData) {
        return window.Telegram.WebApp.initData;
      }
      
      const urlParams = new URLSearchParams(window.location.search);
      const tgWebAppData = urlParams.get('tgWebAppData');
      if (tgWebAppData) {
        return tgWebAppData;
      }
      
      const storedAuth = localStorage.getItem('_auth');
      if (storedAuth) {
        try {
          const auth = JSON.parse(storedAuth);
          if (auth.initData) {
            return auth.initData;
          }
        } catch (e) {
          // Игнорируем ошибку парсинга
        }
      }
      
      return null;
    },
    async loadUserTickets() {
      try {
        const initData = this.getInitData();
        if (!initData) {
          return;
        }

        const axiosInstance = window.axios || (await import('axios')).default;
        const response = await axiosInstance.get('/api/v1/telegram-users/tickets', {
          params: { initData }
        });

        if (response.data && response.data.success) {
          this.userTickets = response.data.tickets || 0;
          this.canSpin = this.userTickets > 0 && !this.isSpinning;
        }
      } catch (error) {
        console.error('Ошибка при загрузке билетов:', error);
      }
    }
  },
  watch: {
    wheelData: {
      handler(newData) {
        if (this.wheel) {
          this.wheel.updateSections(newData);
        }
      },
      deep: true
    },
    timerMinutes() {
      this.startTimer();
    }
  },
  computed: {
    formattedTimer() {
      const hours = Math.floor(this.remainingSeconds / 3600)
        .toString()
        .padStart(2, '0');
      const minutes = Math.floor((this.remainingSeconds % 3600) / 60)
        .toString()
        .padStart(2, '0');
      const seconds = (this.remainingSeconds % 60).toString().padStart(2, '0');
      return `${hours}:${minutes}:${seconds}`;
    },
    ticketsWord() {
      const lastDigit = this.userTickets % 10;
      const lastTwoDigits = this.userTickets % 100;
      
      if (lastTwoDigits >= 11 && lastTwoDigits <= 14) {
        return 'билетов';
      }
      
      if (lastDigit === 1) {
        return 'билет';
      } else if (lastDigit >= 2 && lastDigit <= 4) {
        return 'билета';
      } else {
        return 'билетов';
      }
    }
  }
}

// Улучшенный класс для управления колесом с большим размером
class FortuneWheelClassRedesign {
  constructor(canvas, sections, callbacks = {}) {
    this.canvas = canvas;
    this.ctx = canvas.getContext('2d');
    this.sections = sections;
    this.sectionCount = sections.length;
    this.anglePerSection = (2 * Math.PI) / this.sectionCount;
    this.currentRotation = 0;
    this.isSpinning = false;
    this.radius = 0;
    this.centerX = 0;
    this.centerY = 0;
    this.callbacks = callbacks;

    // Цвета для секций (сохраняем основные цвета)
    this.colors = [
      '#E77D65',
      '#EFB66C',
      '#F8A575',
      '#FDB083',
      '#E77D65',
      '#EFB66C'
    ];

    this.imageCache = {};
    this.imagesLoaded = false;

    this.init();
  }

  init() {
    this.resize();
    this.resizeHandler = () => this.resize();
    window.addEventListener('resize', this.resizeHandler);
    this.loadImages();
    this.draw();
  }

  normalizeAngle(angle) {
    const twoPi = Math.PI * 2;
    return ((angle % twoPi) + twoPi) % twoPi;
  }

  updateSections(newSections) {
    this.sections = newSections;
    this.sectionCount = newSections.length;
    this.anglePerSection = (2 * Math.PI) / this.sectionCount;
    this.loadImages();
    this.draw();
  }

  async loadImages() {
    const imagePromises = [];
    const uniqueImages = new Set();

    this.sections.forEach(section => {
      if (section.image && !uniqueImages.has(section.image)) {
        uniqueImages.add(section.image);
      }
    });

    uniqueImages.forEach(imagePath => {
      const promise = new Promise((resolve) => {
        const img = new Image();
        
        img.onload = () => {
          this.imageCache[imagePath] = img;
          resolve(img);
        };
        img.onerror = () => {
          console.warn(`Не удалось загрузить изображение: ${imagePath}`);
          resolve(null);
        };
        
        img.src = imagePath;
      });
      imagePromises.push(promise);
    });

    await Promise.all(imagePromises);
    this.imagesLoaded = true;
    this.draw();
  }

  resize() {
    const container = this.canvas.parentElement;
    // Уменьшаем размер колеса в 1.2 раза - используем 90% * 1.1 / 1.2 = 82.5% от доступного пространства
    const size = Math.min(container.clientWidth, container.clientHeight) * 0.9 * 1.1 / 1.2;
    
    const dpr = window.devicePixelRatio || 1;
    
    this.canvas.style.width = size + 'px';
    this.canvas.style.height = size + 'px';
    
    const actualWidth = size * dpr;
    const actualHeight = size * dpr;
    
    if (this.canvas.width !== actualWidth || this.canvas.height !== actualHeight) {
      this.canvas.width = actualWidth;
      this.canvas.height = actualHeight;
      
      this.ctx.setTransform(1, 0, 0, 1, 0, 0);
      this.ctx.scale(dpr, dpr);
    }
    
    this.radius = size / 2;
    this.centerX = size / 2;
    this.centerY = size / 2;
    
    this.draw();
  }

  draw() {
    const ctx = this.ctx;
    
    const dpr = window.devicePixelRatio || 1;
    const logicalWidth = this.canvas.width / dpr;
    const logicalHeight = this.canvas.height / dpr;
    
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    
    ctx.clearRect(0, 0, logicalWidth, logicalHeight);

    ctx.save();
    ctx.translate(this.centerX, this.centerY);
    ctx.rotate(this.currentRotation);

    // Рисуем секции с улучшенной видимостью
    for (let i = 0; i < this.sectionCount; i++) {
      const startAngle = i * this.anglePerSection - Math.PI / 2;
      const endAngle = (i + 1) * this.anglePerSection - Math.PI / 2;

      const colorIndex = i % this.colors.length;
      ctx.fillStyle = this.colors[colorIndex];

      ctx.beginPath();
      ctx.moveTo(0, 0);
      ctx.arc(0, 0, this.radius, startAngle, endAngle);
      ctx.closePath();
      ctx.fill();

      // Более толстые и контрастные границы между секторами
      ctx.strokeStyle = '#FFFFFF';
      ctx.lineWidth = Math.max(3, 4 / dpr);
      ctx.stroke();

      const section = this.sections[i];
      const textAngle = startAngle + this.anglePerSection / 2;

      // Рисуем изображение
      if (section.image && this.imageCache[section.image]) {
        ctx.save();
        const img = this.imageCache[section.image];
        
        const imgSize = Math.min(this.radius * 0.333, 60); // Размер изображения уменьшен в 1.5 раза
        const imgRadius = this.radius * 0.8; // Отступ от края уменьшен в 1.5 раза (было 0.7, теперь 0.8)
        const imgX = Math.cos(textAngle) * imgRadius;
        const imgY = Math.sin(textAngle) * imgRadius;

        ctx.translate(imgX, imgY);
        ctx.rotate(textAngle + Math.PI / 2 + Math.PI);

        ctx.imageSmoothingEnabled = true;
        if (typeof ctx.imageSmoothingQuality !== 'undefined') {
          ctx.imageSmoothingQuality = dpr >= 2 ? 'high' : 'medium';
        }

        ctx.drawImage(
          img,
          -imgSize / 2,
          -imgSize / 2,
          imgSize,
          imgSize
        );

        ctx.restore();
      }

      // Рисуем текст - увеличиваем размер шрифта для лучшей читаемости
      ctx.save();
      const textRadius = this.radius * 0.45;
      const textX = Math.cos(textAngle) * textRadius;
      const textY = Math.sin(textAngle) * textRadius;

      ctx.translate(textX, textY);
      ctx.rotate(textAngle + Math.PI / 2 + Math.PI / 2);

      ctx.shadowColor = 'rgba(0, 0, 0, 0.7)';
      ctx.shadowBlur = 4;
      ctx.shadowOffsetX = 2;
      ctx.shadowOffsetY = 2;

      ctx.fillStyle = '#FFFFFF';
      
      // Уменьшен размер шрифта в 2 раза
      const fontSize = dpr >= 2 ? 13 : 12;
      ctx.font = `bold ${fontSize}px "NEXT ART", Arial, sans-serif`;
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';

      ctx.fillText(section.text, 0, 0);

      ctx.shadowColor = 'transparent';
      ctx.shadowBlur = 0;
      ctx.shadowOffsetX = 0;
      ctx.shadowOffsetY = 0;

      ctx.restore();
    }

    ctx.restore();
  }

  spin(forceSectionIndex = null, useProbabilities = false) {
    if (this.isSpinning) return;

    this.isSpinning = true;
    const duration = 4000 + Math.random() * 2000;
    const minRotations = 3;
    const maxRotations = 5;
    const fullRotations = Math.floor(Math.random() * (maxRotations - minRotations + 1)) + minRotations;
    const twoPi = Math.PI * 2;
    const pointerAngle = -Math.PI / 2;

    const hasForce =
      typeof forceSectionIndex === 'number' &&
      !Number.isNaN(forceSectionIndex) &&
      forceSectionIndex >= 0;
    const normalizedForceIndex = hasForce
      ? forceSectionIndex % this.sectionCount
      : null;
    
    let targetSection;
    if (normalizedForceIndex !== null) {
      targetSection = normalizedForceIndex;
    } else if (useProbabilities && this.sections.length > 0) {
      targetSection = this.selectSectionByProbability();
    } else {
      targetSection = Math.floor(Math.random() * this.sectionCount);
    }
    const targetSectionCenterAngle =
      targetSection * this.anglePerSection -
      Math.PI / 2 +
      this.anglePerSection / 2;

    const normalizedCurrent = this.normalizeAngle(this.currentRotation);
    const desiredAlignment = this.normalizeAngle(pointerAngle - targetSectionCenterAngle);
    const deltaToAlignment = this.normalizeAngle(desiredAlignment - normalizedCurrent);
    const totalDelta = deltaToAlignment + fullRotations * twoPi;
    const finalRotation = this.currentRotation + totalDelta;

    const startRotation = this.currentRotation;
    const startTime = performance.now();

    const animate = (currentTime) => {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);

      const easeOut = 1 - Math.pow(1 - progress, 3);
      this.currentRotation = startRotation + (finalRotation - startRotation) * easeOut;
      this.draw();

      if (progress < 1) {
        requestAnimationFrame(animate);
      } else {
        this.currentRotation = finalRotation;
        this.draw();
        this.isSpinning = false;
        this.currentRotation = this.normalizeAngle(this.currentRotation);

        const selectedSection = this.sections[targetSection];
        if (this.callbacks.onSpinComplete) {
          this.callbacks.onSpinComplete(selectedSection);
        }
      }
    };

    requestAnimationFrame(animate);
  }

  selectSectionByProbability() {
    const weights = this.sections.map((section, index) => {
      const probability = parseFloat(section.probability) || 0;
      return { index, weight: probability };
    });

    const totalWeight = weights.reduce((sum, item) => sum + item.weight, 0);

    if (totalWeight === 0 || weights.length === 0) {
      return Math.floor(Math.random() * this.sectionCount);
    }

    let random = Math.random() * totalWeight;
    
    for (const item of weights) {
      random -= item.weight;
      if (random <= 0) {
        return item.index;
      }
    }

    return weights[weights.length - 1].index;
  }
}
</script>

<style scoped>
.fortune-wheel-redesign {
  position: relative;
  width: 100%;
  height: 100%;
  max-width: 375px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  padding: 4px 12px calc(var(--footer-height, 70px) + 8px) 12px;
  box-sizing: border-box;
  overflow: hidden;
  font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: linear-gradient(180deg, #F8A575 0%, #FDB083 100%);
}

/* Info Bar Wrapper */
.info-bar-wrapper {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  width: 100%;
  margin: 8px 0;
}

/* Compact Info Bar */
.info-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  flex: 1;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Gift Icon */
.gift-icon-container {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 8px;
}

.gift-icon {
  width: auto;
  height: auto;
  max-height: 40px;
  object-fit: contain;
}

.gift-text {
  font-size: 12px;
  font-weight: 500;
  color: #FFFFFF;
  margin-top: 4px;
  text-align: center;
}

.timer-compact,
.tickets-compact {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  flex: 1;
}

.timer-label,
.tickets-label {
  font-size: 10px;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.9);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.timer-value {
  font-size: 14px;
  font-weight: 700;
  color: #FFFFFF;
  font-family: 'NEXT ART', monospace;
}

.tickets-count {
  font-size: 20px;
  font-weight: 700;
  color: #FFFFFF;
  line-height: 1;
}

.tickets-word {
  font-size: 10px;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.9);
}

/* Large Wheel Container */
.wheel-container-large {
  flex: 1;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  padding: 8px;
  box-sizing: border-box;
  min-height: 0;
}

.wheel-wrapper-large {
  position: relative;
  width: 100%;
  max-width: min(293px, 82.5vw); /* Уменьшено в 1.2 раза: 352 / 1.2 = 293, 99vw / 1.2 = 82.5vw */
  aspect-ratio: 1 / 1;
  height: auto;
  margin: 0 auto; /* Центрирование */
  display: flex;
  align-items: center;
  justify-content: center;
}

.wheel-canvas-large {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  filter: drop-shadow(0px 6px 12px rgba(0, 0, 0, 0.3));
  will-change: transform;
  transform-origin: center center;
  image-rendering: -webkit-optimize-contrast;
  image-rendering: crisp-edges;
  image-rendering: high-quality;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.wheel-center-large {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 35px;
  height: 35px;
  background: linear-gradient(180deg, #F8A575 0%, #FDB083 100%);
  border-radius: 50%;
  border: 2.5px solid #FFFFFF;
  z-index: 5;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
}

.wheel-pointer-large {
  position: absolute;
  top: -18px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 18px solid transparent;
  border-right: 18px solid transparent;
  border-top: 30px solid #FFFFFF;
  z-index: 6;
  filter: drop-shadow(0px 3px 6px rgba(0, 0, 0, 0.35));
}

/* Spin Button */
.spin-button-large {
  width: calc(100% - 24px);
  max-width: 343px;
  height: 56px;
  margin: 8px auto;
  background: linear-gradient(8.92deg, #E77D65 23.82%, #EFB66C 192.33%);
  box-shadow: 0px 4px 8px rgba(255, 212, 137, 0.3);
  border: none;
  border-radius: 12px;
  cursor: pointer;
  transition: transform 0.2s, opacity 0.2s, background 0.2s;
  z-index: 10;
}

.spin-button-large:active:not(:disabled) {
  transform: scale(0.98);
}

.spin-button-large:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: #BBBBBB;
  box-shadow: none;
}

.spin-button-large span {
  font-size: 18px;
  font-weight: 600;
  line-height: 24px;
  color: #FFFFFF;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

@media (max-height: 700px) {
  .wheel-container-large {
    padding: 4px;
  }

  .info-bar {
    margin: 4px 0;
    padding: 6px 10px;
  }

  .spin-button-large {
    height: 50px;
    margin: 4px auto;
  }
}

@media (max-width: 375px) {
  .fortune-wheel-redesign {
    max-width: 100%;
  }
}
</style>

