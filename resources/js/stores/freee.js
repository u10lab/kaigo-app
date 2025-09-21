import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const useFreeeStore = defineStore('freee', () => {
  const isAuthenticated = ref(false)
  const workStatus = ref('idle') // 'idle'(未出勤), 'working'(勤務中), 'on_service'(サービス中), 'finished'(勤務終了)
  const workStartTime = ref(null)
  const workRecords = ref([])

  const getAuthUrl = async () => {
    try {
      const response = await api.get('/freee/auth-url')
      return response.data.auth_url
    } catch (error) {
      console.error('Get freee auth URL failed:', error)
      throw error
    }
  }

  const handleCallback = async (code) => {
    try {
      const response = await api.get(`/freee/callback?code=${code}`)
      isAuthenticated.value = response.data.success
      return response.data
    } catch (error) {
      console.error('Freee callback failed:', error)
      throw error
    }
  }

  const clockIn = async () => {
    try {
      const response = await api.post('/freee/clock-in')
      workStatus.value = 'working'
      workStartTime.value = new Date().toISOString()
      return response.data
    } catch (error) {
      console.error('Clock in failed:', error)
      throw error
    }
  }

  const clockOut = async () => {
    try {
      const response = await api.post('/freee/clock-out')
      workStatus.value = 'finished'
      workStartTime.value = null
      return response.data
    } catch (error) {
      console.error('Clock out failed:', error)
      throw error
    }
  }

  const fetchWorkRecords = async (month) => {
    try {
      const response = await api.get('/freee/work-records', { params: { month } })
      workRecords.value = response.data.records
      return response.data
    } catch (error) {
      console.error('Fetch work records failed:', error)
      throw error
    }
  }

  return {
    isAuthenticated,
    workStatus,
    workStartTime,
    workRecords,
    getAuthUrl,
    handleCallback,
    clockIn,
    clockOut,
    fetchWorkRecords
  }
})