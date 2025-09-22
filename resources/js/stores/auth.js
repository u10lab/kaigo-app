import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

// defineStoreで構成
export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(null)
  const isInitialized = ref(false)
  
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
      const errorMessage = error.response?.data?.message || 
                          error.response?.data?.error || 
                          'ログインに失敗しました。メールアドレスとパスワードを確認してください。'
      return { success: false, message: errorMessage }
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
      user.value = response.data.user
      return { success: true, user: response.data.user }
    } catch (error) {
      console.error('Fetch user failed:', error)
      // トークンが無効な場合はログアウト
      if (error.response?.status === 401) {
        logout()
      }
      throw error
    }
  }

  // 初期化処理: localStorageにトークンが存在する場合はユーザーデータを取得
  const initializeAuth = async () => {
    const storedToken = localStorage.getItem('token')
    if (storedToken) {
      token.value = storedToken
      try {
        await fetchUser()
        console.log('Auth initialized successfully:', user.value)
      } catch (error) {
        // トークンが無効な場合は削除
        console.error('Token validation failed:', error)
        logout()
      }
    }
    isInitialized.value = true
  }

  // 初期化を実行（非同期）
  initializeAuth()

  return {
    user,
    token,
    isInitialized,
    isAuthenticated,
    isAdmin,
    isHelper,
    login,
    logout,
    fetchUser
  }
})