<template>
  <BaseLoadScreen v-bind="attrs">
    <slot />
  </BaseLoadScreen>
</template>

<script setup>
import { useAttrs } from 'vue'
import BaseLoadScreen from '/resources/js/src/Pages/Load/index.vue'

const attrs = useAttrs()
</script>


