<template>

    <vue-tel-input ref="mcPhone" v-model="phone"
                   v-bind="$attrs"
                   :only-countries="['Ru']"
                   @input="$emit('update:modelValue', $event.target.value)"
                   :class=" this.storage.errors.find(item => item.name === keys)  ? 'is-invalid': '' "
                   :inputOptions="inputOptions"
                   lang="ru"
                   defaultCountry="RU"
                   @blur="onBlur"



    ></vue-tel-input>

    <div ref="errors" class="error-block">
        <mc-error  :keys="keys"/>
    </div>
</template>

<script>
export default {

    props: {
        keys: String | null,
        modelValue: String | Number,
        inputOptions:{
            type:Object,
            default:{
                maxlength:16,
                placeholder:'телефон',
            }
        }
    },
    watch:{
        modelValue(){
            if (this.modelValue && this.storage.errors.find(item => item.name === this.keys)){
                let keys = this.keys
                $(this.$refs.errors).slideUp()
                this.storage.errors.splice(this.storage.errors.findIndex(function(err) {
                    return err.name === keys
                }),1)

            }
        }
    },
    emits: ['update:modelValue'],
    data() {
        return {

            phone: '',
            validate: false,
        }
    },
    methods: {

        onBlur() {
            let regex = /\+7\d{10}/
            if (this.phone){
                this.phone = this.phone.replace(/\D/g, "")
                if (this.phone.charAt(0) == '8' || this.phone.charAt(0) == '7'){
                    this.phone = this.phone.replace(/^./, '+7')

                }

                if (regex.test(this.phone) && this.phone.length == 12) {
                    this.validate = true
                    this.storage.errors = []
                }else {
                    this.storage.errors.push({
                        name:this.keys,
                        message:[
                            'Не верный номер телефона'
                        ]
                    })
                    this.$emit('update:modelValue',this.phone)
                    this.validate = false
                }
            }



        },


    },
    mounted() {
        this.phone = this.modelValue
        this.onBlur()
        this.inputOptions.maxlength = 16
        this.inputOptions.placeholder = this.inputOptions.placeholder ?? 'телефон'
    }
}
</script>


<style scoped lang="scss">
.is-invalid {
    border: 1px solid red !important;
}

.my-input:has(.error) {
    input {
        border: 1px solid red !important;
    }

    span {
        padding-top: 3px;
    }
}

.error {
    color: red;
    font-size: 12px;
}
</style>

