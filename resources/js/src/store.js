/**
 * ============================================================
 * Vuex Store - Централизованное хранилище состояния
 * ============================================================
 *
 * Глобальное хранилище состояния приложения на основе Vuex 4.
 * Позволяет управлять общими данными между компонентами.
 *
 * @module store
 * @requires vuex ^4.0.0
 */

import { createStore } from 'vuex';
import { getTelegramUser } from './utils/telegram.js';

/**
 * Создание экземпляра Vuex Store
 *
 * @constant {Store} store
 */
const store = createStore({
    /**
     * ========================================
     * STATE - Состояние приложения
     * ========================================
     *
     * Реактивные данные, доступные во всём приложении
     *
     * @returns {Object} Начальное состояние
     */
    state() {
        return {
            /**
             * Данные авторизованного пользователя
             *
             * @property {Object|null} user
             * @property {number} user.id - ID пользователя
             * @property {string} user.name - Имя пользователя
             * @property {string} user.email - Email пользователя
             * @property {string} user.phone - Телефон пользователя
             * @property {Object} user.role - Роль пользователя
             * @property {number} user.role.id - ID роли (1=User, 500=Moderator, 900=Admin, 999=Developer)
             * @property {string} user.role.name - Название роли
             * @property {boolean} user.is_admin - Является ли администратором
             * @property {boolean} user.is_moderator - Является ли модератором
             *
             * @example
             * // Получение данных пользователя
             * this.$store.state.user
             *
             * // Проверка авторизации
             * if (this.$store.state.user) {
             *   console.log('Пользователь авторизован');
             * }
             */
            user: null,
            /**
             * Данные пользователя из Telegram Mini App
             */
            telegramUser: null,

            /**
             * Настройки приложения
             *
             * @property {Array} settings
             *
             * @example
             * // Массив настроек из API
             * [
             *   { key: 'app_name', value: 'CRM для Telegram ботов' },
             *   { key: 'app_locale', value: 'ru' }
             * ]
             */
            settings: [],

            /**
             * Ошибки валидации форм
             *
             * @property {Array} errors
             *
             * @example
             * // Массив ошибок от Laravel backend
             * [
             *   { field: 'email', message: 'Email обязателен для заполнения' },
             *   { field: 'password', message: 'Пароль должен содержать минимум 8 символов' }
             * ]
             *
             * // Очистка ошибок
             * this.$store.state.errors = [];
             */
            errors: [],
            media:{
                current:null,
                folders:[],
                filter:{

                },
                uploadSettings: {
                    maxFiles: 20,                    // Максимальное количество файлов за раз
                    maxFilesize: 50,                 // Максимальный размер файла в MB
                    parallelUploads: 20,              // Количество одновременных загрузок
                    acceptedFiles: 'image/*,video/*,application/pdf,.doc,.docx', // Допустимые типы файлов
                    acceptedExtensions: [            // Список допустимых расширений для UI
                        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',  // Изображения
                        'mp4', 'avi', 'mov', 'webm',                  // Видео
                        'pdf', 'doc', 'docx'                          // Документы
                    ],
                    acceptedMimeTypes: [             // MIME типы для валидации
                        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
                        'video/mp4', 'video/x-msvideo', 'video/quicktime', 'video/webm',
                        'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ],
                    fileTypes: {
                        images: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
                        videos: ['mp4', 'avi', 'mov', 'webm'],
                        documents: ['pdf', 'doc', 'docx']
                    }
                }
            }
        };
    },

    /**
     * ========================================
     * MUTATIONS - Синхронные изменения state
     * ========================================
     *
     * Методы для изменения состояния
     * Должны быть синхронными!
     *
     * @example
     * // Использование мутации
     * this.$store.commit('setUser', userData);
     *
     * TODO: Добавить мутации:
     * - setUser(state, user) - установить данные пользователя
     * - clearUser(state) - очистить данные пользователя
     * - setSettings(state, settings) - установить настройки
     * - setErrors(state, errors) - установить ошибки
     * - clearErrors(state) - очистить ошибки
     */
    mutations: {
        /**
         * Устанавливает данные пользователя из Telegram WebApp
         *
         * @param state
         * @param {Object|null} user
         */
        setTelegramUser(state, user) {
            state.telegramUser = user;
        },
        /**
         * Очищает данные Telegram пользователя
         *
         * @param state
         */
        clearTelegramUser(state) {
            state.telegramUser = null;
        }
    },

    /**
     * ========================================
     * ACTIONS - Асинхронные операции
     * ========================================
     *
     * Методы для асинхронных операций (API запросы)
     *
     * @example
     * // Использование action
     * this.$store.dispatch('fetchUser');
     *
     * TODO: Добавить actions:
     * - fetchUser() - загрузить данные пользователя из API
     * - login(credentials) - авторизация
     * - logout() - выход
     * - fetchSettings() - загрузить настройки
     */
    actions: {
        /**
         * Инициализация данных Telegram пользователя
         *
         * @param commit
         * @returns {Object|null}
         */
        initTelegramUser({ commit }) {
            const user = getTelegramUser();
            commit('setTelegramUser', user);
            return user;
        }
    },

    /**
     * ========================================
     * GETTERS - Вычисляемые свойства
     * ========================================
     *
     * Производные данные на основе state
     *
     * @example
     * // Использование getter
     * this.$store.getters.isAuthenticated
     *
     * TODO: Добавить getters:
     * - isAuthenticated(state) - проверка авторизации
     * - userRole(state) - получить роль пользователя
     * - isAdmin(state) - проверка на администратора
     * - hasErrors(state) - есть ли ошибки
     */
    getters: {
        telegramUser(state) {
            return state.telegramUser;
        },
        telegramUserId(state) {
            return state.telegramUser?.id ?? null;
        },
        telegramUserAvatar(state) {
            return state.telegramUser?.photoUrl ?? null;
        },
        telegramDisplayName(state) {
            const user = state.telegramUser;
            if (!user) {
                return 'Игрок';
            }
            if (user.fullName) {
                return user.fullName;
            }
            if (user.username) {
                return user.username;
            }
            const parts = [user.firstName, user.lastName].filter(Boolean);
            if (parts.length) {
                return parts.join(' ');
            }
            return 'Игрок';
        }
    },
});

/**
 * Экспорт экземпляра store
 *
 * @exports {Store} store
 */
export default store;
