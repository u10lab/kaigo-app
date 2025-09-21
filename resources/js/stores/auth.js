import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

// defineStoreで構成
export const useAuthStore = defineStore('auth', () => {
  const user = ref({ id: 1, email: 'admin@test.com', name: '管理者', role: 'admin' })
  const token = ref('mock-admin-token')
  
  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isHelper = computed(() => user.value?.role === 'helper')

  const login = async (email, password) => {
    try {
      const response = await api.post('/login', { email, password })
      const { token: authToken, user: userData } = response.data
      
      token.value = authToken
      user.value = userData
      localStorage.setItem('token', authToken)
      
      return { success: true, user: userData }
    } catch (error) {
      console.error('Login failed:', error)
      return { success: false, message: error.response?.data?.message || 'Login failed' }
    }
  }

  const logout = async () => {
    try {
      await api.post('/logout')
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
    }
  }

  const fetchUser = async () => {
    try {
      const response = await api.get('/user')
      user.value = response.data
    } catch (error) {
      console.error('Fetch user failed:', error)
      logout()
    }
  }

  // localStorageにトークンが存在する場合はユーザーデータを初期化
  const storedToken = localStorage.getItem('token')
  if (storedToken) {
    token.value = storedToken
    // 実際のアプリではサーバーでトークンを検証する
    // デモ用にトークンに基づいてデフォルトユーザーを設定
    if (storedToken === 'mock-admin-token') {
      user.value = { id: 1, email: 'admin@test.com', name: '管理者', role: 'admin' }
    } else if (storedToken === 'mock-helper-token') {
      user.value = { id: 2, email: 'helper1@test.com', name: '山田太郎', role: 'helper' }
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    isAdmin,
    isHelper,
    login,
    logout,
    fetchUser
  }
})