<template>
  <div id="app" class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <router-view />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from './stores/auth'

const authStore = useAuthStore()

// アプリケーション起動時にユーザー情報を取得
onMounted(async () => {
  // localStorageにトークンが存在する場合はユーザー情報を復元
  const token = localStorage.getItem('token')
  if (token) {
    await authStore.fetchUser()
  }
})
</script>

<style>
/* グローバルスタイルは app.css で管理 */
</style>
