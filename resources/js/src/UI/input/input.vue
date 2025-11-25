<template>
    <slot name="label"></slot>
    <input :value="modelValue"
           @input="$emit('update:modelValue', $event.target.value)"
           :class=" this.storage.errors.find(item => item.name === keys) ? 'is-invalid': ''"
           v-bind="$attrs"
    />
    <slot></slot>


    <div ref="errors" class="error-block" :style="this.storage.errors.find(item => item.name == keys) ? 'display: block!important;' : ''">
        <mc-error  :keys="keys"/>
    </div>



</template>

<script>
export default {
    props: {
        modelValue: String | Number,
        keys: String | null,

    },
    emits: ['update:modelValue'],
    watch:{
        modelValue(){
           if (this.modelValue && this.modelValue.length > 0 && this.storage.errors.find(item => item.name === this.keys)){
               let keys = this.keys
               $(this.$refs.errors).slideUp()
               this.storage.errors.splice(this.storage.errors.findIndex(function(err) {
                   return err.name === keys
               }),1)

           }
        }
    },
    data(){
        return{

        }
    },
    mounted() {

    }


}
</script>
<style scoped lang="scss">
my-input{
    width: 100%;
}
.my-input:has(.error) {
    input{
        border:1px solid red!important;
    }
    span{
        padding-top: 3px;
    }
}
.error {
    color: #dc3545;;
    font-size: 12px;
}

</style>
