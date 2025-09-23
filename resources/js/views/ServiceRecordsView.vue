<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-200">
      <Navigation />
      <div class="absolute inset-0 bg-slate-900 bg-opacity-5"></div>
      
      <div class="relative z-10 max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
          <div class="flex justify-between items-center mb-8">
            <h1 class="text-4xl font-bold text-slate-800">
              <span class="bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent">
                サービス記録
              </span>
            </h1>
            
            <!-- Filters -->
            <div class="flex items-center space-x-4">
              <select
                v-model="filters.month"
                @change="loadServiceRecords"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-blue focus:border-primary-blue"
              >
                <option v-for="month in months" :key="month.value" :value="month.value">
                  {{ month.label }}
                </option>
              </select>
              
              <select
                v-model="filters.clientId"
                @change="loadServiceRecords"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-blue focus:border-primary-blue"
              >
                <option value="">全利用者</option>
                <option v-for="client in clients" :key="client.id" :value="client.id">
                  {{ client.name }}
                </option>
              </select>
              
              <select
                v-model="filters.serviceType"
                @change="loadServiceRecords"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-blue focus:border-primary-blue"
              >
                <option value="">全サービス</option>
                <option v-for="serviceType in serviceTypes" :key="serviceType.id" :value="serviceType.id">
                  {{ serviceType.name }}
                </option>
              </select>
            </div>
          </div>
  
          <!-- Summary Stats -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="card-enhanced overflow-hidden rounded-2xl hover:scale-105 transition-transform duration-300">
              <div class="p-5">
                <dt class="text-sm font-medium text-text-secondary truncate">
                  総サービス件数
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-text-primary">
                  {{ totalRecords }}件
                </dd>
              </div>
            </div>
            
            <div class="card-enhanced overflow-hidden rounded-2xl hover:scale-105 transition-transform duration-300">
              <div class="p-5">
                <dt class="text-sm font-medium text-text-secondary truncate">
                  総サービス時間
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-primary-blue">
                  {{ totalHours }}時間
                </dd>
              </div>
            </div>
            
            <div class="card-enhanced overflow-hidden rounded-2xl hover:scale-105 transition-transform duration-300">
              <div class="p-5">
                <dt class="text-sm font-medium text-text-secondary truncate">
                  総金額
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-primary-green">
                  ¥{{ totalAmount.toLocaleString() }}
                </dd>
              </div>
            </div>
            
            <div class="card-enhanced overflow-hidden rounded-2xl hover:scale-105 transition-transform duration-300">
              <div class="p-5">
                <dt class="text-sm font-medium text-text-secondary truncate">
                  平均サービス時間
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-text-primary">
                  {{ averageHours.toFixed(1) }}h
                </dd>
              </div>
            </div>
          </div>
  
          <!-- Service Records Table -->
          <div class="card-enhanced shadow overflow-hidden rounded-2xl">
            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
              <h2 class="text-lg leading-6 font-medium text-text-primary">
                サービス実施記録 {{ filters.month === 'all' ? '(全期間)' : `(${formatMonth(filters.month)})` }}
              </h2>
              <button
                @click="exportToCsv"
                class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors"
              >
                CSV出力
              </button>
            </div>
            
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                      実施日
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                      従業員
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                      利用者
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                      サービス種別
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                      開始時間
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                      終了時間
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                      時間数
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                      時給
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                      金額
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="record in filteredRecords" :key="record.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                      {{ formatDate(record.start_time) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                      {{ record.employee_name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text-primary">
                      {{ record.client_name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                      {{ record.service_type }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                      {{ formatTime(record.start_time) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                      {{ formatTime(record.end_time) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                      {{ (record.duration / 60).toFixed(1) }}h
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                      ¥{{ record.hourly_rate?.toLocaleString() }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-primary-green">
                      ¥{{ ((record.duration / 60) * record.hourly_rate).toLocaleString() }}
                    </td>
                  </tr>
                </tbody>
              </table>
              
              <div v-if="filteredRecords.length === 0" class="text-center py-12">
                <p class="text-text-secondary">該当するサービス記録がありません</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted } from 'vue'
  import { useServiceStore } from '../stores/service'
  import Navigation from '../components/Navigation.vue'
  
  const serviceStore = useServiceStore()
  
  const filters = ref({
    month: 'all',
    clientId: '',
    serviceType: ''
  })
  
  const serviceRecords = ref([])
  
  const months = [
    { value: 'all', label: '全期間' },
    { value: '2025-01', label: '2025年1月' },
    { value: '2024-12', label: '2024年12月' },
    { value: '2024-11', label: '2024年11月' },
    { value: '2024-10', label: '2024年10月' },
    { value: '2024-09', label: '2024年9月' },
    { value: '2024-08', label: '2024年8月' },
    { value: '2024-07', label: '2024年7月' }
  ]
  
  const clients = computed(() => serviceStore.clients)
  const serviceTypes = computed(() => serviceStore.serviceTypes)
  
  const filteredRecords = computed(() => {
    let records = serviceRecords.value
    
    if (filters.value.clientId) {
      const client = clients.value.find(c => c.id === filters.value.clientId)
      records = records.filter(record => record.client_name === client.name)
    }
    
    if (filters.value.serviceType) {
      const serviceType = serviceTypes.value.find(st => st.id === filters.value.serviceType)
      records = records.filter(record => record.service_type === serviceType.name)
    }
    
    return records
  })
  
  const totalRecords = computed(() => filteredRecords.value.length)
  
  const totalHours = computed(() => {
    return filteredRecords.value.reduce((sum, record) => sum + (record.duration / 60), 0)
  })
  
  const totalAmount = computed(() => {
    return filteredRecords.value.reduce((sum, record) => 
      sum + ((record.duration / 60) * record.hourly_rate), 0)
  })
  
  const averageHours = computed(() => {
    if (totalRecords.value === 0) return 0
    return totalHours.value / totalRecords.value
  })
  
  const formatDate = (dateTimeString) => {
    return new Date(dateTimeString).toLocaleDateString('ja-JP', {
      month: 'short',
      day: 'numeric'
    })
  }
  
  const formatTime = (dateTimeString) => {
    return new Date(dateTimeString).toLocaleTimeString('ja-JP', {
      hour: '2-digit',
      minute: '2-digit'
    })
  }
  
  const formatMonth = (monthString) => {
    const [year, month] = monthString.split('-')
    return `${year}年${month}月`
  }
  
  const errorMessage = ref('')
  const loading = ref(false)

  const loadServiceRecords = async () => {
    loading.value = true
    errorMessage.value = ''

    try {
      if (filters.value.month === 'all') {
        // 全期間の場合は月指定なしで取得
        const response = await serviceStore.fetchServiceRecords()
        serviceRecords.value = response.records || []
      } else {
        // 特定月の取得
        const response = await serviceStore.fetchServiceRecords(filters.value.month)
        serviceRecords.value = response.records || []
      }
    } catch (error) {
      console.error('Failed to load service records:', error)
      errorMessage.value = error.message || 'サービス記録の取得に失敗しました'
      serviceRecords.value = []
    } finally {
      loading.value = false
    }
  }
  
  const exportToCsv = () => {
    const headers = ['実施日', '利用者', 'サービス種別', '開始時間', '終了時間', '時間数', '時給', '金額']
    const rows = filteredRecords.value.map(record => [
      formatDate(record.start_time),
      record.client_name,
      record.service_type,
      formatTime(record.start_time),
      formatTime(record.end_time),
      (record.duration / 60).toFixed(1),
      record.hourly_rate,
      ((record.duration / 60) * record.hourly_rate).toFixed(0)
    ])
    
    const csvContent = [headers.join(','), ...rows.map(row => row.join(','))].join('\n')
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `service_records_${filters.value.month.replace('-', '')}.csv`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  }
  
  onMounted(async () => {
    await serviceStore.fetchClients()
    await serviceStore.fetchServiceTypes()
    loadServiceRecords()
  })
  </script>