import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const usePaidLeaveStore = defineStore('paidLeave', () => {
  const paidLeaveRecords = ref([])
  const userPaidLeaveStatus = ref({})
  const allEmployeesLeaveStatus = ref([])

  const fetchPaidLeaveStatus = async (userId) => {
    try {
      const response = await api.get(`/paid-leave/${userId}`)
      userPaidLeaveStatus.value = response.data
      return response.data
    } catch (error) {
      console.error('Fetch paid leave status failed:', error)
      // 現在はモックデータを返す
      const mockData = {
        user_id: userId,
        total_days: 10,
        used_days: 3,
        remaining_days: 7,
        annual_requirement_used: 2,
        annual_requirement_remaining: 3,
        usage_history: [
          { date: '2024-12-15', reason: '体調不良', status: 'approved' },
          { date: '2024-11-20', reason: '私用', status: 'approved' },
          { date: '2024-10-05', reason: '家族の用事', status: 'approved' }
        ]
      }
      userPaidLeaveStatus.value = mockData
      return mockData
    }
  }

  const fetchAllEmployeesLeaveStatus = async () => {
    try {
      const response = await api.get('/paid-leave/all')
      allEmployeesLeaveStatus.value = response.data
      return response.data
    } catch (error) {
      console.error('Fetch all employees leave status failed:', error)
      // 現在はモックデータを返す
      const mockData = [
        {
          user_id: 1,
          name: '山田太郎',
          total_days: 10,
          used_days: 3,
          remaining_days: 7,
          annual_requirement_used: 2,
          annual_requirement_remaining: 3
        },
        {
          user_id: 2,
          name: '田中花子',
          total_days: 10,
          used_days: 5,
          remaining_days: 5,
          annual_requirement_used: 4,
          annual_requirement_remaining: 1
        }
      ]
      allEmployeesLeaveStatus.value = mockData
      return mockData
    }
  }

  const usePaidLeave = async (userId, date, reason) => {
    try {
      const response = await api.post('/paid-leave/use', {
        user_id: userId,
        date,
        reason
      })
      // Refresh the status after using paid leave
      // 有給使用後にステータスを更新
      await fetchPaidLeaveStatus(userId)
      return response.data
    } catch (error) {
      console.error('Use paid leave failed:', error)
      throw error
    }
  }

  return {
    paidLeaveRecords,
    userPaidLeaveStatus,
    allEmployeesLeaveStatus,
    fetchPaidLeaveStatus,
    fetchAllEmployeesLeaveStatus,
    usePaidLeave
  }
})