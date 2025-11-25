<template>
  <BaseTop v-bind="attrs">
    <slot />
  </BaseTop>
</template>

<script setup>
import { useAttrs } from 'vue'
import BaseTop from '/resources/js/src/Pages/Top/index.vue'

const attrs = useAttrs()
</script>


