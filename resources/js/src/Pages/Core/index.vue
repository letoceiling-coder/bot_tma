<template>
  <div class="core-container">
    <transition name="fade" mode="out-in">
      <LoadScreen v-if="isLoading" />
      <div v-else class="core-content">
        <div class="core-view">
          <component
            :is="currentComponent"
            @spin-result="handleSpinResult"
          />
        </div>
      </div>
    </transition>

    <GiftPopUp
      :visible="showPopup"
      :description="popupMessage"
      :title="popupTitle"
      @close="togglePopup(false)"
      @confirm="handlePopupConfirm"
    />
  </div>
</template>

<script>
import LoadScreen from './components/LoadScreen.vue'
import MainView from './components/MainView.vue'
import PagesWrapper from './components/PagesWrapper.vue'
import FrandView from './components/FrandView.vue'
import TopView from './components/TopView.vue'
import GiftPopUp from './components/GiftPopUp.vue'

export default {
  name: 'CorePage',
  components: {
    LoadScreen,
    GiftPopUp
  },
  data() {
    return {
      isLoading: true,
      activeView: 'main',
      showPopup: false,
      popupMessage: 'Секретный подарок уже почти у вас!',
      popupTitle: 'Секретный подарок от кролика',
      preloadTimeout: null,
      viewMap: {
        main: { component: MainView },
        pages: { component: PagesWrapper },
        friends: { component: FrandView },
        top: { component: TopView }
      }
    }
  },
  computed: {
    currentComponent() {
      const view = this.viewMap[this.activeView]
      return view?.component || MainView
    }
  },
  mounted() {
    // Сохраняем реферальный параметр start или ref из URL (если есть),
    // чтобы не потерять его при навигации по роутам SPA
    try {
      const urlParams = new URLSearchParams(window.location.search)
      // Поддерживаем оба варианта: start (стандартный) и ref (кастомный)
      const refParam = urlParams.get('ref') || urlParams.get('start') || urlParams.get('startapp')
      if (refParam) {
        localStorage.setItem('telegram_start_param', refParam)
        console.log('Saved referral param to localStorage from CorePage URL:', {
          ref: urlParams.get('ref'),
          start: urlParams.get('start'),
          startapp: urlParams.get('startapp'),
          saved: refParam
        })
      }
    } catch (e) {
      console.warn('Failed to read referral param from URL in CorePage:', e)
    }

    this.initializeTelegramUser()
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
    initializeTelegramUser() {
      this.$store.dispatch('initTelegramUser')
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
.core-container {
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

.core-content {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px 0;
}

.core-view {
  flex: 1;
  min-height: 0;
  display: flex;
  align-items: stretch;
  justify-content: center;
}

.core-view > * {
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
