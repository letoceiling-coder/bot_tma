<template>
  <div class="redesign-container">
    <transition name="fade" mode="out-in">
      <LoadScreen v-if="isLoading" />
      <SubscribePage
        v-else-if="!subscriptionsChecked || !allSubscribed"
        @subscribed="handleSubscribed"
      />
      <div v-else class="redesign-content">
        <div class="redesign-view">
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
  name: 'RedesignPage',
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
        let startParam = null
        
        const telegram = window.Telegram && window.Telegram.WebApp
        
        if (telegram) {
          if (telegram.tgWebAppStartParam) {
            startParam = telegram.tgWebAppStartParam
          } else if (telegram.start_param) {
            startParam = telegram.start_param
          } else if (telegram.initDataUnsafe && telegram.initDataUnsafe.start_param) {
            startParam = telegram.initDataUnsafe.start_param
          }
        }
        
        if (!startParam) {
          const urlParams = new URLSearchParams(window.location.search)
          startParam = urlParams.get('ref') || urlParams.get('start') || urlParams.get('startapp')
        }
        
        if (!startParam && window.location.hash) {
          const hashParams = new URLSearchParams(window.location.hash.substring(1))
          startParam = hashParams.get('ref') || hashParams.get('start') || hashParams.get('startapp')
        }

        if (!startParam) {
          const storedStart = localStorage.getItem('telegram_start_param')
          if (storedStart) {
            startParam = storedStart
          }
        }
        
        const referralParam = startParam || null
        
        const axios = (await import('axios')).default
        
        const queryParams = new URLSearchParams()
        queryParams.append('initData', initData)
        
        if (referralParam && referralParam !== null && referralParam !== '') {
          queryParams.append('ref', String(referralParam))
        }
        
        const queryString = queryParams.toString()
        const fullUrl = `/api/v1/telegram-users/start?${queryString}`
        
        const response = await axios.post(fullUrl, null, {
          headers: {
            'Content-Type': 'application/json'
          }
        })

        if (response.data && response.data.success) {
          const isNew = response.data.is_new
          const userData = response.data.user
          
          if (this.$store && userData) {
            this.$store.commit('setTelegramUser', userData)
          }
        }
      } catch (error) {
        console.error('Error registering Telegram user:', error)
      }
    },
    async checkSubscriptions() {
      const telegram = window.Telegram && window.Telegram.WebApp
      
      if (!telegram) {
        this.subscriptionsChecked = true
        this.allSubscribed = true
        return
      }

      try {
        const initDataRaw = this.getInitData()
        
        if (!initDataRaw) {
          this.subscriptionsChecked = true
          this.allSubscribed = false
          return
        }

        const axios = (await import('axios')).default
        const response = await axios.get('/api/v1/subscriptions/check', {
          params: {
            initData: initDataRaw,
            force: false
          }
        })

        if (response.data.success) {
          this.allSubscribed = response.data.allSubscribed
        } else {
          this.allSubscribed = false
        }
        
        this.subscriptionsChecked = true
      } catch (error) {
        console.error('Error checking subscriptions:', error)
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
.redesign-container {
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
  padding: 0;
  height: 100vh;
  max-height: 100vh;
}

.redesign-content {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.redesign-view {
  flex: 1;
  min-height: 0;
  display: flex;
  align-items: stretch;
  justify-content: center;
  overflow: hidden;
}

.redesign-view > * {
  width: 100%;
  height: 100%;
  overflow: hidden;
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

