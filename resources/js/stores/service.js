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
      const response = await api.get('/service/clients')
      clients.value = response.data.data
      return clients.value
    } catch (error) {
      console.error('Fetch clients failed:', error)
      // API失敗時はモックデータを使用
      console.log('Using mock data for clients')
      return clients.value
    }
  }

  const fetchServiceTypes = async () => {
    try {
      const response = await api.get('/service/service-types')
      serviceTypes.value = response.data.data
      return serviceTypes.value
    } catch (error) {
      console.error('Fetch service types failed:', error)
      // API失敗時はモックデータを使用
      console.log('Using mock data for service types')
      return serviceTypes.value
    }
  }

  const fetchServiceRecords = async (month) => {
    try {
      const params = month ? { month } : {}
      const response = await api.get('/service/records', { params })

      // APIレスポンスの形式を確認して適切に処理
      if (response.data.success && response.data.data) {
        // ページネーション対応
        const records = response.data.data.data || response.data.data
        serviceRecords.value = records.map(record => ({
          id: record.id,
          employee_name: record.user?.name || 'Unknown',
          client_name: record.client?.name || 'Unknown',
          service_type: record.service_type?.name || 'Unknown',
          start_time: record.service_start_time,
          end_time: record.service_end_time,
          duration: record.service_minutes || 0,
          hourly_rate: record.service_type?.hourly_rate || 0
        }))
        return { records: serviceRecords.value }
      } else {
        throw new Error('Invalid API response format')
      }
    } catch (error) {
      console.error('Fetch service records failed:', error)
      // エラーを再投げして呼び出し元で処理
      throw new Error(`サービス記録の取得に失敗しました: ${error.message}`)
    }
  }

  const fetchCurrentServiceStatus = async () => {
    try {
      const response = await api.get('/service/current-status')
      if (response.data.success && response.data.data) {
        currentService.value = response.data.data
        return response.data.data
      }
      return null
    } catch (error) {
      console.error('Fetch current service status failed:', error)
      return null
    }
  }

  // 現在のサービス状況を確認
  const checkCurrentService = async () => {
    try {
      const response = await api.get('/service/current-status')
      return response.data
    } catch (error) {
      console.error('Failed to check current service:', error)
      return null
    }
  }

  const startService = async (clientId, serviceTypeId) => {
    try {
      // 既存のサービスがあるかチェック
      const currentServiceCheck = await checkCurrentService()
      if (currentServiceCheck?.success && currentServiceCheck.data) {
        throw new Error('既にサービスが開始されています。先にサービスを終了してください。')
      }

      const requestData = {
        client_id: parseInt(clientId),
        service_type_id: parseInt(serviceTypeId)
      }
      console.log('Service start request data:', requestData)
      
      const response = await api.post('/service/start', requestData)
      
      if (response.data.success) {
        // API成功時: サーバーから返されたデータをそのまま使用
        currentService.value = response.data.data
        console.log('Service started - currentService set to:', currentService.value)
      }
      
      return response.data
    } catch (error) {
      console.error('Start service failed:', error)
      console.error('Error response:', error.response?.data)
      console.error('Error status:', error.response?.status)
      
      // 既存の未終了サービスがある場合のエラーハンドリング
      if (error.response?.status === 400 && error.response?.data?.message?.includes('既にサービスが開始されています')) {
        throw new Error('既にサービスが開始されています。先にサービスを終了してください。')
      }
      
      // API失敗時: ローカルでサービス記録を作成
      const client = clients.value.find(c => c.id === clientId)
      const serviceType = serviceTypes.value.find(st => st.id === serviceTypeId)
      
      currentService.value = {
        id: 'local_' + Date.now(), // ローカル用のID
        client_id: clientId,
        service_type_id: serviceTypeId,
        client_name: client?.name || 'Unknown',
        service_type: serviceType?.name || 'Unknown',
        hourly_rate: serviceType?.hourly_rate || 0,
        start_time: new Date().toISOString()
      }
      
      return { success: true, data: currentService.value }
    }
  }

  const endService = async () => {
    try {
      if (!currentService.value) throw new Error('No active service')
      
      // APIコールを試行
      try {
        // ローカルIDの場合はAPIコールをスキップ
        if (currentService.value.id.toString().startsWith('local_')) {
          throw new Error('Local service, skip API call')
        }
        
        // データベースのIDを使用（APIから返されたID）
        const serviceId = currentService.value.id
        const response = await api.post(`/service/${serviceId}/end`)
        
        if (response.data.success) {
          // API成功時: サーバーから返されたデータを使用
          const completedService = {
            ...currentService.value,
            end_time: response.data.data.service_end_time,
            duration: response.data.data.service_minutes
          }
          
          // 今日のサービス記録に追加
          todayServiceRecords.value.push(completedService)
        }
        
        // サービス終了
        currentService.value = null
        
        console.log('Service ended successfully via API')
        return response.data
        
      } catch (apiError) {
        console.log('API call failed, using local calculation')
        
        // API失敗時: ローカルで計算
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
        
        // サービス終了
        currentService.value = null
        
        console.log('Service ended successfully locally')
        return { success: true, data: completedService }
      }
      
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
    fetchCurrentServiceStatus,
    checkCurrentService,
    startService,
    endService
  }
})