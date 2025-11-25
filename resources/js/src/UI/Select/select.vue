<template>

    <select :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            :class=" this.storage.errors.find(item => item.name === keys) ? 'is-invalid': ''"
            v-bind="$attrs"
    :disabled="$attrs.selectable ? true :false">
        <slot name="option"></slot>

        <option v-for="select in selects" :value="select.id ?? select.name"
                :disabled="select.disabled"
                :class="select.disabled ? 'option-disabled' : ''"
                >{{ select.name }}</option>

    </select>

    <slot></slot>

    <div ref="errors" class="error-block">
        <mc-error  :keys="keys"/>
    </div>
</template>

<script>
export default {
    props: {
        modelValue: String | Number,
        keys: String | null,
        selects: Object,
        disabled: {type:Boolean,default:true},

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
select option:disabled{
    background: #ccc;
    width: 500px;
    padding: 5px;
}
</style>
