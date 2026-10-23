<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: String, required: true },
  sub: { type: String, default: '' },
  tone: { type: String, default: '' },
})
</script>

<template>
  <div class="stat">
    <p class="stat-label">{{ label }}</p>
    <p class="stat-value num" :class="tone">{{ value }}</p>
    <p v-if="sub" class="stat-sub num" :class="tone">{{ sub }}</p>
  </div>
</template>
