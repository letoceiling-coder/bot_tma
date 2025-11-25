<template>
  <BaseMain v-bind="attrs">
    <slot />
  </BaseMain>
</template>

<script setup>
import { useAttrs } from 'vue'
import BaseMain from '/resources/js/src/Pages/Main/index.vue'

const attrs = useAttrs()
</script>


