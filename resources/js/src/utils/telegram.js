/**
 * Telegram Mini App helper utilities.
 *
 * Provides a single place to interact with the Telegram WebApp API
 * and extract user-related information for the Vue front-end.
 */

const DEFAULT_USER = {
  id: null,
  firstName: '',
  lastName: '',
  username: '',
  photoUrl: null,
  languageCode: 'ru',
  isTelegram: false,
  fullName: '',
  raw: null
}

/**
 * Safely composes a display name using the available Telegram fields.
 *
 * @param {Object} user
 * @returns {string}
 */
function buildDisplayName(user = {}) {
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

/**
 * Reads user data from Telegram WebApp (if available).
 *
 * @returns {Object} normalized user payload
 */
export function getTelegramUser() {
  if (typeof window === 'undefined') {
    return { ...DEFAULT_USER }
  }

  const telegram = window.Telegram && window.Telegram.WebApp
  if (!telegram) {
    return { ...DEFAULT_USER }
  }

  try {
    telegram.ready && telegram.ready()
    telegram.expand && telegram.expand()
  } catch (e) {
    console.warn('Telegram WebApp initialization failed', e)
  }

  const rawUser = telegram.initDataUnsafe?.user
  if (!rawUser) {
    return {
      ...DEFAULT_USER,
      isTelegram: true,
      raw: telegram.initDataUnsafe || null,
      fullName: 'Игрок'
    }
  }

  const normalized = {
    id: rawUser.id ?? null,
    firstName: rawUser.first_name ?? '',
    lastName: rawUser.last_name ?? '',
    username: rawUser.username ?? '',
    photoUrl: rawUser.photo_url ?? null,
    languageCode: rawUser.language_code ?? 'ru',
    isTelegram: true,
    fullName: buildDisplayName(rawUser),
    raw: rawUser
  }

  return normalized
}

/**
 * Extracts only public-facing fields that UI components need.
 *
 * @returns {{username: string, userAvatar: string|null, userId: number|null}}
 */
export function getUiUserPayload() {
  const user = getTelegramUser()
  return {
    username: user.fullName || 'Игрок',
    userAvatar: user.photoUrl,
    userId: user.id,
    telegramUser: user
  }
}

