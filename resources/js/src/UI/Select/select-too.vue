<template>

    <select ref="mySelect"

            v-bind="$attrs"
            multiple="multiple"
            data-placeholder="Select a State"
            data-dropdown-css-class="select2-purple"
            >
        <slot name="option"></slot>
        <option v-for="select in selects" :value="select.id ?? select.name"
                :disabled="select.disabled"
                :class="select.disabled ? 'option-disabled' : ''"
        >{{ select.name }}</option>


    </select>
    <div ref="errors" class="error-block">
        <mc-error  :keys="keys"/>
    </div>
</template>

<script>
export default {
name: "select-too",
    props: {
        selects: Object,
        keys: String | null,
        modelValue: String | Number,
    },
    emits: ['update:modelValue'],
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
    computed: {
        value: {
            get() {
                return this.modelValue
            },
            set(value) {
                this.$emit('update:modelValue', value)
            }
        }
    },
    methods: {
        setForm(value) {


            this.$emit('update:modelValue', value)

        },
        changeOn() {

            $(this.$refs.mySelect).val(this.modelValue).trigger('change')
        }
    },
    mounted() {
        $(this.$refs.mySelect).select2({
            theme: 'bootstrap4'
        })



        let setForm = this.setForm
        let changeOn = this.changeOn

        $(this.$refs.mySelect).on('change', function () {

            setForm($(this).val())
        });
        setTimeout(function () {
            changeOn()
        }, 2000);

    }
}
</script>

<style scoped>

</style>
