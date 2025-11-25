/**
 * ============================================================
 * Регистр глобальных Vue компонентов
 * ============================================================
 * 
 * Этот модуль экспортирует все глобальные компоненты,
 * которые будут доступны в любом месте приложения
 * без необходимости локального импорта.
 * 
 * @module UI/register
 */

// ============================================================
// Компоненты медиа-менеджера
// ============================================================

/**
 * MediaManager - главный компонент управления медиафайлами
 * Позволяет загружать, просматривать и выбирать медиафайлы
 */
import Media from '/resources/js/src/UI/Media/index.vue';

/**
 * BtnMedia - кнопка для открытия менеджера медиафайлов
 * Используется в формах для выбора изображений
 */
import BtnMedia from '/resources/js/src/UI/Media/components/btn-media.vue';
import ModalMedia from '/resources/js/src/UI/Media/components/modal-media.vue';

// ============================================================
// Страницы-шаблоны
// ============================================================

/**
 * Main - главный layout с навигацией и sidebar
 * Основной контейнер для всех защищённых страниц
 */
import Main from '/resources/js/src/Pages/Core/index.vue';

/**
 * Login - страница авторизации
 * @route /login
 */
import Login from '/resources/js/src/Pages/Auth/login.vue';

/**
 * Register - страница регистрации нового пользователя
 * @route /register
 */
import Register from '/resources/js/src/Pages/Auth/register.vue';

/**
 * Forget - страница восстановления пароля
 * @route /forgot-password
 */
import Forget from '/resources/js/src/Pages/Auth/forget.vue';

// ============================================================
// UI компоненты - Уведомления и ошибки
// ============================================================

/**
 * NotyError - компонент отображения ошибок валидации
 * Показывает красивые уведомления об ошибках
 */
import NotyError from '/resources/js/src/UI/error.vue';

// ============================================================
// UI компоненты - Поля ввода
// ============================================================

/**
 * MCInput - стандартное текстовое поле ввода
 * Поддерживает валидацию, placeholder, маски
 */
import MCInput from '/resources/js/src/UI/Input/input.vue';

/**
 * MCPhone - поле ввода телефона с маской
 * Автоматически форматирует номер телефона
 */
import MCPhone from '/resources/js/src/UI/Input/phone.vue';

/**
 * MCTextarea - многострочное текстовое поле
 * Для ввода больших объёмов текста
 */
import MCTextarea from '/resources/js/src/UI/Input/textarea.vue';

// ============================================================
// UI компоненты - Выпадающие списки
// ============================================================

/**
 * MCOption - опция для выпадающего списка
 * Используется внутри mc-select
 */
import MCOption from '/resources/js/src/UI/Select/option.vue';

/**
 * MCSelect - множественный выбор из списка
 * Позволяет выбрать несколько значений
 */
import MCSelect from '/resources/js/src/UI/Select/select.vue';

/**
 * MCSelectOne - одиночный выбор из списка
 * Позволяет выбрать только одно значение
 */
import MCSelectOne from '/resources/js/src/UI/Select/select-one.vue';

/**
 * MCSelectToo - альтернативный компонент выбора
 * Дополнительный вариант выпадающего списка
 */
import MCSelectToo from '/resources/js/src/UI/Select/select-too.vue';

// ============================================================
// Объект UI компонентов
// ============================================================

/**
 * Мапинг UI компонентов для глобальной регистрации
 * 
 * @constant {Object} components
 * @property {Component} mc-error - Компонент ошибок
 * @property {Component} mc-input - Текстовое поле
 * @property {Component} mc-phone - Поле телефона
 * @property {Component} mc-option - Опция списка
 * @property {Component} mc-select - Множественный выбор
 * @property {Component} mc-select-one - Одиночный выбор
 * @property {Component} mc-select-too - Альтернативный выбор
 * @property {Component} mc-textarea - Текстовая область
 * @property {Component} btn-media - Кнопка медиа
 * @property {Component} MediaManager - Менеджер медиафайлов
 */
const components = {
    'mc-error': NotyError,
    'mc-input': MCInput,
    'mc-phone': MCPhone,
    'mc-option': MCOption,
    'mc-select': MCSelect,
    'mc-select-one': MCSelectOne,
    'mc-select-too': MCSelectToo,
    'mc-textarea': MCTextarea,
    'btn-media': BtnMedia,
    'MediaManager': Media,
    'modal-media': ModalMedia,
};

// ============================================================
// Объект шаблонов-страниц
// ============================================================

/**
 * Мапинг компонентов-страниц для глобальной регистрации
 * 
 * @constant {Object} Template
 * @property {Component} mc-main - Главный layout
 * @property {Component} login - Страница входа
 * @property {Component} register - Страница регистрации
 * @property {Component} forget - Страница восстановления пароля
 */
const Template = {
    'mc-main': Main,
    'login': Login,
    'register': Register,
    'forget': Forget,
};

// ============================================================
// Экспорт объединённого объекта компонентов
// ============================================================

/**
 * Объединяем все компоненты в один объект для экспорта
 * 
 * @exports {Object} Объединённый объект всех компонентов
 * @returns {Object} Template + components
 */
export default Object.assign({}, Template, components);
