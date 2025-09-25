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
   * 申請一覧を取得
   */
  const fetchApplications = async (filters = {}) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.get('/paid-leave/applications', { params: filters })
      
      if (response.data.success) {
        applications.value = response.data.data
        return response.data.data
      } else {
        throw new Error(response.data.message || '申請一覧の取得に失敗しました')
      }
    } catch (err) {
      console.error('Fetch applications failed:', err)
      error.value = err.message || '申請一覧の取得に失敗しました'
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
   * freeeから従業員マスタを同期
   */
  const syncEmployeesFromFreee = async () => {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/paid-leave/sync-employees-from-freee')
      
      if (response.data.success) {
        return response.data.data
      } else {
        throw new Error(response.data.message || 'freeeからの従業員マスタ同期に失敗しました')
      }
    } catch (err) {
      console.error('Sync employees from freee failed:', err)
      const errorMessage = err.response?.data?.message || err.message || 'freeeからの従業員マスタ同期に失敗しました'
      error.value = errorMessage
      
      // アクセストークン期限切れの場合は特別なメッセージを表示
      if (errorMessage.includes('アクセストークン') || errorMessage.includes('認証')) {
        error.value = 'freee APIの認証が期限切れです。管理者画面でfreee認証を再実行してください。'
      }
      
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * 有給休暇申請を承認してfreeeに登録
   */
  const approveAndRegisterToFreee = async (userId, date) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/paid-leave/approve-and-register-to-freee', {
        user_id: userId,
        date: date
      })
      
      if (response.data.success) {
        return response.data.data
      } else {
        throw new Error(response.data.message || 'freeeへの登録に失敗しました')
      }
    } catch (err) {
      console.error('Approve and register to freee failed:', err)
      error.value = err.message || 'freeeへの登録に失敗しました'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * 正確な残日数を取得（freee実績を含む）
   */
  const fetchAccurateRemainingDays = async (userId = null, fiscalYear = null) => {
    loading.value = true
    error.value = null

    try {
      const params = {}
      if (userId) params.user_id = userId
      if (fiscalYear) params.fiscal_year = fiscalYear

      const response = await api.get('/paid-leave/accurate-remaining-days', { params })
      
      if (response.data.success) {
        return response.data.data
      } else {
        throw new Error(response.data.message || '正確な残日数の取得に失敗しました')
      }
    } catch (err) {
      console.error('Fetch accurate remaining days failed:', err)
      error.value = err.message || '正確な残日数の取得に失敗しました'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * freeeから有給休暇実績を取得
   */
  const fetchPaidLeaveRecordsFromFreee = async (userId = null, year = null) => {
    loading.value = true
    error.value = null

    try {
      const params = {}
      if (userId) params.user_id = userId
      if (year) params.year = year

      const response = await api.get('/paid-leave/records-from-freee', { params })
      
      if (response.data.success) {
        return response.data.data
      } else {
        throw new Error(response.data.message || 'freeeからの有給休暇実績取得に失敗しました')
      }
    } catch (err) {
      console.error('Fetch paid leave records from freee failed:', err)
      error.value = err.message || 'freeeからの有給休暇実績取得に失敗しました'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * freee API経由で有給休暇申請を作成
   */
  const applyPaidLeaveViaFreee = async (applicationData) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/paid-leave/apply-via-freee', applicationData)
      if (response.data.success) {
        // 申請一覧を再取得
        await fetchApplications()
        return response.data
      } else {
        throw new Error(response.data.message || '有給休暇申請に失敗しました')
      }
    } catch (err) {
      console.error('Apply paid leave via freee failed:', err)
      error.value = err.response?.data?.message || err.message || '有給休暇申請に失敗しました'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * freee API経由で有給休暇申請を承認
   */
  const approveApplicationViaFreee = async (applicationId, comment = '') => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/paid-leave/approve', {
        application_id: applicationId,
        approver_comment: comment
      })
      if (response.data.success) {
        // 申請一覧を再取得
        await fetchApplications()
        return response.data
      } else {
        throw new Error(response.data.message || '申請承認に失敗しました')
      }
    } catch (err) {
      console.error('Approve application via freee failed:', err)
      error.value = err.response?.data?.message || err.message || '申請承認に失敗しました'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * freee API経由で有給休暇申請を却下
   */
  const rejectApplicationViaFreee = async (applicationId, reason) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/paid-leave/reject-via-freee', {
        application_id: applicationId,
        rejection_reason: reason
      })
      if (response.data.success) {
        // 申請一覧を再取得
        await fetchApplications()
        return response.data
      } else {
        throw new Error(response.data.message || '申請却下に失敗しました')
      }
    } catch (err) {
      console.error('Reject application via freee failed:', err)
      error.value = err.response?.data?.message || err.message || '申請却下に失敗しました'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * freee APIから有給休暇申請を同期
   */
  const syncApplicationsFromFreee = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/paid-leave/sync-applications-from-freee')
      if (response.data.success) {
        // 申請一覧を再取得
        await fetchApplications()
        return response.data
      } else {
        throw new Error(response.data.message || '申請同期に失敗しました')
      }
    } catch (err) {
      console.error('Sync applications from freee failed:', err)
      error.value = err.response?.data?.message || err.message || '申請同期に失敗しました'
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
    fetchApplications,
    approveApplication,
    rejectApplication,
    fetchAnnualLeaveCheck,
    fetchAllEmployeesLeaveStatus,
    // freee API統合機能
    applyPaidLeaveViaFreee,
    approveApplicationViaFreee,
    rejectApplicationViaFreee,
    syncApplicationsFromFreee,
    syncEmployeesFromFreee,
    approveAndRegisterToFreee,
    fetchAccurateRemainingDays,
    fetchPaidLeaveRecordsFromFreee,
    clearError
  }
})
