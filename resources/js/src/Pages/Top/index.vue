<template>
  <div class="top-screen">

    <div class="content">
      <FortuneHeader
        button-label="Пригласить друга"
        @action="shareInvite"
      />

      <section class="hero-card">
        <div class="hero-info">
<!--          <div class="hero-heading">-->
<!--            <img :src="asset('figma/top/image 329.png')" alt="Кубок" draggable="false" />-->
<!--            <img :src="asset('figma/top/image 327.png')" alt="Трофей" draggable="false" />-->
<!--            <img :src="asset('figma/top/image 328.png')" alt="Приз" draggable="false" />-->
<!--          </div>-->
          <p class="hero-title">ТОП игроки</p>
          <p class="hero-subtitle">Подарочной картой каждый месяц</p>
          <div class="hero-badge">
            <span>получай 5000 ₽</span>
          </div>
        </div>
        <img
          class="hero-illustration"
          :src="asset('figma/top/image 325.png')"
          alt="Игрок"
          draggable="false"
        />
      </section>

      <section class="leaderboard">
        <div class="leaderboard-head">
          <img :src="asset('figma/top/ТОП ИГРОКОВ.png')" alt="ТОП ИГРОКОВ" draggable="false" />
          <img :src="asset('figma/top/подарочной картой каждый месяц.png')" alt="подарочной картой каждый месяц" draggable="false" />
        </div>
        <ul class="leaderboard-list">
          <li
            v-for="(player, index) in players"
            :key="player.id"
            class="leaderboard-item"
            :style="{ animationDelay: `${index * 0.1}s` }"
          >
            <div class="rank-badge">
              <span>{{ player.rank }}</span>
            </div>
            <div class="player-avatar">
              <img :src="asset(player.avatar)" :alt="player.name" draggable="false" />
            </div>
            <div class="player-info">
              <p class="player-name">{{ player.name }}</p>
              <p class="player-prize">{{ player.prize }}</p>
            </div>
            <button class="player-action">
              <span>{{ player.amount }}</span>
              <span class="btn-arrow sm"></span>
            </button>
          </li>
        </ul>
      </section>

      <section class="cta">
        <button class="cta-btn" @click="shareInvite">
          <span>Пригласить друга</span>
          <span class="btn-arrow"></span>
        </button>
        <p class="hint">{{ message }}</p>
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

export default {
  name: 'TopPage',
  components: {
    BottomNav,
    FortuneHeader
  },
  props: {
    message: {
      type: String,
      default: 'Подключаем игру…'
    },
    botLink: {
      type: String,
      default: 'https://t.me/fortuneWheelBot'
    }
  },
  computed: {
    telegramUserId() {
      return this.$store.getters.telegramUserId || null
    }
  },
  data() {
    return {
      activeTab: 'top',
      botUsernameCache: null, // Кеш для username бота
      players: [
        {
          id: 1,
          rank: 1,
          name: 'Игрок 1',
          prize: '5000 ₽',
          amount: '5000 ₽',
          avatar: 'figma/top/Group 2180.png'
        },
        {
          id: 2,
          rank: 2,
          name: 'Игрок 2',
          prize: '3000 ₽',
          amount: '3000 ₽',
          avatar: 'figma/top/Group 2181.png'
        },
        {
          id: 3,
          rank: 3,
          name: 'Игрок 3',
          prize: '1500 ₽',
          amount: '1500 ₽',
          avatar: 'figma/top/Group 2182.png'
        },
        {
          id: 4,
          rank: 4,
          name: 'Игрок 4',
          prize: '1000 ₽',
          amount: '1000 ₽',
          avatar: 'figma/top/Group 2183.png'
        },
        {
          id: 5,
          rank: 5,
          name: 'Игрок 5',
          prize: '800 ₽',
          amount: '800 ₽',
          avatar: 'figma/top/Group 2184.png'
        },
        {
          id: 6,
          rank: 6,
          name: 'Игрок 6',
          prize: '600 ₽',
          amount: '600 ₽',
          avatar: 'figma/top/Group 2185.png'
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
.top-screen {
  position: relative;
  width: 100%;
  max-width: 375px;
  height: 100%;
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
  padding: 120px 0 calc(var(--footer-height, 70px) + 90px);
  display: flex;
  flex-direction: column;
  gap: 16px;
  z-index: 1;
  overflow-y: auto;
}

.btn-arrow {
  width: 14px;
  height: 14px;
  border-top: 2px solid currentColor;
  border-right: 2px solid currentColor;
  transform: rotate(45deg);
  display: inline-block;
}

.btn-arrow.sm {
  width: 10px;
  height: 10px;
}

.hero-card {
  width: 100%;
  border-radius: 16px;
  background: rgba(231, 125, 101, 0.95);
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 10px 25px rgba(226, 113, 83, 0.35);
  padding: 18px 18px 0;
  display: flex;
  justify-content: space-between;
  gap: 8px;
  color: #fff;
  overflow: hidden;
}

.hero-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
  animation: fadeSlide 0.8s ease forwards;
}

.hero-heading {
  display: flex;
  gap: 8px;
  align-items: center;
}

.hero-heading img {
  width: 40px;
  height: 40px;
  object-fit: contain;
  animation: float 4s ease-in-out infinite;
}

.hero-title {
  font-size: 28px;
  font-weight: 700;
  text-transform: uppercase;
}

.hero-subtitle {
  font-size: 14px;
  line-height: 1.3;
  opacity: 0.9;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  padding: 8px 14px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.2);
  font-weight: 700;
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

.hero-illustration {
  width: 150px;
  object-fit: contain;
  animation: float 4.6s ease-in-out infinite;
  filter: drop-shadow(0 12px 24px rgba(0, 0, 0, 0.3));
  pointer-events: none;
}

.leaderboard {
  width: 100%;
  border-radius: 16px;
  padding: 18px 16px 12px;
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.25);
  box-shadow: 0 12px 30px rgba(226, 113, 83, 0.35);
  backdrop-filter: blur(8px);
}

.leaderboard-head {
  text-align: center;
  margin-bottom: 12px;
}

.leaderboard-head img {
  display: block;
  width: 100%;
  max-width: 220px;
  margin: 0 auto 4px;
  object-fit: contain;
}

.leaderboard-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.leaderboard-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #fff;
  animation: popIn 0.6s ease forwards;
}

.rank-badge {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(120deg, #fcad7f 0%, #e5867a 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 16px;
}

.player-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.player-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.player-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.player-name {
  font-size: 14px;
  font-weight: 600;
}

.player-prize {
  font-size: 12px;
  opacity: 0.8;
}

.player-action {
  border: none;
  border-radius: 8px;
  padding: 6px 12px;
  background: linear-gradient(120deg, #fcad7f 0%, #e5867a 100%);
  color: #fff;
  font-weight: 700;
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(255, 212, 137, 0.35);
}

.cta {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: center;
}

.cta-btn {
  width: 100%;
  height: 58px;
  border-radius: 10px;
  border: none;
  background: linear-gradient(9deg, #e77d65 0%, #efb66c 100%);
  color: #fff;
  font-size: 20px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  cursor: pointer;
  box-shadow: 0 8px 18px rgba(231, 125, 101, 0.35);
}

.hint {
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
    transform: translateY(-10px);
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

@keyframes popIn {
  0% {
    opacity: 0;
    transform: translateY(12px) scale(0.96);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@media (max-height: 720px) {
  .content {
    padding-top: 16px;
    gap: 12px;
  }

  .hero-card {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
}
</style>

