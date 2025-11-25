<template>
    <select ref="myButton"

            :class=" this.storage.errors.find(item => item.name === keys) ? 'is-invalid': ''"
            data-placeholder="Select a State"
            data-dropdown-css-class="select2-purple"
            v-bind="$attrs"
            style="width: 100%;position: absolute;opacity: 0;">
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
    name: "select-one",
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
    methods: {
        setForm(value) {

            this.$emit('update:modelValue', value)


        },
        changeOn() {

            $(this.$refs.myButton).val(this.modelValue).trigger('change')
        }
    },
    mounted() {
        $(this.$refs.myButton).select2({
            theme: 'bootstrap4'
        })


        let setForm = this.setForm
        let changeOn = this.changeOn

        $(this.$refs.myButton).on('change', function () {

            setForm($(this).val())
        });
        setTimeout(function () {
            changeOn()
        }, 500);

    }
}
</script>

<style >
li[aria-disabled="true"]{
    background: #ccc;
    padding: 5px;
    width: 100%;
    cursor: no-drop;
}
</style>
