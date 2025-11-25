<template>
  <div class="frand-screen">

    <div class="content">
      <FortuneHeader
        button-label="Пригласить друга"
        @action="shareInvite"
      />

      <section class="intro">
        <p class="intro-title">приглашай друзей</p>
        <p class="intro-subtitle">получай больше подарков</p>
      </section>

<!--      <section class="hero">-->
<!--        <div class="hero-circle"></div>-->
<!--        <div class="hero-glow"></div>-->
<!--        <img-->
<!--          class="hero-main"-->
<!--          :src="asset('figma/frand/image 317.png')"-->
<!--          alt="Приглашение друзей"-->
<!--          draggable="false"-->
<!--        />-->
<!--        <img-->
<!--          class="hero-decor hero-decor&#45;&#45;left"-->
<!--          :src="asset('figma/frand/Group 2172.png')"-->
<!--          alt="Декор"-->
<!--          draggable="false"-->
<!--        />-->
<!--        <img-->
<!--          class="hero-decor hero-decor&#45;&#45;right"-->
<!--          :src="asset('figma/frand/Group 2173.png')"-->
<!--          alt="Декор"-->
<!--          draggable="false"-->
<!--        />-->
<!--      </section>-->

      <section class="steps">
        <article
          class="step-card"
          v-for="(step, index) in steps"
          :key="step.title"
          :style="{ animationDelay: `${index * 0.15}s` }"
        >
          <div class="step-icon">
            <img :src="asset(step.icon)" :alt="step.title" draggable="false" />
          </div>
          <div class="step-body">
            <p class="step-title">{{ step.title }}</p>
            <p class="step-text">{{ step.text }}</p>
          </div>
        </article>
      </section>

      <section class="invite">
        <button class="invite-btn" @click="shareInvite">
          <span>Пригласить друга</span>
          <span class="invite-arrow"></span>
        </button>
        <p class="invite-hint">{{ message }}</p>
      </section>

        <BottomNav
            :active-tab="activeTab"
            @change="setActiveTab"
        />
    </div>
  </div>
</template>

<script>
import BottomNav from '/resources/js/src/components/BottomNav.vue'
import FortuneHeader from '/resources/js/src/Pages/Fartune/components/FortuneHeader.vue'
import { mapGetters } from 'vuex'

export default {
  name: 'FrandPage',
  components: {
    BottomNav,
    FortuneHeader
  },
  props: {
    botLink: {
      type: String,
      default: 'https://t.me/fortuneWheelBot'
    },
    message: {
      type: String,
      default: 'Подключаем игру…'
    }
  },
  computed: {
    ...mapGetters(['telegramUserId'])
  },
  data() {
    return {
      activeTab: 'friends',
      botUsernameCache: null, // Кеш для username бота
      steps: [
        {
          title: 'Поделись ссылкой',
          text: 'Отправь приглашение друзьям, чтобы они присоединились.',
          icon: 'figma/frand/Group 2171.png'
        },
        {
          title: 'Они играют',
          text: 'Друзья выполняют задания и зарабатывают билеты.',
          icon: 'figma/frand/Group 2172.png'
        },
        {
          title: 'Ты получаешь билеты',
          text: 'За каждого активного друга начисляем бонусные билеты.',
          icon: 'figma/frand/Group 2173.png'
        },
        {
          title: 'Подарки от партнеров',
          text: 'Обменивай билеты на подарки и попадай в топ.',
          icon: 'figma/frand/Group 2175.png'
        }
      ]
    }
  },
  methods: {
    setActiveTab(tab) {
      if (this.activeTab === tab) {
        this.navigateByTab(tab)
        return
      }
      this.activeTab = tab
      this.navigateByTab(tab)
    },
    navigateByTab(tab) {
      const map = {
        wheel: '/start',
        friends: '/friends',
        top: '/top'
      }
      const target = map[tab]
      if (!target) return
      if (this.$router) {
        this.$router.push(target)
      } else if (typeof window !== 'undefined') {
        window.location.href = target
      }
    },
    navigateToHowTo() {
      const target = '/pages'
      if (this.$router) {
        this.$router.push(target)
      } else if (typeof window !== 'undefined') {
        window.location.href = target
      }
    },
    async shareInvite() {
      // Получаем ID текущего пользователя из store
      const inviterId = this.telegramUserId
      
      if (!inviterId) {
        // Попробуем получить из initDataUnsafe
        const telegram = window.Telegram && window.Telegram.WebApp
        if (telegram?.initDataUnsafe?.user?.id) {
          const userId = telegram.initDataUnsafe.user.id
          await this.openShareDialog(userId)
          return
        }
        
        // Если ID недоступен, открываем без параметра
        await this.openShareDialog(null)
        return
      }
      
      await this.openShareDialog(inviterId)
    },
    async getBotUsername() {
      // Пробуем получить username бота из различных источников
      const telegram = window.Telegram && window.Telegram.WebApp
      
      // 1. Из кеша компонента (если уже получали)
      if (this.botUsernameCache) {
        return this.botUsernameCache
      }
      
      // 2. Получаем с бэкенда через API (самый надежный способ)
      try {
        // Используем глобальный axios из window.axios (уже подключен в bootstrap.js)
        const axiosInstance = window.axios || (await import('axios')).default
        const response = await axiosInstance.get('/api/v1/bot/username')
        
        // Проверяем ответ более тщательно
        if (response && response.data) {
          let username = response.data.username
          
          // Если username есть (может быть null, undefined, или пустая строка)
          if (username) {
            // Очищаем username от @ и специальных символов
            username = String(username).replace(/^@/, '').replace(/[^a-zA-Z0-9_]/g, '').trim()
            
            // Если после очистки username не пустой
            if (username) {
              // Кешируем результат в data компонента вместо $options
              if (!this.botUsernameCache) {
                this.botUsernameCache = username
              }
              return username
            }
          }
        }
      } catch (error) {
        // Если API недоступен, пробуем другие способы
        // Игнорируем ошибку, пробуем fallback методы
      }
      
      // 3. Из query параметров URL (если передано при открытии mini app)
      try {
        const urlParams = new URLSearchParams(window.location.search)
        const botParam = urlParams.get('bot') || urlParams.get('bot_username') || urlParams.get('botname')
        if (botParam) {
          const username = botParam.replace(/^@/, '').replace(/[^a-zA-Z0-9_]/g, '')
          this.botUsernameCache = username
          return username
        }
      } catch (e) {
        // Игнорируем ошибки
      }
      
      // 4. Из URL - проверяем window.location для имени бота
      try {
        const url = window.location.href
        // Если mini app открыта через t.me/botname/app или похожий формат
        const botMatch = url.match(/t\.me\/([a-zA-Z0-9_]+)/i)
        if (botMatch && botMatch[1]) {
          this.botUsernameCache = botMatch[1]
          return botMatch[1]
        }
        
        // Проверяем реферер
        if (document.referrer) {
          const refBotMatch = document.referrer.match(/t\.me\/([a-zA-Z0-9_]+)/i)
          if (refBotMatch && refBotMatch[1]) {
            this.botUsernameCache = refBotMatch[1]
            return refBotMatch[1]
          }
        }
      } catch (e) {
        // Игнорируем ошибки
      }
      
      // 5. Из пропсов (если передан botLink И он не дефолтный)
      // Пропускаем дефолтный botLink, так как он может быть неправильным
      if (this.botLink && !this.botLink.includes('fortuneWheelBot')) {
        try {
          const url = new URL(this.botLink)
          const pathMatch = url.pathname.match(/\/([a-zA-Z0-9_]+)/)
          if (pathMatch && pathMatch[1]) {
            this.botUsernameCache = pathMatch[1]
            return pathMatch[1]
          }
          // Если это t.me/botname
          if (url.hostname === 't.me' && url.pathname.startsWith('/')) {
            const username = url.pathname.substring(1).replace(/[^a-zA-Z0-9_]/g, '')
            this.botUsernameCache = username
            return username
          }
        } catch (e) {
          // Если botLink не валидный URL, пытаемся извлечь имя из строки
          const match = this.botLink.match(/@?([a-zA-Z0-9_]+)/)
          if (match && match[1]) {
            this.botUsernameCache = match[1]
            return match[1]
          }
        }
      }
      
      // 6. Fallback - дефолтное значение (должно использоваться только если все методы не сработали)
      // ВАЖНО: Это значение используется только если API не вернул username
      // Проверьте, что в .env установлено TELEGRAM_BOT_USERNAME=sitesaccessbot
      // И что API endpoint /api/v1/bot/username работает
      return 'FortuneWheelBot'
    },
    async openShareDialog(userId) {
      const telegram = window.Telegram && window.Telegram.WebApp
      
      // Получаем username текущего бота, где запущена mini app
      const botUsername = await this.getBotUsername()
      
      // Формируем ссылку на бота с параметром start
      let botUrl = `https://t.me/${botUsername}`
      if (userId) {
        botUrl = `${botUrl}?start=${userId}`
      }
      
      const shareText = 'Присоединяйся и получай бонусы вместе со мной!'
      
      // Для Telegram Mini App используем прямой формат поделиться
      // Формат: https://t.me/share/url?url=ENCODED_URL&text=ENCODED_TEXT
      const shareDialogUrl = `https://t.me/share/url?url=${encodeURIComponent(botUrl)}&text=${encodeURIComponent(shareText)}`
      
      // Используем Telegram WebApp API для открытия диалога поделиться
      if (telegram && telegram.openTelegramLink) {
        try {
          // Открываем диалог поделиться в Telegram - откроет список контактов
          telegram.openTelegramLink(shareDialogUrl)
          this.$emit('invite', { shareUrl: botUrl, userId })
          return
        } catch (error) {
          // Показываем ошибку пользователю
          if (telegram.showAlert) {
            telegram.showAlert('Не удалось открыть диалог поделиться. Попробуйте еще раз.')
          } else {
            alert('Не удалось открыть диалог поделиться. Попробуйте еще раз.')
          }
        }
      }
      
      // Если Telegram WebApp API недоступен, открываем диалог поделиться напрямую
      try {
        window.open(shareDialogUrl, '_blank')
        this.$emit('invite', { shareUrl: botUrl, userId })
      } catch (error) {
        // Показываем ошибку пользователю
        const telegram = window.Telegram && window.Telegram.WebApp
        if (telegram && telegram.showAlert) {
          telegram.showAlert('Не удалось открыть ссылку для поделиться. Попробуйте еще раз.')
        } else {
          alert('Не удалось открыть ссылку для поделиться. Попробуйте еще раз.')
        }
      }
    },
    async copyToClipboard(text) {
      try {
        // Пробуем использовать современный Clipboard API
        if (navigator.clipboard && navigator.clipboard.writeText) {
          await navigator.clipboard.writeText(text)
          
          // Показываем уведомление пользователю
          const telegram = window.Telegram && window.Telegram.WebApp
          if (telegram && telegram.showAlert) {
            telegram.showAlert('Ссылка скопирована в буфер обмена! Отправь её друзьям.')
          } else {
            alert('Ссылка скопирована в буфер обмена! Отправь её друзьям.')
          }
          
          console.log('Link copied to clipboard:', text)
          this.$emit('invite', { shareUrl: text, userId: null })
          return
        }
        
        // Fallback для старых браузеров
        const textArea = document.createElement('textarea')
        textArea.value = text
        textArea.style.position = 'fixed'
        textArea.style.left = '-999999px'
        textArea.style.top = '-999999px'
        document.body.appendChild(textArea)
        textArea.focus()
        textArea.select()
        
        try {
          document.execCommand('copy')
          const telegram = window.Telegram && window.Telegram.WebApp
          if (telegram && telegram.showAlert) {
            telegram.showAlert('Ссылка скопирована в буфер обмена! Отправь её друзьям.')
          } else {
            alert('Ссылка скопирована в буфер обмена! Отправь её друзьям.')
          }
          console.log('Link copied to clipboard (fallback):', text)
          this.$emit('invite', { shareUrl: text, userId: null })
        } catch (err) {
          console.error('Failed to copy link:', err)
          // Последний fallback: открываем ссылку
          window.open(text, '_blank')
          this.$emit('invite', { shareUrl: text, userId: null })
        } finally {
          document.body.removeChild(textArea)
        }
      } catch (error) {
        console.error('Failed to copy link:', error)
        // Последний fallback: открываем ссылку в новой вкладке
        window.open(text, '_blank')
        this.$emit('invite', { shareUrl: text, userId: null })
      }
    }
  }
}
</script>

<style scoped>
.frand-screen {
  position: relative;
  width: 100%;
  max-width: 375px;
  height: 100%;
  margin: 0 auto;
  padding: 0 16px;
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
  padding: 120px 0 calc(var(--footer-height, 70px) + 90px);
  display: flex;
  flex-direction: column;
  gap: 16px;
  z-index: 1;
  overflow-y: auto;
}

.invite-arrow {
  display: inline-block;
  width: 12px;
  height: 12px;
  border-top: 2px solid #fff;
  border-right: 2px solid #fff;
  transform: rotate(45deg);
}

.intro {
  text-align: center;
  text-transform: uppercase;
  color: #fff;
  letter-spacing: 0.06em;
}

.intro-title {
  font-size: 26px;
  font-weight: 700;
}

.intro-subtitle {
  margin-top: 6px;
  font-size: 16px;
  font-weight: 600;
  opacity: 0.9;
}

.hero {
  position: relative;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 10px 0 24px;
}

.hero-circle {
  position: absolute;
  width: 500px;
  height: 500px;
  border-radius: 50%;
  background: #fcad7f;
  opacity: 0.35;
  filter: blur(10px);
}

.hero-glow {
  position: absolute;
  width: 420px;
  height: 420px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.4) 0%, rgba(255, 185, 126, 0.3) 55%, transparent 80%);
  animation: breathe 6s ease-in-out infinite;
}

.hero-main {
  width: min(320px, 95%);
  object-fit: contain;
  filter: drop-shadow(0 14px 30px rgba(0, 0, 0, 0.35));
  animation: float 4.6s ease-in-out infinite;
  pointer-events: none;
}

.hero-decor {
  position: absolute;
  width: 140px;
  pointer-events: none;
  animation: float 5s ease-in-out infinite;
}

.hero-decor--left {
  left: -10px;
  bottom: 140px;
  animation-delay: 0.3s;
}

.hero-decor--right {
  right: -10px;
  top: 120px;
  animation-delay: 0.8s;
}

.steps {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.step-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px;
  position: relative;
  overflow: hidden;
  border-radius: 18px;
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.22) 0%, rgba(255, 255, 255, 0.08) 100%);
  border: 1px solid rgba(255, 255, 255, 0.35);
  color: #fff;
  box-shadow:
    0 14px 32px rgba(226, 113, 83, 0.35),
    inset 0 1px 0 rgba(255, 255, 255, 0.3);
  backdrop-filter: blur(10px);
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
  animation: popIn 0.6s ease forwards;
}

.step-card::before {
  content: '';
  position: absolute;
  inset: 1px;
  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  pointer-events: none;
}

.step-card:hover {
  transform: translateY(-4px);
  box-shadow:
    0 18px 34px rgba(226, 113, 83, 0.45),
    inset 0 1px 0 rgba(255, 255, 255, 0.45);
  border-color: rgba(255, 255, 255, 0.55);
}

.step-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(145deg, rgba(231, 125, 101, 0.95), rgba(255, 189, 134, 0.9));
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 6px 16px rgba(231, 125, 101, 0.45);
}

.step-icon img {
  width: 32px;
  height: 32px;
  object-fit: contain;
}

.step-body {
  display: flex;
  flex-direction: column;
  gap: 4px;
  width: 100%;
}

.step-title {
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
}

.step-text {
  font-size: 12px;
  line-height: 1.3;
  opacity: 0.9;
  width: 100%;
}

.invite {
  display: flex;
  flex-direction: column;
  gap: 10px;
  align-items: center;
}

.invite-btn {
  width: 100%;
  height: 58px;
  border: none;
  border-radius: 10px;
  background: linear-gradient(9deg, #e77d65 0%, #efb66c 100%);
  color: #fff;
  font-size: 20px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  cursor: pointer;
  box-shadow: 0 8px 20px rgba(231, 125, 101, 0.35);
  transition: transform 0.2s ease;
}

.invite-btn:active {
  transform: scale(0.97);
}

.invite-hint {
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

@keyframes breathe {
  0%, 100% {
    transform: scale(0.95);
    opacity: 0.5;
  }
  50% {
    transform: scale(1.05);
    opacity: 0.9;
  }
}

@keyframes popIn {
  from {
    opacity: 0;
    transform: translateY(12px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@media (max-height: 720px) {
  .content {
    padding-top: 16px;
    gap: 12px;
  }

  .hero {
    padding-bottom: 12px;
  }
}
</style>

