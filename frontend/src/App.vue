<script setup>
import AppHeader from './components/AppHeader.vue'
import { isAuthenticated } from './stores/auth'
</script>

<template>
  <AppHeader v-if="isAuthenticated" />
  <main class="page">
    <RouterView />
  </main>
</template>
