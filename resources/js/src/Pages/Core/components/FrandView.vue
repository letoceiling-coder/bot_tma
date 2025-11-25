<template>
  <BaseFrand v-bind="attrs">
    <slot />
  </BaseFrand>
</template>

<script setup>
import { useAttrs } from 'vue'
import BaseFrand from '/resources/js/src/Pages/Frand/index.vue'

const attrs = useAttrs()
</script>


