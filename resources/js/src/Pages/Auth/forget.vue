<template>
    <div class="container flex-center column pb-3 mb-5">
        <div class="col-lg-5 col-md-6 col-sm-12 col-12" id="form">
            <h1 class="mt-4 text-center">Восстановление пароля</h1>
            <p>Отправим ссылку на восстановление вам на почту</p>
            <form novalidate="novalidate">

                <div class="form-group">

                    <mc-input v-model="form.email"
                              name="email"
                              keys="email"
                              type="text"
                              placeholder="E-mail"
                              class="input-element form-popup__input form-control"

                    />

                </div>

                <div class="form-group">
                    <button @click.prevent="forgotEmail" class="input-element btnV2 form-control" type="button" >Отправить</button>

                </div>


                <div class="flex-center pb-5">
                    <router-link to="/login" class=" text-center">Уже есть аккаунт? Войдите
                    </router-link>
                </div>


            </form>
        </div>
    </div>
</template>

<script>
export default {
    name: "forget",
    data() {
        return {
            form: {
                email: '',
            },


        }
    },
    mounted() {
       this.form.email = ''
    },
    methods:{
        async forgotEmail() {

            await axios.post('/api/v1/user/forgot-email', this.form)
                .then(data => {

                    if (data){
                        this.$router.push('/login')
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

p {

    font-family: 'TT Interfaces', serif;
    font-style: normal;
    font-weight: 400;
    @include adaptiv-font(13, 10);
    line-height: 22px;

    text-align: center;
    letter-spacing: -0.25px;
    color: #1C1C1C;


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
