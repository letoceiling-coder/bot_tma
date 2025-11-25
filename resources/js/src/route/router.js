import {createRouter, createWebHistory} from "vue-router";
import routes from "./routes.js";

const router = createRouter({
    history: createWebHistory(),
    routes: routes,

    scrollBehavior(to, from, savedPosition) {

        return { x: 0, y: 0 };
    },

});

export default router;
