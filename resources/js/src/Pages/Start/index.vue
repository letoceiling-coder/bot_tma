<template>
  <div class="start-container">
    <transition name="fade" mode="out-in">
      <LoadScreen v-if="isLoading" />
      <SubscribePage
        v-else-if="!subscriptionsChecked || !allSubscribed"
        @subscribed="handleSubscribed"
      />
      <div v-else class="start-content">
        <div class="start-view">
          <component
            :is="currentComponent"
            @spin-result="handleSpinResult"
          />
        </div>
      </div>
    </transition>

    <GiftPopUp
      :visible="showPopup"
      :title="popupTitle"
      :description="popupMessage"
      @close="togglePopup(false)"
      @confirm="handlePopupConfirm"
    />
  </div>
</template>

<script>
import LoadScreen from '../Load/index.vue'
import MainPage from '../Main/index.vue'
import PagesWrapper from '/resources/js/src/Pages/Pages/index.vue'
import FrandPage from '../Frand/index.vue'
import TopPage from '../Top/index.vue'
import GiftPopUp from '../PopUp/index.vue'
import SubscribePage from '../Subscribe/index.vue'

export default {
  name: 'StartPage',
  components: {
    LoadScreen,
    GiftPopUp,
    SubscribePage
  },
  data() {
    return {
      isLoading: true,
      activeView: 'main',
      showPopup: false,
      popupMessage: 'Секретный подарок уже почти у вас!',
      popupTitle: 'Секретный подарок от кролика',
      subscriptionsChecked: false,
      allSubscribed: false,
      tabs: [
        { id: 'main', label: 'Главная' },
        { id: 'pages', label: 'Онбординг' },
        { id: 'friends', label: 'Друзья' },
        { id: 'top', label: 'Топ' }
      ],
      preloadTimeout: null
    }
  },
  computed: {
    currentComponent() {
      const map = {
        main: MainPage,
        pages: PagesWrapper,
        friends: FrandPage,
        top: TopPage
      }
      return map[this.activeView] || MainPage
    }
  },
  async mounted() {
    // Ждем инициализации Telegram WebApp перед регистрацией пользователя
    await this.waitForTelegramWebApp()
    
    // Регистрируем или обновляем пользователя Telegram
    await this.registerTelegramUser()
    
    // Сразу проверяем подписки при запуске приложения
    await this.checkSubscriptions()
    
    // После проверки подписок продолжаем загрузку
    this.preloadTimeout = setTimeout(() => {
      this.isLoading = false
    }, 2000)
  },
  beforeUnmount() {
    if (this.preloadTimeout) {
      clearTimeout(this.preloadTimeout)
    }
  },
  methods: {
    async waitForTelegramWebApp() {
      // Ждем инициализации Telegram WebApp API
      return new Promise((resolve) => {
        if (window.Telegram && window.Telegram.WebApp) {
          const webApp = window.Telegram.WebApp
          
          // Если WebApp уже готов
          if (webApp.readyState === 'ready' || webApp.isReady) {
            webApp.ready()
            resolve()
            return
          }
          
          // Ждем события ready
          webApp.ready()
          
          // Проверяем через небольшую задержку
          setTimeout(() => {
            resolve()
          }, 500)
        } else {
          // Если Telegram API не доступен, продолжаем без ожидания
          resolve()
        }
      })
    },
    getInitData() {
      const telegram = window.Telegram && window.Telegram.WebApp
      
      if (!telegram) {
        return null
      }

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
      
      // Fallback: формируем из initDataUnsafe
      if (!initDataRaw && telegram?.initDataUnsafe?.user) {
        const user = telegram.initDataUnsafe.user
        const authDate = telegram.initDataUnsafe.auth_date || Math.floor(Date.now() / 1000)
        initDataRaw = `user=${encodeURIComponent(JSON.stringify(user))}&auth_date=${authDate}`
      }
      
      return initDataRaw || null
    },
    async registerTelegramUser() {
      try {
        const initData = this.getInitData()
        
        if (!initData) {
          console.log('No initData available for Telegram user registration')
          return
        }

        // Получаем параметр start из различных источников (для реферальной системы)
        // В Telegram Mini App параметр start может передаваться:
        // 1. Через Telegram WebApp API (tgWebAppStartParam / initDataUnsafe.start_param)
        // 2. Через URL (например, https://bot.siteaccess.ru?start={commandParameter})
        // 3. Через localStorage (мы сохраняем его при первом заходе в CorePage)
        let startParam = null
        
        // 1. Проверяем Telegram WebApp API (приоритетный способ)
        const telegram = window.Telegram && window.Telegram.WebApp
        
        // Логируем все доступные свойства Telegram WebApp для отладки
        if (telegram) {
          console.log('Telegram WebApp object:', {
            tgWebAppStartParam: telegram.tgWebAppStartParam,
            start_param: telegram.start_param,
            version: telegram.version,
            platform: telegram.platform,
            initData: telegram.initData ? 'present' : 'missing',
            initDataUnsafe: telegram.initDataUnsafe ? {
              user: telegram.initDataUnsafe.user ? 'present' : 'missing',
              start_param: telegram.initDataUnsafe.start_param,
              auth_date: telegram.initDataUnsafe.auth_date
            } : 'missing',
            readyState: telegram.readyState,
            isReady: telegram.isReady,
            isExpanded: telegram.isExpanded
          })
          
          // ПРАВИЛЬНЫЙ способ для Telegram Mini Apps - tgWebAppStartParam
          if (telegram.tgWebAppStartParam) {
            startParam = telegram.tgWebAppStartParam
            console.log('✅ Got start_param from Telegram.WebApp.tgWebAppStartParam:', startParam)
          }
          
          // Альтернативный способ через start_param (устаревший)
          if (!startParam && telegram.start_param) {
            startParam = telegram.start_param
            console.log('✅ Got start_param from Telegram.WebApp.start_param:', startParam)
          }
          
          // Через initDataUnsafe
          if (!startParam && telegram.initDataUnsafe && telegram.initDataUnsafe.start_param) {
            startParam = telegram.initDataUnsafe.start_param
            console.log('✅ Got start_param from Telegram.WebApp.initDataUnsafe.start_param:', startParam)
          }
        } else {
          console.warn('⚠️ Telegram WebApp API not available')
        }
        
        // 2. Проверяем URL параметры (fallback — актуально для inline кнопок с внешним URL)
        // Поддерживаем оба варианта: ref (кастомный) и start (стандартный)
        if (!startParam) {
          const urlParams = new URLSearchParams(window.location.search)
          startParam = urlParams.get('ref') || urlParams.get('start') || urlParams.get('startapp')
          if (startParam) {
            console.log('✅ Got referral param from URL query params:', {
              ref: urlParams.get('ref'),
              start: urlParams.get('start'),
              startapp: urlParams.get('startapp'),
              used: startParam
            })
          }
        }
        
        // 3. Проверяем hash параметры (если есть)
        if (!startParam && window.location.hash) {
          const hashParams = new URLSearchParams(window.location.hash.substring(1))
          startParam = hashParams.get('ref') || hashParams.get('start') || hashParams.get('startapp')
          if (startParam) {
            console.log('✅ Got referral param from URL hash params:', {
              ref: hashParams.get('ref'),
              start: hashParams.get('start'),
              startapp: hashParams.get('startapp'),
              used: startParam
            })
          }
        }

        // 4. Последний fallback — localStorage (сохраняется в CorePage при первом заходе)
        if (!startParam) {
          const storedStart = localStorage.getItem('telegram_start_param')
          if (storedStart) {
            startParam = storedStart
            console.log('Got start_param from localStorage:', startParam)
          }
        }
        
        // Логируем все доступные источники для отладки
        const urlParams = new URLSearchParams(window.location.search)
        const hashParams = window.location.hash ? new URLSearchParams(window.location.hash.substring(1)) : null
        console.log('Referral param extraction:', {
          telegram_tgWebAppStartParam: telegram?.tgWebAppStartParam,
          telegram_start_param: telegram?.start_param,
          telegram_initDataUnsafe_start_param: telegram?.initDataUnsafe?.start_param,
          url_query_ref: urlParams.get('ref'),
          url_query_start: urlParams.get('start'),
          url_query_startapp: urlParams.get('startapp'),
          url_hash_ref: hashParams?.get('ref'),
          url_hash_start: hashParams?.get('start'),
          url_hash_startapp: hashParams?.get('startapp'),
          localStorage_start: (typeof localStorage !== 'undefined') ? localStorage.getItem('telegram_start_param') : null,
          final_referral_param: startParam,
          final_referral_param_type: typeof startParam,
          final_referral_param_length: startParam ? startParam.length : 0
        })

        // Формируем параметры для запроса
        // Используем ref (приоритет) или start (fallback) для совместимости
        const referralParam = startParam || null
        
        // Логируем параметры перед отправкой
        console.log('📤 Sending request to /api/v1/telegram-users/start with params:', {
          initData_length: initData ? initData.length : 0,
          referral_param: referralParam,
          has_referral_param: !!referralParam,
          referral_param_type: typeof referralParam,
          referral_param_value: referralParam,
          referral_param_is_null: referralParam === null,
          referral_param_is_empty_string: referralParam === ''
        })
        
        // Если параметр referral есть, но не передается, предупреждаем
        if (startParam && !referralParam) {
          console.error('❌ ERROR: startParam exists but referralParam is missing!', {
            startParam,
            referralParam
          })
        }

        const axios = (await import('axios')).default
        
        // Формируем URL с параметрами для правильной передачи
        // Используем URLSearchParams для явного формирования query string
        const queryParams = new URLSearchParams()
        queryParams.append('initData', initData)
        
        // Передаем referral параметр как ref (приоритет) или start (fallback)
        // Но передаем его только если он существует и не пустой
        if (referralParam && referralParam !== null && referralParam !== '') {
          // Используем ref как основной параметр (как настроено в боте)
          queryParams.append('ref', String(referralParam))
          console.log('✅ Adding referral param (ref) to query string:', referralParam)
        } else {
          console.log('⚠️ Referral param is missing or empty, not adding to query string:', {
            referral_param: referralParam,
            is_null: referralParam === null,
            is_empty: referralParam === '',
            is_undefined: referralParam === undefined
          })
        }
        
        const queryString = queryParams.toString()
        const fullUrl = `/api/v1/telegram-users/start?${queryString}`
        
        console.log('📡 Sending POST request to:', fullUrl)
        console.log('📋 Query string:', queryString)
        console.log('🔍 Start param in query string:', queryParams.get('start'))
        console.log('🔍 All query params keys:', Array.from(queryParams.keys()))
        console.log('🔍 All query params:', Object.fromEntries(queryParams.entries()))
        
        // Отправляем POST запрос с параметрами в query string
        const response = await axios.post(fullUrl, null, {
          headers: {
            'Content-Type': 'application/json'
          }
        })
        
        // Логируем фактический URL запроса для отладки
        console.log('Actual request URL:', response.config?.url || fullUrl)
        console.log('Request was successful:', response.status === 200)

        if (response.data && response.data.success) {
          const isNew = response.data.is_new
          const userData = response.data.user
          
          console.log('Telegram user registered/updated:', {
            is_new: isNew,
            telegram_id: userData?.telegram_id,
            tickets: userData?.tickets,
            referrals_count: userData?.referrals_count,
            invited_by: userData?.invited_by_telegram_user_id,
            start_param: startParam
          })
          
          // Если это новый пользователь и была реферальная ссылка
          if (isNew && startParam) {
            console.log('New user registered via referral link:', {
              new_user_id: userData?.telegram_id,
              inviter_id: startParam,
              inviter_referrals_count: userData?.referrals_count
            })
          }
          
          // Сохраняем данные пользователя в store
          if (this.$store && userData) {
            this.$store.commit('setTelegramUser', userData)
          }
        }
      } catch (error) {
        console.error('Error registering Telegram user:', error)
        // Не блокируем загрузку приложения при ошибке регистрации
        if (error.response) {
          console.error('API Error:', error.response.data)
        }
      }
    },
    async checkSubscriptions() {
      const telegram = window.Telegram && window.Telegram.WebApp
      
      if (!telegram) {
        // Если не в Telegram, пропускаем проверку
        this.subscriptionsChecked = true
        this.allSubscribed = true
        return
      }

      try {
        // Используем общий метод для получения initData
        const initDataRaw = this.getInitData()
        
        if (!initDataRaw) {
          // Если нет initData, считаем что нужно подписаться
          this.subscriptionsChecked = true
          this.allSubscribed = false
          return
        }

        // Запрос к API для проверки подписок
        // Используем force=false чтобы использовать кеш если есть
        const axios = (await import('axios')).default
        const response = await axios.get('/api/v1/subscriptions/check', {
          params: {
            initData: initDataRaw,
            force: false // Использовать кеш для ускорения
          }
        })

        if (response.data.success) {
          this.allSubscribed = response.data.allSubscribed
        } else {
          // При ошибке API показываем страницу подписки
          this.allSubscribed = false
        }
        
        this.subscriptionsChecked = true
      } catch (error) {
        console.error('Error checking subscriptions:', error)
        // При ошибке показываем страницу подписки
        this.subscriptionsChecked = true
        this.allSubscribed = false
      }
    },
    
    handleSubscribed() {
      this.allSubscribed = true
      this.subscriptionsChecked = true
    },
    
    setActiveView(view) {
      this.activeView = view
    },
    togglePopup(state) {
      this.showPopup = state
    },
    handlePopupConfirm() {
      this.togglePopup(false)
    },
    handleSpinResult(section) {
      const title = section?.text || 'Результат вращения'
      const description = section?.answer || section?.text || 'Попробуйте ещё раз!'
      this.popupTitle = title
      this.popupMessage = description
      this.togglePopup(true)
    }
  }
}
</script>

<style scoped>
.start-container {
  width: 100%;
  max-width: 375px;
  margin: 0 auto;
  background: linear-gradient(180deg, #f8a575 0%, #fdb083 100%);
  font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  display: flex;
  align-items: stretch;
  justify-content: center;
  overflow: hidden;
  position: relative;
  padding: 0 12px;
}

.start-content {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.start-nav {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
}

.nav-btn {
  height: 40px;
  border: none;
  border-radius: 10px;
  background: rgba(231, 125, 101, 0.85);
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.02em;
  cursor: pointer;
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.nav-btn.active {
  background: linear-gradient(8deg, #ffdfa4 0%, #ffb97e 100%);
  color: #c94f35;
}

.nav-btn.popup-btn {
  grid-column: span 3;
  background: rgba(255, 255, 255, 0.25);
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.4);
}

.nav-btn:active {
  transform: scale(0.97);
}

.start-view {
  flex: 1;
  min-height: 0;
  display: flex;
  align-items: stretch;
  justify-content: center;
}

.start-view > * {
  width: 100%;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

