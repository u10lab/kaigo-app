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
      workStatus.value = 'completed'
      workStartTime.value = null
      return response.data
    } catch (error) {
      console.error('Clock out failed:', error)
      // freee認証未完了時はローカルで勤務状態を管理
      if (error.response?.status === 400 && error.response?.data?.error?.includes('認証が完了していません')) {
        console.log('Freee authentication not completed, using local work status')
        workStatus.value = 'completed'
        workStartTime.value = null
        return { message: '勤務終了（ローカル管理）', local: true }
      }
      throw error
    }
  }

  const fetchWorkRecords = async (month) => {
    try {
      // 新しいサービス記録ベースの勤務状態APIを使用
      const response = await api.get('/service/work-status')
      
      if (response.data.success) {
        const data = response.data.data
        
        // 勤務状態を設定
        workStatus.value = data.work_status
        workStartTime.value = data.work_start_time
        
        // 勤務記録を設定（サービス記録から変換）
        workRecords.value = data.today_services || []
        
        console.log('Work status loaded from service records:', data)
        return { records: data.today_services || [], local: true }
      } else {
        throw new Error(response.data.error || 'Failed to get work status')
      }
    } catch (error) {
      console.error('Fetch work records failed:', error)
      // エラーが発生した場合は空のデータを返し、状態はidleに設定
      workRecords.value = []
      workStatus.value = 'idle'
      workStartTime.value = null
      
      console.log('Work records fetch failed, but continuing with empty data')
      return { records: [], local: true }
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

  // 従業員ID更新
  const updateEmployeeIds = async () => {
    try {
      console.log('Attempting to update employee IDs...')
      const response = await api.post('/freee/update-employee-ids')
      console.log('Employee ID update successful:', response.data)
      return response.data
    } catch (error) {
      console.error('Failed to update employee IDs:', error)
      console.error('Error response:', error.response?.data)
      throw error
    }
  }

  // 従業員一覧取得
  const fetchEmployees = async () => {
    try {
      const response = await api.get('/freee/employees')
      return response.data
    } catch (error) {
      console.error('Failed to fetch employees:', error)
      throw error
    }
  }

  // 認証状態チェック
  const checkAuthStatus = async () => {
    try {
      const response = await api.get('/freee/auth-status')
      return response.data
    } catch (error) {
      console.error('Failed to check auth status:', error)
      throw error
    }
  }

  // 従業員インポート
  const importEmployeesFromFreee = async () => {
    try {
      console.log('Attempting to import employees from freee...')
      const response = await api.post('/freee/import-employees')
      console.log('Employee import successful:', response.data)
      return response.data
    } catch (error) {
      console.error('Failed to import employees:', error)
      console.error('Error response:', error.response?.data)
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
    fetchPayrollData,
    updateEmployeeIds,
    fetchEmployees,
    checkAuthStatus,
    importEmployeesFromFreee
  }
})