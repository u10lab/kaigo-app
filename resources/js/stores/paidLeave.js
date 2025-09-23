import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const usePaidLeaveStore = defineStore('paidLeave', () => {
  const paidLeaveStatus = ref(null)
  const applications = ref([])
  const annualCheck = ref(null)
  const loading = ref(false)
  const error = ref(null)

  /**
   * 有給休暇状況を取得
   */
  const fetchPaidLeaveStatus = async (userId = null, fiscalYear = null) => {
    loading.value = true
    error.value = null

    try {
      const params = {}
      if (userId) params.user_id = userId
      if (fiscalYear) params.fiscal_year = fiscalYear

      const response = await api.get('/paid-leave/status', { params })
      
      if (response.data.success) {
        paidLeaveStatus.value = response.data.data
        return response.data.data
      } else {
        throw new Error(response.data.message || '有給休暇状況の取得に失敗しました')
      }
    } catch (err) {
      console.error('Fetch paid leave status failed:', err)
      error.value = err.message || '有給休暇状況の取得に失敗しました'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * 有給休暇申請
   */
  const applyPaidLeave = async (applicationData) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/paid-leave/apply', applicationData)
      
      if (response.data.success) {
        // 申請成功後、状況を再取得
        await fetchPaidLeaveStatus()
        return response.data
      } else {
        throw new Error(response.data.message || '有給休暇申請に失敗しました')
      }
    } catch (err) {
      console.error('Apply paid leave failed:', err)
      error.value = err.message || '有給休暇申請に失敗しました'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * 有給休暇申請を承認
   */
  const approveApplication = async (applicationId, comment = null) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/paid-leave/approve', {
        application_id: applicationId,
        approver_comment: comment
      })
      
      if (response.data.success) {
        return response.data
      } else {
        throw new Error(response.data.message || '申請承認に失敗しました')
      }
    } catch (err) {
      console.error('Approve application failed:', err)
      error.value = err.message || '申請承認に失敗しました'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * 有給休暇申請を却下
   */
  const rejectApplication = async (applicationId, reason) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/paid-leave/reject', {
        application_id: applicationId,
        rejection_reason: reason
      })
      
      if (response.data.success) {
        return response.data
      } else {
        throw new Error(response.data.message || '申請却下に失敗しました')
      }
    } catch (err) {
      console.error('Reject application failed:', err)
      error.value = err.message || '申請却下に失敗しました'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * 年次有給休暇取得義務の達成状況をチェック
   */
  const fetchAnnualLeaveCheck = async (fiscalYear = null) => {
    loading.value = true
    error.value = null

    try {
      const params = {}
      if (fiscalYear) params.fiscal_year = fiscalYear

      const response = await api.get('/paid-leave/annual-check', { params })
      
      if (response.data.success) {
        annualCheck.value = response.data.data
        return response.data.data
      } else {
        throw new Error(response.data.message || '年次有給休暇取得状況の確認に失敗しました')
      }
    } catch (err) {
      console.error('Fetch annual leave check failed:', err)
      error.value = err.message || '年次有給休暇取得状況の確認に失敗しました'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * 全従業員の有給休暇状況を取得
   */
  const fetchAllEmployeesLeaveStatus = async () => {
    loading.value = true
    error.value = null

    try {
      const response = await api.get('/paid-leave/all-employees-status')
      
      if (response.data.success) {
        return response.data.data
      } else {
        throw new Error(response.data.message || '全従業員の有給休暇状況の取得に失敗しました')
      }
    } catch (err) {
      console.error('Fetch all employees leave status failed:', err)
      error.value = err.message || '全従業員の有給休暇状況の取得に失敗しました'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * エラーをクリア
   */
  const clearError = () => {
    error.value = null
  }

  return {
    paidLeaveStatus,
    applications,
    annualCheck,
    loading,
    error,
    fetchPaidLeaveStatus,
    applyPaidLeave,
    approveApplication,
    rejectApplication,
    fetchAnnualLeaveCheck,
    fetchAllEmployeesLeaveStatus,
    clearError
  }
})
