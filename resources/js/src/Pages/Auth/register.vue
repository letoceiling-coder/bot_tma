<template>
    <div class="container flex-center column pb-3">
        <div class="col-lg-4 col-md-6 col-sm-12 col-12" id="form">
            <h1 class="mt-4 text-center">Регистрация</h1>
            <form>

                <div class="form-group">

                    <mc-input v-model="form.name"

                              keys="name"
                              type="text"
                              class="input-element input-wrapper__element-inp form-control"
                              name="name"
                              placeholder="Имя"

                    />
                </div>

                <div class="form-group">

                    <mc-input v-model="form.email"

                              type="email"
                              keys="email"
                              class="input-element input-wrapper__element-inp form-control"
                              name="email"
                              placeholder="email"

                    />
                </div>

                <div class="form-group relative">

                    <mc-input v-model="form.password"

                              type="password"
                              keys="password"
                              class="input-element input-wrapper__element-inp form-control"
                              name="password"
                              placeholder="Пароль"
                    />
                </div>

                <div class="form-group relative">

                    <mc-input v-model="form.password_confirmation"

                              type="password"
                              keys="password_confirmation"
                              class="input-element input-wrapper__element-inp form-control"
                              name="password"
                              placeholder="Повторите пароль"
                    />
                </div>


                <div class="form-group">

                    <button @click.prevent="register" type="submit" name="submit" class="form-control">Регистрация</button>

                </div>
                <div class="form-group">
                    <router-link to="/login" class="form-control text-center">Авторизация</router-link>


                </div>

            </form>
        </div>
    </div>

</template>

<script>

import Cookies from "js-cookie";


export default {
    name: "register",

    mounted() {

        if (this.storage.user){
            this.$router.push('/')
        }
    },
    methods: {

        async register() {

            await axios.post('/api/v1/user/register', this.form)
                .then(data => {

                    if (data){
                        this.storage.user = data.data.user
                        this.storage.settings.user = data.data.user
                        this.storage.settings.accessToken = data.data.accessToken
                        Cookies.set("accessToken", data.data.accessToken)
                        this.$router.push('/')

                    }
                }).catch((error) => {
                    console.log(error)

                })


        },
    },
    data() {
        return {
            form: {
                email: '',
                name: '',
                password: '',
                password_confirmation: '',
            },

        }
    },

}
</script>


<style scoped lang="scss">
@import '/resources/css/mixin.scss';

.form-group {
    i {
        cursor: pointer;
        top: 10px;
        right: 10px;
        color: #ced4da;;
    }
}


.form-group input[name=submit]:hover {
    background-color: rgb(244 245 245);

}

h1 {
    @include adaptiv-font(32, 28);
}

a:hover {
    background-color: inherit;
    color: inherit;
}

#form {

    margin-top: 40px;
    background: #F4F5F5;
    border-radius: 20px;

    form {
        width: 90%;
        margin: 0 auto;

        input {
            width: 100%;
        }
    }

}

@media only screen and (max-width: 450px) {

}
</style>
