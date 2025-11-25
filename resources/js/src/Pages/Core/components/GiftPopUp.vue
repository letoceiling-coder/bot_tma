<template>
  <BaseGiftPopUp v-bind="attrs">
    <slot />
  </BaseGiftPopUp>
</template>

<script setup>
import { useAttrs } from 'vue'
import BaseGiftPopUp from '/resources/js/src/Pages/PopUp/index.vue'

const attrs = useAttrs()
</script>


