<template>

  <header class="header">
    <div class="logotype">
      <div class="logo" :class="{ 'logo--image': Boolean(resolvedAvatar) }">
        <img
          v-if="resolvedAvatar"
          class="logo-img"
          :src="resolvedAvatar"
          alt="Аватар игрока"
          draggable="false"
        />
        <span v-else class="logo-initials">{{ userInitials }}</span>
      </div>
      <span class="username">{{ username }}</span>
    </div>
    <button class="primary-btn" @click="$emit('action')">
      <span class="btn-text">{{ buttonLabel }}</span>
      <span class="btn-arrow">{{ buttonArrow }}</span>
    </button>
  </header>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
  name: 'FortuneHeader',
  props: {
    buttonLabel: {
      type: String,
      default: 'Как играть?'
    },
    buttonArrow: {
      type: String,
      default: '→'
    }
  },
  emits: ['action'],
  data() {
    return {
      initData: null,
      telegramUser: null
    }
  },
  mounted() {
    // Ждем загрузки Telegram WebApp SDK
    if (window.Telegram && window.Telegram.WebApp) {
      this.initTelegram()
    } else {
      // Если SDK еще не загружен, ждем события загрузки
      window.addEventListener('load', () => {
        this.initTelegram()
      })
      // Также пробуем через небольшой таймаут
      setTimeout(() => {
        this.initTelegram()
      }, 100)
    }
  },
  computed: {
    ...mapGetters(['telegramDisplayName', 'telegramUserAvatar']),
    username() {
      // Приоритет: store -> telegram initData -> fallback
      if (this.telegramDisplayName) {
        return this.telegramDisplayName
      }
      if (this.telegramUser?.fullName) {
        return this.telegramUser.fullName
      }
      if (this.telegramUser?.username) {
        return this.telegramUser.username
      }
      const parts = [
        this.telegramUser?.firstName,
        this.telegramUser?.lastName
      ].filter(Boolean)
      if (parts.length) {
        return parts.join(' ')
      }
      return 'Игрок'
    },
    resolvedAvatar() {
      // Приоритет: store -> telegram initData -> fallback
      if (this.telegramUserAvatar) {
        return this.telegramUserAvatar
      }
      if (this.telegramUser?.photoUrl) {
        return this.telegramUser.photoUrl
      }
      if (typeof this.asset === 'function') {
        return this.asset('img/system/no-user.jpg')
      }
      return 'img/system/no-user.jpg'
    },
    userInitials() {
      if (!this.username) return '??'
      const parts = this.username
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map(part => part[0]?.toUpperCase() || '')
        .join('')
      return parts || '??'
    }
  },
  methods: {
    initTelegram() {
      if (typeof window === 'undefined') {
        console.warn('FortuneHeader: window is undefined')
        return
      }

      const telegram = window.Telegram && window.Telegram.WebApp
      if (!telegram) {
        console.warn('FortuneHeader: Telegram WebApp is not available')
        return
      }

      try {
        // Инициализация Telegram WebApp
        if (telegram.ready) {
          telegram.ready()
        }
        if (telegram.expand) {
          telegram.expand()
        }

        // Сохраняем initData для отладки
        this.initData = telegram.initDataUnsafe || null

        console.log('FortuneHeader: initDataUnsafe', this.initData)

        // Извлекаем данные пользователя
        const rawUser = telegram.initDataUnsafe?.user
        console.log('FortuneHeader: rawUser', rawUser)

        if (rawUser) {
          this.telegramUser = {
            id: rawUser.id ?? null,
            firstName: rawUser.first_name ?? '',
            lastName: rawUser.last_name ?? '',
            username: rawUser.username ?? '',
            photoUrl: rawUser.photo_url ?? null,
            languageCode: rawUser.language_code ?? 'ru',
            fullName: this.buildDisplayName(rawUser),
            raw: rawUser
          }

          console.log('FortuneHeader: telegramUser', this.telegramUser)

          // Если в store нет данных, обновляем store
          if (!this.telegramDisplayName && this.telegramUser.fullName) {
            this.$store.dispatch('initTelegramUser')
          }
        } else {
          console.warn('FortuneHeader: No user data in initDataUnsafe')
        }
      } catch (e) {
        console.error('FortuneHeader: Telegram WebApp initialization failed', e)
      }
    },
    buildDisplayName(user = {}) {
      const parts = [user.first_name, user.last_name].filter(Boolean)
      if (parts.length) {
        return parts.join(' ')
      }
      if (user.username) {
        return user.username
      }
      if (user.first_name) {
        return user.first_name
      }
      return 'Игрок'
    }
  }
}
</script>

<style scoped>
.header {
  position: sticky;
  top: 4px;
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  z-index: 20;
  padding: 12px 16px;
  margin: 0 auto 8px;
  border-radius: 16px;
  background: rgba(248, 165, 117, 0.9);
  box-shadow: 0 6px 20px rgba(231, 125, 101, 0.25);
}

.logotype {
  display: flex;
  align-items: center;
  gap: 8px;
}

.logo {
  width: 32px;
  height: 32px;
  background: #000000;
  border-radius: 50%;
  border: 1px solid #F7785B;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.logo-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.logo-initials {
  color: #FFFFFF;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.username {
  font-size: 12px;
  font-weight: 500;
  line-height: 14px;
  color: #FFFFFF;
}

.primary-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 15px;
  background: #E77D65;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.btn-text {
  font-size: 12px;
  font-weight: 500;
  line-height: 14px;
  color: #FFFFFF;
}

.btn-arrow {
  font-size: 14px;
  color: #FFFFFF;
}
</style>

