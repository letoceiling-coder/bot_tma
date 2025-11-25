<template>
  <div class="fortune-wheel-container">
    <!-- Header -->

    <FortuneHeader
      @action="handleHowToPlay"
    />

    <!-- Timer Block -->
    <div class="timer-block">
      <div class="timer-content">
        <span class="timer-text">{{ formattedTimer }}</span>
      </div>
    </div>

    <!-- Wheel Container -->
    <div class="wheel-container">
      <div class="wheel-wrapper">
        <canvas
          ref="wheelCanvas"
          class="wheel-canvas"
        ></canvas>
        <div class="wheel-center"></div>
        <div class="wheel-pointer"></div>
      </div>
    </div>

    <!-- Tickets Info Block -->
    <div class="tickets-block">
      <div class="tickets-content">
        <span class="tickets-text">
          <span class="tickets-label">У вас</span>
          <span class="tickets-count">{{ userTickets }}</span>
          <span class="tickets-word">{{ ticketsWord }}</span>
        </span>
      </div>
    </div>

    <!-- Spin Button -->
    <button
      class="spin-button"
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
import FortuneHeader from './components/FortuneHeader.vue'

export default {
  name: 'FortuneWheel',
  components: {
    BottomNav,
    FortuneHeader
  },
  props: {
    wheelData: {
      type: Array,
      default: () => [
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
    },
    timerText: {
      type: String,
      default: 'Новый билет через'
    },
    timerMinutes: {
      type: Number,
      default: 24 * 60 // 24 часа в минутах
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
      ticketsCheckInterval: null
    }
  },
  mounted() {
    this.initWheel();
    this.initTelegram();
    this.startTimer();
    this.loadUserTickets();
    // Обновляем билеты каждые 30 секунд
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
      this.wheel = new FortuneWheelClass(
        this.$refs.wheelCanvas,
        this.wheelData,
        {
          onSpinComplete: (section) => {
            this.$emit('spin-complete', section);
            this.isSpinning = false;
            // После завершения вращения проверяем билеты и разблокируем кнопку
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
        // setHeaderColor и setBackgroundColor не поддерживаются в версии 6.0+
        // Используйте themeParams для настройки цветов
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
      
      // Проверяем билеты перед началом вращения
      if (this.userTickets < 1) {
        return;
      }

      // Блокируем кнопку и списываем билет
      this.isSpinning = true;
      this.canSpin = false;

      try {
        // Списываем билет через API
        const initData = this.getInitData();
        if (initData) {
          try {
            const axiosInstance = window.axios || (await import('axios')).default;
            await axiosInstance.post('/api/v1/telegram-users/spend-ticket', null, {
              params: { initData }
            });
            // Обновляем количество билетов после списания
            await this.loadUserTickets();
          } catch (error) {
            console.error('Ошибка при списании билета:', error);
            // Если ошибка, возвращаем возможность крутить (билет не был списан)
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

      // Начинаем вращение колеса
      // Если required = false, останавливаемся на секторе с text="0"
      // Если required = true, используем вероятностную логику
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
      
      // Получаем время до следующего билета из API
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
            // Fallback: используем значение из props
            this.remainingSeconds = Math.max(1, Math.round(this.timerMinutes * 60));
          }
        } else {
          // Fallback: используем значение из props
          this.remainingSeconds = Math.max(1, Math.round(this.timerMinutes * 60));
        }
      } catch (error) {
        console.error('Ошибка при получении таймера:', error);
        // Fallback: используем значение из props
        this.remainingSeconds = Math.max(1, Math.round(this.timerMinutes * 60));
      }
      
      this.timerInterval = setInterval(() => {
        if (this.remainingSeconds > 0) {
          this.remainingSeconds -= 1;
        } else {
          // Когда таймер достиг нуля, перезагружаем билеты и таймер
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
      // Получаем initData из различных источников
      if (window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.initData) {
        return window.Telegram.WebApp.initData;
      }
      
      // Проверяем URL параметры
      const urlParams = new URLSearchParams(window.location.search);
      const tgWebAppData = urlParams.get('tgWebAppData');
      if (tgWebAppData) {
        return tgWebAppData;
      }
      
      // Проверяем localStorage
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
          
          // Обновляем таймер если есть информация о следующем билете
          if (response.data.seconds_until_next_ticket) {
            const hours = Math.floor(response.data.seconds_until_next_ticket / 3600);
            const minutes = Math.floor((response.data.seconds_until_next_ticket % 3600) / 60);
            // Можно обновить таймер, но для простоты оставляем текущую логику
          }
          
          // Обновляем возможность крутить
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
      return `${this.timerText} ${hours}:${minutes}:${seconds}`;
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

// Класс для управления колесом
class FortuneWheelClass {
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

    // Цвета для секций
    this.colors = [
      '#E77D65',
      '#EFB66C',
      '#F8A575',
      '#FDB083',
      '#E77D65',
      '#EFB66C'
    ];

    // Кэш для загруженных изображений
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
        
        // Для Retina дисплеев изображения будут автоматически масштабироваться
        // через canvas.scale(dpr, dpr) в методе resize()
        
        img.onload = () => {
          this.imageCache[imagePath] = img;
          resolve(img);
        };
        img.onerror = () => {
          console.warn(`Не удалось загрузить изображение: ${imagePath}`);
          resolve(null);
        };
        
        // Устанавливаем src после обработчиков событий
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
    const size = Math.min(container.clientWidth, container.clientHeight) / 1.2;
    
    // Получаем device pixel ratio для Retina дисплеев (iPhone, iPad и т.д.)
    const dpr = window.devicePixelRatio || 1;
    
    // Устанавливаем CSS размер (логический размер для отображения)
    this.canvas.style.width = size + 'px';
    this.canvas.style.height = size + 'px';
    
    // Устанавливаем реальный размер canvas с учетом DPR
    // Это предотвращает пикселизацию на Retina дисплеях
    const actualWidth = size * dpr;
    const actualHeight = size * dpr;
    
    // Проверяем изменился ли размер или DPR
    if (this.canvas.width !== actualWidth || this.canvas.height !== actualHeight) {
      this.canvas.width = actualWidth;
      this.canvas.height = actualHeight;
      
      // Сбрасываем трансформации и масштабируем контекст
      // Это нужно делать каждый раз при изменении размера canvas
      this.ctx.setTransform(1, 0, 0, 1, 0, 0); // Сброс трансформации
      this.ctx.scale(dpr, dpr); // Применяем масштаб для Retina
    }
    
    // Используем логический размер для расчетов (после scale)
    this.radius = size / 2;
    this.centerX = size / 2;
    this.centerY = size / 2;
    
    this.draw();
  }

  draw() {
    const ctx = this.ctx;
    
    // Получаем логический размер (после масштабирования через scale)
    const dpr = window.devicePixelRatio || 1;
    const logicalWidth = this.canvas.width / dpr;
    const logicalHeight = this.canvas.height / dpr;
    
    // Включаем высокое качество сглаживания для Retina дисплеев
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    
    ctx.clearRect(0, 0, logicalWidth, logicalHeight);

    ctx.save();
    ctx.translate(this.centerX, this.centerY);
    ctx.rotate(this.currentRotation);

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

      ctx.strokeStyle = '#FFFFFF';
      // Увеличиваем толщину линии для Retina дисплеев
      ctx.lineWidth = Math.max(2, 3 / dpr);
      ctx.stroke();

      const section = this.sections[i];
      const textAngle = startAngle + this.anglePerSection / 2;

      // Рисуем изображение
      if (section.image && this.imageCache[section.image]) {
        ctx.save();
        const img = this.imageCache[section.image];
        
        // Размер изображения в логических единицах
        const imgSize = Math.min(this.radius * 0.4, 70);
        const imgRadius = this.radius * 0.75;
        const imgX = Math.cos(textAngle) * imgRadius;
        const imgY = Math.sin(textAngle) * imgRadius;

        ctx.translate(imgX, imgY);
        ctx.rotate(textAngle + Math.PI / 2 + Math.PI);

        // Для четкости на Retina дисплеях используем высокое качество сглаживания
        ctx.imageSmoothingEnabled = true;
        
        // Используем лучшее качество сглаживания для Retina дисплеев
        if (typeof ctx.imageSmoothingQuality !== 'undefined') {
          ctx.imageSmoothingQuality = dpr >= 2 ? 'high' : 'medium';
        }

        // Для лучшего качества на Retina увеличиваем размер изображения
        // Canvas уже масштабирован через scale(dpr, dpr), поэтому изображения
        // автоматически будут рисоваться в более высоком разрешении
        ctx.drawImage(
          img,
          -imgSize / 2,
          -imgSize / 2,
          imgSize,
          imgSize
        );

        ctx.restore();
      }

      // Рисуем текст
      ctx.save();
      const textRadius = this.radius * 0.4;
      const textX = Math.cos(textAngle) * textRadius;
      const textY = Math.sin(textAngle) * textRadius;

      ctx.translate(textX, textY);
      ctx.rotate(textAngle + Math.PI / 2 + Math.PI / 2);

      ctx.shadowColor = 'rgba(0, 0, 0, 0.5)';
      ctx.shadowBlur = 2;
      ctx.shadowOffsetX = 1;
      ctx.shadowOffsetY = 1;

      ctx.fillStyle = '#FFFFFF';
      
      // Уменьшен размер шрифта в 2 раза
      const fontSize = dpr >= 2 ? 10 : 9;
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
    
    // Выбираем сектор: принудительно, по вероятностям или случайно
    let targetSection;
    if (normalizedForceIndex !== null) {
      targetSection = normalizedForceIndex;
    } else if (useProbabilities && this.sections.length > 0) {
      // Используем вероятностную логику
      targetSection = this.selectSectionByProbability();
    } else {
      // Случайный выбор
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

  /**
   * Выбирает сектор на основе вероятностей
   */
  selectSectionByProbability() {
    // Собираем вероятности и создаем взвешенный массив
    const weights = this.sections.map((section, index) => {
      const probability = parseFloat(section.probability) || 0;
      return { index, weight: probability };
    });

    // Вычисляем общую сумму весов
    const totalWeight = weights.reduce((sum, item) => sum + item.weight, 0);

    // Если сумма весов равна 0 или нет весов, используем равномерное распределение
    if (totalWeight === 0 || weights.length === 0) {
      return Math.floor(Math.random() * this.sectionCount);
    }

    // Генерируем случайное число от 0 до totalWeight
    let random = Math.random() * totalWeight;
    
    // Находим сектор, соответствующий случайному числу
    for (const item of weights) {
      random -= item.weight;
      if (random <= 0) {
        return item.index;
      }
    }

    // Fallback на последний сектор (на случай ошибок округления)
    return weights[weights.length - 1].index;
  }
}
</script>

<style scoped>
.fortune-wheel-container {
  position: relative;
  width: 100%;
  max-width: 375px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  padding: 16px 16px calc(var(--footer-height, 70px) + 16px);
  box-sizing: border-box;
  overflow-y: auto;
  font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: linear-gradient(180deg, #F8A575 0%, #FDB083 100%);
}

/* Timer Block */
.timer-block {
  position: relative;
  width: 100%;
  padding: 0 16px;
  margin-top: 20px;
  z-index: 10;
}

.timer-content {
  width: 247px;
  height: 49px;
  margin: 0 auto;
  background: #BBBBBB;
  backdrop-filter: blur(10px);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;

  font-family: 'NEXT ART', 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-weight: 700;
}

.timer-text {
  font-size: 18px;
  font-weight: 500;
  line-height: 21px;
  text-align: center;
  color: #FFFFFF;
}

/* Tickets Info Block */
.tickets-block {
  position: relative;
  width: 100%;
  padding: 0 16px;
  margin-top: 20px;
  margin-bottom: 20px;
  z-index: 10;
}

.tickets-content {
  width: 247px;
  height: 49px;
  margin: 0 auto;
  background: linear-gradient(135deg, #E77D65 0%, #EFB66C 100%);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0px 2px 8px rgba(231, 125, 101, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.tickets-text {
  font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-size: 16px;
  font-weight: 500;
  line-height: 21px;
  text-align: center;
  color: #FFFFFF;
  display: flex;
  align-items: center;
  gap: 4px;
}

.tickets-label {
  font-size: 16px;
  font-weight: 500;
}

.tickets-count {
  font-size: 24px;
  font-weight: 700;
  line-height: 1;
}

.tickets-word {
  font-size: 16px;
  font-weight: 500;
}

/* Wheel Container */
.wheel-container {
  width: 100%;
  flex: 0 0 auto;
  aspect-ratio: 1 / 1;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  padding: 20px 15px;
  box-sizing: border-box;
}

.wheel-wrapper {
  position: relative;
  width: min(287px, 75vw);
  max-width: 287px;
  aspect-ratio: 1 / 1;
  height: auto;
  max-height: 287px;
  margin: 0 auto; /* Центрирование */
  display: flex;
  align-items: center;
  justify-content: center;
}

.wheel-canvas {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  filter: drop-shadow(0px 4px 4px rgba(0, 0, 0, 0.25));
  will-change: transform;
  transform-origin: center center;
  /* Улучшение качества рендеринга изображений на Retina дисплеях */
  image-rendering: -webkit-optimize-contrast;
  image-rendering: crisp-edges;
  image-rendering: high-quality;
  /* Для лучшего качества на Retina */
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.wheel-center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 60px;
  height: 60px;
  background: linear-gradient(180deg, #F8A575 0%, #FDB083 100%);
  border-radius: 50%;
  border: 4px solid #FFFFFF;
  z-index: 5;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.wheel-pointer {
  position: absolute;
  top: -15px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 15px solid transparent;
  border-right: 15px solid transparent;
  border-top: 25px solid #FFFFFF;
  z-index: 6;
  filter: drop-shadow(0px 2px 4px rgba(0, 0, 0, 0.3));
}

/* Spin Button */
.spin-button {
  width: calc(100% - 32px);
  max-width: 343px;
  height: 58px;
  margin: 0 auto 20px;
  background: linear-gradient(8.92deg, #E77D65 23.82%, #EFB66C 192.33%);
  box-shadow: 0px 4px 4px rgba(255, 212, 137, 0.25);
  border: none;
  border-radius: 10px;
  cursor: pointer;
  transition: transform 0.2s, opacity 0.2s, background 0.2s;
  z-index: 10;
}

.spin-button:active:not(:disabled) {
  transform: scale(0.98);
}

.spin-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: #BBBBBB;
  box-shadow: none;
}

.spin-button span {
  font-size: 20px;
  font-weight: 500;
  line-height: 24px;
  color: #FFFFFF;
}

@media (max-height: 700px) {
  .wheel-container {
    padding: 10px 15px;
  }

  .timer-block {
    margin-top: 10px;
  }

  .spin-button {
    margin-bottom: 10px;
  }
}

@media (max-width: 375px) {
  .fortune-wheel-container {
    max-width: 100%;
  }
}
</style>

