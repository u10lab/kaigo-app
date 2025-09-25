<template>
    <div class="min-h-screen bg-bg-secondary flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-md w-full space-y-8">
        <div>
          <h2 class="mt-6 text-center text-3xl font-bold text-text-primary">
            ログイン
          </h2>
          <p class="mt-2 text-center text-sm text-text-secondary">
            ホームケアステーション業務改善アプリ
          </p>
        </div>
        
        <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
          <div class="rounded-md shadow-sm space-y-4">
            <div>
              <label for="email" class="block text-sm font-medium text-text-primary mb-2">
                メールアドレス
              </label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-text-primary rounded-md focus:outline-none focus:ring-primary-blue focus:border-primary-blue focus:z-10 sm:text-sm"
                placeholder="メールアドレスを入力"
              />
            </div>
            
            <div>
              <label for="password" class="block text-sm font-medium text-text-primary mb-2">
                パスワード
              </label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                required
                class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-text-primary rounded-md focus:outline-none focus:ring-primary-blue focus:border-primary-blue focus:z-10 sm:text-sm"
                placeholder="パスワードを入力"
              />
            </div>
          </div>
  
          <div v-if="error" class="text-red-600 text-sm text-center">
            {{ error }}
          </div>
  
          <div>
            <button
              type="submit"
              :disabled="loading"
              class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-blue hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-blue disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ loading ? 'ログイン中...' : 'ログイン' }}
            </button>
          </div>
  
        </form>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue'
  import { useAuthStore } from '../stores/auth'
  import { useRouter } from 'vue-router'
  
  const authStore = useAuthStore()
  const router = useRouter()
  
  const form = ref({
    email: '',
    password: ''
  })
  
  const loading = ref(false)
  const error = ref('')
  
  const handleLogin = async () => {
    loading.value = true
    error.value = ''
    
    try {
      const result = await authStore.login(form.value.email, form.value.password)
      
      if (result.success) {
        // Redirect based on user role
        if (result.user.role === 'admin') {
          router.push('/admin')
        } else {
          router.push('/helper')
        }
      } else {
        error.value = result.message || 'ログインに失敗しました'
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'ログインに失敗しました'
    } finally {
      loading.value = false
    }
  }
  </script>