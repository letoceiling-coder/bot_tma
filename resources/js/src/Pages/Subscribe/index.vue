<template>
  <div class="subscribe-screen">
    <div class="content">
      <section class="intro">
        <h1 class="title">Подпишись на каналы</h1>
        <p class="subtitle">Для использования приложения необходимо подписаться на все каналы</p>
      </section>

      <section class="channels-list">
        <div
          v-for="(channel, index) in channels"
          :key="channel.id"
          class="channel-card"
          :class="{ 'channel-card--subscribed': channel.subscribed }"
          :style="{ animationDelay: `${index * 0.1}s` }"
        >
          <div class="channel-info">
            <div class="channel-avatar">
              <img
                v-if="channel.avatar"
                :src="channel.avatar"
                :alt="channel.title"
                draggable="false"
              />
              <div v-else class="channel-avatar-placeholder">
                {{ channel.title.charAt(0).toUpperCase() }}
              </div>
            </div>
            <div class="channel-details">
              <h3 class="channel-title">{{ channel.title }}</h3>
              <p v-if="channel.description" class="channel-description">
                {{ channel.description }}
              </p>
            </div>
          </div>
          <button
            v-if="!channel.subscribed"
            class="subscribe-btn"
            @click="handleSubscribe(channel)"
            :disabled="checkingSubscription"
          >
            Подписаться
          </button>
          <div v-else class="subscribed-badge">
            <span>✓</span>
          </div>
        </div>
      </section>

      <section class="cta-section">
        <button
          class="continue-btn"
          :disabled="!allSubscribed || checkingSubscription"
          @click="handleContinue"
        >
          <span>Продолжить</span>
          <span class="btn-arrow">→</span>
        </button>
        <p v-if="!allSubscribed" class="hint">
          Подпишитесь на все каналы, чтобы продолжить
        </p>
      </section>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'SubscribePage',
  props: {
    channelsConfig: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      checkingSubscription: false,
      channels: []
    }
  },
  computed: {
    allSubscribed() {
      return this.channels.every(channel => channel.subscribed)
    }
  },
  async mounted() {
    // Загружаем каналы и сразу проверяем подписки
    await this.initChannels()
    
    // Если каналы загружены, автоматически проверяем подписки
    if (this.channels.length > 0) {
      await this.checkSubscriptions()
    } else {
      console.warn('No channels to check subscriptions for')
    }
  },
  methods: {
    async initChannels() {
      try {
        // Загружаем каналы из API
        const { data } = await axios.get('/api/v1/subscriptions/channels')
        
        if (data.success && data.data && data.data.length > 0) {
          this.channels = data.data.map(ch => ({
            ...ch,
            subscribed: false
          }))
        } else if (this.channelsConfig && this.channelsConfig.length > 0) {
          // Fallback: используем каналы из пропсов
          this.channels = this.channelsConfig.map(ch => ({
            ...ch,
            subscribed: false
          }))
        } else {
          // Дефолтные каналы (только для разработки)
          console.warn('No channels found in API or props, using defaults')
          this.channels = []
        }
      } catch (error) {
        console.error('Error loading channels:', error)
        // Используем пропсы или пустой массив
        if (this.channelsConfig && this.channelsConfig.length > 0) {
          this.channels = this.channelsConfig.map(ch => ({
            ...ch,
            subscribed: false
          }))
        } else {
          this.channels = []
        }
      }
    },
    
    async checkSubscriptions() {
      this.checkingSubscription = true
      
      try {
        const telegram = window.Telegram && window.Telegram.WebApp
        
        if (!telegram) {
          console.warn('Telegram WebApp not available')
          // В режиме разработки можно автоматически отметить как подписанные
          if (process.env.NODE_ENV === 'development') {
            this.channels.forEach(ch => {
              ch.subscribed = true
            })
          }
          return
        }

        // Сначала пробуем использовать Telegram WebApp API для проверки подписок
        // Это проверка от имени пользователя через клиентский API
        console.log('📋 Starting subscription check...')
        
        // Проверяем доступность WebApp API
        const webAppApiAvailable = typeof telegram.checkSubscription === 'function'
        
        console.log(`🔍 WebApp API available: ${webAppApiAvailable}`)
        
        let webAppCheckResult = null
        
        if (webAppApiAvailable) {
          // Пробуем использовать WebApp API
          webAppCheckResult = await this.checkSubscriptionsViaWebApp(telegram)
        } else {
          console.log('⚠️ WebApp.checkSubscription method not available, will use backend API directly')
        }
        
        // Если WebApp API вернул результаты для всех каналов, используем их
        if (webAppCheckResult && webAppCheckResult.length > 0 && webAppCheckResult.length === this.channels.length) {
          console.log('✅ Subscription check via WebApp API successful for all channels')
          this.channels.forEach(channel => {
            const webAppChannel = webAppCheckResult.find(wch => wch.id === channel.id)
            if (webAppChannel !== undefined) {
              channel.subscribed = webAppChannel.subscribed
            }
          })
        } else {
          // Fallback: используем backend API через Bot API
          if (webAppApiAvailable) {
            console.log('📡 Using backend API (Bot API) for subscription check (WebApp API returned partial/no results)')
          } else {
            console.log('📡 Using backend API (Bot API) for subscription check (WebApp API not available)')
          }
          await this.checkSubscriptionsViaBackend(telegram, webAppApiAvailable) // Передаем информацию была ли попытка WebApp API
        }
        
      } catch (error) {
        console.error('Error checking subscriptions:', error)
        if (error.response) {
          console.error('Response error:', {
            status: error.response.status,
            data: error.response.data,
            message: error.response.data?.message || error.message
          })
        }
        // При ошибке показываем все как неподписанные для безопасности
      } finally {
        this.checkingSubscription = false
      }
    },

    /**
     * Проверка подписок через Telegram WebApp API (клиентская проверка от имени пользователя)
     */
    async checkSubscriptionsViaWebApp(telegram) {
      try {
        console.log('🔍 Attempting WebApp API subscription check...')
        
        // Проверяем доступность метода checkSubscription
        if (typeof telegram.checkSubscription !== 'function') {
          console.warn('⚠️ WebApp.checkSubscription method not available')
          console.log('ℹ️ Will use backend API (Bot API) as fallback')
          return null
        }

        console.log('✅ WebApp.checkSubscription method is available')
        const results = []
        
        // Проверяем каждый канал через WebApp API
        for (const channel of this.channels) {
          try {
            // Получаем chat_id канала
            // Пробуем использовать telegram_chat_id если указан, иначе username
            let channelId = channel.telegram_chat_id || channel.username
            
            if (!channelId) {
              console.warn(`⚠️ No channel ID for ${channel.title}`)
              continue
            }
            
            // checkSubscription принимает username без @ или числовой chat_id
            // Убираем @ если есть (метод принимает username без @)
            const originalChannelId = channelId
            if (typeof channelId === 'string') {
              channelId = channelId.replace(/^@/, '')
            }
            
            console.log(`🔍 Checking subscription via WebApp for ${channel.title} (channelId: ${channelId})`)
            
            // Используем WebApp API для проверки подписки
            // checkSubscription возвращает Promise<boolean> или boolean
            let subscribed = false
            
            try {
              const result = telegram.checkSubscription(channelId)
              
              // Результат может быть Promise или boolean
              if (result instanceof Promise) {
                subscribed = await result
              } else {
                subscribed = !!result
              }
              
              console.log(`✅ WebApp subscription check for ${channel.title}: ${subscribed}`)
            } catch (error) {
              console.warn(`❌ WebApp API error for ${channel.title}:`, error.message || error)
              // Если метод не поддерживается или произошла ошибка,
              // пробрасываем исключение для fallback на backend
              throw error
            }
            
            results.push({
              id: channel.id,
              subscribed: subscribed
            })
          } catch (error) {
            console.warn(`⚠️ Failed to check subscription via WebApp for ${channel.title}:`, error.message || error)
            // Если WebApp API не работает для этого канала, пропускаем его
            // и будем проверять через backend
          }
        }
        
        // Если хотя бы один канал проверили успешно, возвращаем результаты
        if (results.length > 0) {
          console.log(`✅ WebApp API check completed: ${results.length} channels checked`)
          return results
        }
        
        console.log('⚠️ WebApp API check returned no results, will use backend API')
        return null
      } catch (error) {
        console.warn('❌ WebApp subscription check failed:', error.message || error)
        console.log('ℹ️ Will use backend API (Bot API) as fallback')
        return null
      }
    },

    /**
     * Проверка подписок через Backend API (Bot API)
     * @param {Object} telegram - Telegram WebApp объект
     * @param {boolean} webAppApiAvailable - Была ли попытка использовать WebApp API
     */
    async checkSubscriptionsViaBackend(telegram, webAppApiAvailable = true) {
      // Получаем initData из Telegram WebApp
      const urlParams = new URLSearchParams(window.location.search)
      let initDataRaw = urlParams.get('tgWebAppData') || urlParams.get('_auth') || ''
      
      if (!initDataRaw && window.location.hash) {
        const hashParams = new URLSearchParams(window.location.hash.substring(1))
        initDataRaw = hashParams.get('tgWebAppData') || hashParams.get('_auth') || ''
      }
      
      if (!initDataRaw && typeof telegram.initData === 'string') {
        initDataRaw = telegram.initData
      }
      
      if (!initDataRaw && telegram?.initDataUnsafe?.user) {
        const user = telegram.initDataUnsafe.user
        const authDate = telegram.initDataUnsafe.auth_date || Math.floor(Date.now() / 1000)
        initDataRaw = `user=${encodeURIComponent(JSON.stringify(user))}&auth_date=${authDate}`
        console.warn('Using fallback initData from initDataUnsafe')
      }
      
      if (!initDataRaw) {
        console.error('initData not available. Make sure app is opened through Telegram Mini App')
        return
      }

      // Запрос к API для проверки подписок
      const { data } = await axios.get('/api/v1/subscriptions/check', {
        params: {
          initData: initDataRaw,
          force: true, // Принудительная проверка, игнорировать кеш
          source: webAppApiAvailable ? 'webapp_fallback' : 'backend_direct' // Указываем источник запроса
        }
      })

      console.log('Subscription check response (backend):', data)

      if (data.success && data.channels) {
        // Обновляем статус подписки для каждого канала
        data.channels.forEach(apiChannel => {
          const localChannel = this.channels.find(ch => ch.id === apiChannel.id)
          if (localChannel) {
            localChannel.subscribed = apiChannel.subscribed
          } else {
            this.channels.push({
              ...apiChannel,
              subscribed: apiChannel.subscribed
            })
          }
        })
        
        console.log('Updated channels subscription status:', this.channels.map(ch => ({
          title: ch.title,
          subscribed: ch.subscribed
        })))
      } else {
        console.warn('Subscription check failed:', data.message || 'Unknown error')
      }
    },

    async handleSubscribe(channel) {
      const telegram = window.Telegram && window.Telegram.WebApp
      
      if (telegram && telegram.openTelegramLink) {
        // Открываем канал в Telegram через WebApp API
        telegram.openTelegramLink(channel.url)
        
        // После возврата из канала проверяем подписку
        // Используем setInterval для периодической проверки
        const checkInterval = setInterval(async () => {
          const subscribed = await this.checkSubscriptionAfterReturn(channel)
          if (subscribed) {
            clearInterval(checkInterval)
          }
        }, 2000)
        
        // Останавливаем проверку через 30 секунд
        setTimeout(() => {
          clearInterval(checkInterval)
        }, 30000)
      } else {
        // Fallback: открываем в новой вкладке
        window.open(channel.url, '_blank')
      }
    },

    async checkSubscriptionAfterReturn(channel) {
      try {
        const telegram = window.Telegram && window.Telegram.WebApp
        
        if (!telegram) {
          return false
        }

        // Получаем initData таким же способом как в checkSubscriptions
        const urlParams = new URLSearchParams(window.location.search)
        let initDataRaw = urlParams.get('tgWebAppData') || urlParams.get('_auth') || ''
        
        if (!initDataRaw && window.location.hash) {
          const hashParams = new URLSearchParams(window.location.hash.substring(1))
          initDataRaw = hashParams.get('tgWebAppData') || hashParams.get('_auth') || ''
        }
        
        if (!initDataRaw && typeof telegram.initData === 'string') {
          initDataRaw = telegram.initData
        }
        
        if (!initDataRaw && telegram?.initDataUnsafe?.user) {
          const user = telegram.initDataUnsafe.user
          const authDate = telegram.initDataUnsafe.auth_date || Math.floor(Date.now() / 1000)
          initDataRaw = `user=${encodeURIComponent(JSON.stringify(user))}&auth_date=${authDate}`
        }
        
        if (!initDataRaw) {
          console.warn('initData not available for subscription check')
          return false
        }

        // Очищаем кеш перед проверкой
        await axios.post('/api/v1/subscriptions/clear-cache', null, {
          params: {
            initData: initDataRaw
          }
        })

        // Проверяем подписки через API с принудительной проверкой
        const { data } = await axios.get('/api/v1/subscriptions/check', {
          params: {
            initData: initDataRaw,
            force: true // Игнорировать кеш при проверке после подписки
          }
        })

        if (data.success && data.channels) {
          const apiChannel = data.channels.find(ch => ch.id === channel.id)
          if (apiChannel && apiChannel.subscribed) {
            channel.subscribed = true
            return true
          }
        }
        
        return false
      } catch (error) {
        console.error('Error checking subscription after return:', error)
        return false
      }
    },

    handleContinue() {
      if (this.allSubscribed) {
        this.$emit('subscribed')
        // Переход на главную страницу
        if (this.$router) {
          this.$router.push('/start')
        } else {
          window.location.href = '/start'
        }
      }
    }
  }
}
</script>

<style scoped>
.subscribe-screen {
  position: relative;
  width: 100%;
  max-width: 375px;
  min-height: 100vh;
  margin: 0 auto;
  padding: 0 16px;
  background: linear-gradient(180deg, #f8a575 0%, #fdb083 100%);
  font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  overflow-y: auto;
}

.content {
  width: 100%;
  max-width: 343px;
  padding: 32px 0 calc(var(--footer-height, 70px) + 40px);
  display: flex;
  flex-direction: column;
  gap: 24px;
  z-index: 1;
}

.intro {
  text-align: center;
  color: #fff;
  animation: fadeSlide 0.6s ease forwards;
}

.title {
  font-size: 28px;
  font-weight: 700;
  line-height: 1.2;
  margin-bottom: 12px;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.subtitle {
  font-size: 16px;
  line-height: 1.4;
  opacity: 0.9;
  font-weight: 500;
}

.channels-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.channel-card {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 16px;
  padding: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  transition: all 0.3s ease;
  animation: popIn 0.5s ease forwards;
  opacity: 0;
}

.channel-card--subscribed {
  background: rgba(255, 255, 255, 0.25);
  border-color: rgba(255, 212, 137, 0.5);
}

.channel-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 0;
}

.channel-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  border: 2px solid rgba(255, 255, 255, 0.3);
  background: rgba(0, 0, 0, 0.2);
}

.channel-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.channel-avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(135deg, #e77d65 0%, #efb66c 100%);
}

.channel-details {
  flex: 1;
  min-width: 0;
}

.channel-title {
  font-size: 16px;
  font-weight: 600;
  color: #fff;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.channel-description {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.3;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.subscribe-btn {
  padding: 10px 20px;
  background: linear-gradient(8deg, #e77d65 0%, #efb66c 100%);
  border: none;
  border-radius: 10px;
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s ease, opacity 0.2s ease;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(231, 125, 101, 0.35);
}

.subscribe-btn:active {
  transform: scale(0.95);
}

.subscribe-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.subscribed-badge {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(76, 175, 80, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 2px solid rgba(255, 255, 255, 0.5);
}

.subscribed-badge span {
  color: #fff;
  font-size: 20px;
  font-weight: 700;
}

.cta-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-items: center;
  margin-top: 8px;
}

.continue-btn {
  width: 100%;
  height: 58px;
  border-radius: 12px;
  border: none;
  background: linear-gradient(9deg, #e77d65 0%, #efb66c 100%);
  color: #fff;
  font-size: 18px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  cursor: pointer;
  transition: transform 0.2s ease, opacity 0.2s ease;
  box-shadow: 0 8px 18px rgba(231, 125, 101, 0.35);
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.continue-btn:active:not(:disabled) {
  transform: scale(0.98);
}

.continue-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-arrow {
  font-size: 20px;
}

.hint {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.85);
  text-align: center;
  font-weight: 500;
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
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>

