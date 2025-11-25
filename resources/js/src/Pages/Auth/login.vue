<template>

    <div class="container flex-center column pb-3">
        <div class="col-lg-4 col-md-6 col-sm-12 col-12" id="form">
            <h1 class="mt-4 text-center">Авторизация</h1>
            <form>
                <div class="form-group">
                    <mc-input v-model="form.email" class="input-element input-wrapper__element-inp form-control"
                              type="email"
                              name="email"
                              keys="email"
                              autofocus=""
                              placeholder="Email"
                    />
                </div>
                <div class="form-group">
                    <mc-input v-model="form.password" class="input-element input-wrapper__element-inp form-control"
                              type="password"
                              name="password"
                              keys="password"
                              placeholder="Пароль"
                    />
                </div>
                <div class="pb-2">
                    <router-link to="/forget" class="text-dark hover">Не помню пароль</router-link>
                </div>
                <div class="form-group">
                    <button @click="login" type="button" class="form-control btn-keypress">Войти</button>
                </div>
                <div class="form-group">
                    <router-link to="/register" class="form-control text-center">Регистрация</router-link>
                </div>


            </form>
        </div>
    </div>

</template>

<script>

import Cookies from "js-cookie";


export default {
    name: "login",

    mounted() {

        if (this.storage.user){
            this.$router.push('/')
        }

    },
    data() {
        return {

            form: {
                email: '',
                password: '',
            },


        }
    },

    methods: {


        async login() {


            await axios.post('/api/v1/user/login', {
                email: this.form.email,
                password: this.form.password,
            })
                .then(data => {
                    console.log(data)
                    if (data) {
                        Cookies.set("accessToken", data.data.accessToken)

                        window.location.href = "/admin/dashboard"
                    }

                }).catch((error) => {
                    console.log(error)

                })


        },
    }
}
</script>

<style scoped lang="scss">
@import '/resources/css/mixin.scss';


@media only screen and (max-width: 450px) {

}
</style>
