/**
 * ============================================================
 * jQuery - библиотека для работы с DOM
 * ============================================================
 * 
 * ВНИМАНИЕ: Bootstrap 5 НЕ требует jQuery!
 * jQuery подключен для совместимости со старыми компонентами
 * и плагинами, которые могут его использовать.
 * 
 * Делаем jQuery доступным глобально через window.$
 * 
 * @optional Можно удалить, если не используются jQuery плагины
 */
import $ from 'jquery';
window.$ = window.jQuery = $;

/**
 * ============================================================
 * Popper.js - позиционирование элементов
 * ============================================================
 * 
 * Используется Bootstrap для dropdowns, tooltips, popovers
 */
import '@popperjs/core';

/**
 * ============================================================
 * Bootstrap 5 - UI фреймворк
 * ============================================================
 * 
 * Bootstrap 5 не требует jQuery и работает с нативным JavaScript
 * Экспортируем в window.bootstrap для доступа к API
 * 
 * @example Использование
 * const modal = new window.bootstrap.Modal('#myModal');
 * modal.show();
 */
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

/**
 * ============================================================
 * Axios - HTTP клиент для API запросов
 * ============================================================
 * 
 * Автоматически отправляет CSRF токен в заголовках запросов
 * на основе значения cookie "XSRF-TOKEN"
 */
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ?? `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });
