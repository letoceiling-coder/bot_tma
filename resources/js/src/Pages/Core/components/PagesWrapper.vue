<template>
  <BasePagesWrapper v-bind="attrs">
    <slot />
  </BasePagesWrapper>
</template>

<script setup>
import { useAttrs } from 'vue'
import BasePagesWrapper from '/resources/js/src/Pages/Pages/index.vue'

const attrs = useAttrs()
</script>


