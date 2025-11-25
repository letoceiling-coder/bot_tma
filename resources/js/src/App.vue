<template>
    <div id="app">
        <router-view></router-view>
    </div>
    <notifications />
    <modal-media/>
</template>

<script>
export default {
    name: 'App',
    mounted() {
        // Загружаем настройки из глобальной переменной
        if (typeof settingsSite !== 'undefined') {
            this.$store.state.settings = settingsSite;
            this.$store.state.user = settingsSite.user;
        }

        console.log('CRM App загружено');
    }
}
</script>

<style>
#app {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8a575 0%, #fdb083 100%);
  --footer-height: 70px; /* 58px height + 12px bottom offset */
}
</style>

