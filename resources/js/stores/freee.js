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
      // freee認証未完了時はローカルで勤務状態を管理
      if (error.response?.status === 400 && error.response?.data?.error?.includes('認証が完了していません')) {
        console.log('Freee authentication not completed, using local work status')
        workStatus.value = 'working'
        workStartTime.value = new Date().toISOString()
        return { message: '勤務開始（ローカル管理）', local: true }
      }
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
      // freee認証未完了時はローカルで勤務状態を管理
      if (error.response?.status === 400 && error.response?.data?.error?.includes('認証が完了していません')) {
        console.log('Freee authentication not completed, using local work status')
        workStatus.value = 'finished'
        workStartTime.value = null
        return { message: '勤務終了（ローカル管理）', local: true }
      }
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
      // freee認証未完了時はモックデータを返す
      if (error.response?.status === 400 && error.response?.data?.error?.includes('認証が完了していません')) {
        console.log('Freee authentication not completed, using mock work records')
        const mockRecords = [
          { date: '2025-01-15', start_time: '09:00', end_time: '17:00', break_time: 60, work_time: 420 }
        ]
        workRecords.value = mockRecords
        return { records: mockRecords, local: true }
      }
      throw error
    }
  }

  const fetchPayrollData = async (month) => {
    try {
      const response = await api.get('/freee/payroll', { params: { month } })
      return response.data
    } catch (error) {
      console.error('Fetch payroll data failed:', error)
      // freee認証未完了時はモックデータを返す
      if (error.response?.status === 400 && error.response?.data?.error?.includes('認証が完了していません')) {
        console.log('Freee authentication not completed, using mock payroll data')
        return {
          data: {
            base_salary: 160000,
            total_hours: 160,
            overtime_hours: 0
          },
          local: true
        }
      }
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
    fetchWorkRecords,
    fetchPayrollData
  }
})