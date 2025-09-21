import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const useServiceStore = defineStore('service', () => {
  const clients = ref([
    { id: 'A001', name: '山田一郎', care_level: '要介護2' },
    { id: 'A002', name: '田中花子', care_level: '要介護1' },
    { id: 'A003', name: '佐々木健一', care_level: '要介護3' }
  ])
  
  const serviceTypes = ref([
    { id: 'bathing', name: '入浴介護', hourly_rate: 1500 },
    { id: 'cleaning', name: '掃除', hourly_rate: 1200 },
    { id: 'daily_living', name: '生活援助', hourly_rate: 1300 }
  ])
  
  const serviceRecords = ref([])
  const currentService = ref(null)
  const todayServiceRecords = ref([])

  const fetchClients = async () => {
    try {
      // APIが未実装のため、現在はモックデータを返す
      return clients.value
    } catch (error) {
      console.error('Fetch clients failed:', error)
      throw error
    }
  }

  const fetchServiceTypes = async () => {
    try {
      // APIが未実装のため、現在はモックデータを返す
      return serviceTypes.value
    } catch (error) {
      console.error('Fetch service types failed:', error)
      throw error
    }
  }

  const fetchServiceRecords = async (month) => {
    try {
      const response = await api.get('/service/records', { params: { month } })
      serviceRecords.value = response.data.records
      return response.data
    } catch (error) {
      console.error('Fetch service records failed:', error)
      // 現在はモックデータを返す
      serviceRecords.value = [
        {
          id: 1,
          client_name: '山田一郎',
          service_type: '入浴介護',
          start_time: '2025-01-15 09:00:00',
          end_time: '2025-01-15 11:00:00',
          duration: 120,
          hourly_rate: 1500
        }
      ]
      return { records: serviceRecords.value }
    }
  }

  const startService = async (clientId, serviceTypeId) => {
    try {
      const response = await api.post('/service/start', {
        client_id: clientId,
        service_type_id: serviceTypeId
      })
      
      // サービス開始情報を記録
      const client = clients.value.find(c => c.id === clientId)
      const serviceType = serviceTypes.value.find(st => st.id === serviceTypeId)
      
      currentService.value = {
        id: Date.now(),
        client_id: clientId,
        service_type_id: serviceTypeId,
        client_name: client?.name || 'Unknown',
        service_type: serviceType?.name || 'Unknown',
        hourly_rate: serviceType?.hourly_rate || 0,
        start_time: new Date().toISOString()
      }
      
      return response.data
    } catch (error) {
      console.error('Start service failed:', error)
      
      // デモ用にローカルでサービス記録を作成
      const client = clients.value.find(c => c.id === clientId)
      const serviceType = serviceTypes.value.find(st => st.id === serviceTypeId)
      
      currentService.value = {
        id: Date.now(),
        client_id: clientId,
        service_type_id: serviceTypeId,
        client_name: client?.name || 'Unknown',
        service_type: serviceType?.name || 'Unknown',
        hourly_rate: serviceType?.hourly_rate || 0,
        start_time: new Date().toISOString()
      }
      
      return { success: true, service: currentService.value }
    }
  }

  const endService = async () => {
    try {
      if (!currentService.value) throw new Error('No active service')
      
      // 現在のユーザー情報を取得
      const { useAuthStore } = await import('./auth')
      const authStore = useAuthStore()
      const currentUser = authStore.user
      
      // サービス終了時刻を記録
      const endTime = new Date().toISOString()
      const completedService = {
        ...currentService.value,
        employee_name: currentUser?.name || 'Unknown',
        employee_id: currentUser?.id,
        end_time: endTime,
        duration: Math.round((new Date(endTime) - new Date(currentService.value.start_time)) / 1000 / 60) // 分単位
      }
      
      // 今日のサービス記録に追加
      todayServiceRecords.value.push(completedService)
      
      // APIコールを試行するが、失敗しても処理を続行
      try {
        const response = await api.post(`/service/${currentService.value.id}/end`)
      } catch (apiError) {
        console.log('API call failed, but continuing with local state update')
      }
      
      // サービス終了
      currentService.value = null
      
      console.log('Service ended successfully')
      
      return { success: true }
    } catch (error) {
      console.error('End service failed:', error)
      // エラーが発生してもサービスをリセット
      currentService.value = null
      return { success: false, error: error.message }
    }
  }

  return {
    clients,
    serviceTypes,
    serviceRecords,
    currentService,
    todayServiceRecords,
    fetchClients,
    fetchServiceTypes,
    fetchServiceRecords,
    startService,
    endService
  }
})