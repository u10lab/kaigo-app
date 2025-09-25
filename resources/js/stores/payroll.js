import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const usePayrollStore = defineStore('payroll', () => {
  const payrollData = ref({})
  const serviceAdjustments = ref([])
  const employees = ref([])

  const fetchEmployees = async () => {
    try {
      const response = await api.get('/users')
      employees.value = response.data // 既にサーバーサイドでフィルタリング済み
      console.log('Employees fetched from database:', employees.value)
      return employees.value
    } catch (error) {
      console.error('Fetch employees failed:', error)
      employees.value = []
      throw error
    }
  }

  const fetchPayrollData = async (month, userId) => {
    try {
      const response = await api.get('/payroll/calculate', {
        params: { month, user_id: userId }
      })
      payrollData.value = response.data
      return response.data
    } catch (error) {
      console.error('Fetch payroll data failed:', error)
      throw error
    }
  }

  const recalculatePayroll = async (month, userId) => {
    try {
      const response = await api.post('/payroll/recalculate', { month, user_id: userId })
      return response.data
    } catch (error) {
      console.error('Recalculate payroll failed:', error)
      throw error
    }
  }

  const fetchAllPayrollData = async (month) => {
    console.log('=== PayrollStore: fetchAllPayrollData 開始 ===')
    console.log('対象月:', month)

    try {
      // まず従業員データを取得
      console.log('Step 1: 従業員データ確認...')
      if (employees.value.length === 0) {
        console.log('従業員データが空のため取得開始...')
        await fetchEmployees()
      }
      console.log('従業員データ:', employees.value)

      const payrollList = []
      console.log('Step 2: 各従業員の給与計算開始...')

      for (const employee of employees.value) {
        console.log(`--- 従業員 ${employee.name} (ID: ${employee.id}) の給与計算 ---`)
        try {
          const data = await fetchPayrollData(month, employee.id)
          console.log(`${employee.name}の給与データ:`, data)
          payrollList.push(data)
        } catch (error) {
          console.error(`従業員 ${employee.id} の給与取得失敗:`, error)
          console.error('エラー詳細:', {
            message: error.message,
            response: error.response?.data,
            status: error.response?.status
          })

          // エラーの場合は基本情報のみ追加
          const errorData = {
            user_id: employee.id,
            employee_name: employee.name,
            employee_id: employee.employee_id || 'N/A',
            month: month,
            base_salary: 0,
            overtime_pay: 0,
            service_adjustments: 0,
            total_salary: 0,
            total_hours: 0,
            overtime_hours: 0,
            base_hourly_rate: 0,
            service_breakdown: [],
            error: error.message || '給与データの取得に失敗しました'
          }
          payrollList.push(errorData)
          console.log(`${employee.name}のエラーデータ:`, errorData)
        }
      }

      console.log('Step 3: 全従業員の給与計算完了')
      console.log('最終結果:', payrollList)
      console.log('=== PayrollStore: fetchAllPayrollData 完了 ===')

      return payrollList
    } catch (error) {
      console.error('fetchAllPayrollData 全体エラー:', error)
      throw error
    }
  }

  return {
    payrollData,
    serviceAdjustments,
    employees,
    fetchEmployees,
    fetchPayrollData,
    recalculatePayroll,
    fetchAllPayrollData
  }
})