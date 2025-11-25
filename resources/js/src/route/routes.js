import Home from "../Pages/Main/index.vue";
import LoadScreen from "../Pages/Load/index.vue";
import PageIntro from "../Pages/Page/index.vue";
import PageTwo from "../Pages/Page2/index.vue";
import PageThree from "../Pages/Page3/index.vue";
import PagesWrapper from "../Pages/Pages/index.vue";
import StartPage from "../Pages/Start/index.vue";
import CorePage from "../Pages/Core/index.vue";
import FrandPage from "../Pages/Frand/index.vue";
import TopPage from "../Pages/Top/index.vue";
import GiftPopUp from "../Pages/PopUp/index.vue";
import MediaManager from "../UI/Media/index.vue";
import WheelSettings from "../Pages/Admin/WheelSettings.vue";
import ChannelSettings from "../Pages/Admin/ChannelSettings.vue";

/**
 * Конфигурация маршрутов приложения
 */
const routes = [
    /**
     * Медиа-менеджер
     * Доступен по адресу: /admin/media
     */
    {
        path: '/admin/media',
        component: MediaManager,
        name: 'admin.media',
        meta: {
            title: 'Медиа-менеджер',
            requiresAuth: true,
        },
    },
    {
        path: '/admin/settings/wheel',
        component: WheelSettings,
        name: 'admin.settings.wheel',
        meta: {
            title: 'Настройки колеса',
            requiresAuth: true,
        },
    },
    {
        path: '/admin/settings/channels',
        component: ChannelSettings,
        name: 'admin.settings.channels',
        meta: {
            title: 'Управление каналами',
            requiresAuth: true,
        },
    },

    /**
     * Экран загрузки
     * Адрес: /load
     */
    {
        path: '/load',
        component: LoadScreen,
        name: 'load',
        meta: {
            title: 'Загрузка',
        },
    },

    /**
     * Онбординг страницы
     */
    {
        path: '/page',
        component: PageIntro,
        name: 'page',
        meta: {
            title: 'Онбординг 1',
        },
    },
    {
        path: '/page-2',
        component: PageTwo,
        name: 'pageTwo',
        meta: {
            title: 'Онбординг 2',
        },
    },
    {
        path: '/page-3',
        component: PageThree,
        name: 'pageThree',
        meta: {
            title: 'Онбординг 3',
        },
    },
    {
        path: '/pages',
        component: PagesWrapper,
        name: 'pages',
        meta: {
            title: 'Онбординг',
        },
    },
    {
        path: '/',
        component: CorePage,
        name: 'core',
        meta: {
            title: 'Старт',
        },
    },
    {
        path: '/start',
        component: StartPage,
        name: 'start',
        meta: {
            title: 'Старт',
        },
    },

    /**
     * Друзья и топ
     */
    {
        path: '/friends',
        component: FrandPage,
        name: 'friends',
        meta: {
            title: 'Пригласи друзей',
        },
    },
    {
        path: '/top',
        component: TopPage,
        name: 'top',
        meta: {
            title: 'Топ игроков',
        },
    },

    /**
     * Попап превью
     */
    {
        path: '/popup',
        component: GiftPopUp,
        name: 'popup',
        meta: {
            title: 'Попап подарка',
        },
    },

    /**
     * Главная страница с динамической маршрутизацией
     * Все остальные роуты обрабатываются здесь
     */
    {
        path: '/:template?/:action?/:params?',
        component: Home,
        name: 'home',
        props: true,
    },
];

export default routes;
