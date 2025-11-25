<template>

    <textarea   :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                v-bind="$attrs"></textarea>

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
}
</script>

<style scoped>

</style>
