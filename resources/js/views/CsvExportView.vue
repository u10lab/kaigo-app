<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-200">
      <Navigation />
      <div class="absolute inset-0 bg-slate-900 bg-opacity-5"></div>
      
      <div class="relative z-10 max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
          <h1 class="text-4xl font-bold text-slate-800 mb-8 text-center">
            <span class="bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent">
              CSV出力
            </span>
          </h1>
  
          <!-- Export Form -->
          <div class="card-enhanced shadow rounded-2xl mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-text-primary">介護保険請求用CSV出力</h2>
            </div>
            <div class="p-6">
              <form @submit.prevent="handleExport" class="space-y-6">
                <!-- Export Period -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">
                      開始月
                    </label>
                    <input
                      type="month"
                      v-model="exportForm.startMonth"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-blue focus:border-primary-blue"
                      required
                    >
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">
                      終了月
                    </label>
                    <input
                      type="month"
                      v-model="exportForm.endMonth"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-blue focus:border-primary-blue"
                      required
                    >
                  </div>
                </div>
  
                <!-- Export Type -->
                <div>
                  <label class="block text-sm font-medium text-text-primary mb-2">
                    出力形式
                  </label>
                  <select
                    v-model="exportForm.exportType"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-blue focus:border-primary-blue"
                    required
                  >
                    <option value="">出力形式を選択</option>
                    <option value="care_insurance">介護保険請求用</option>
                    <option value="service_summary">サービス実績一覧</option>
                    <option value="payroll_summary">給与計算用</option>
                  </select>
                </div>
  
                <!-- Export Button -->
                <div class="flex justify-end">
                  <button
                    type="submit"
                    :disabled="loading"
                    class="px-6 py-3 bg-primary-blue text-white rounded-md hover:bg-blue-700 disabled:opacity-50 transition-colors"
                  >
                    {{ loading ? 'エクスポート中...' : 'CSVエクスポート' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
  
          <!-- Export History -->
          <div class="card-enhanced shadow rounded-2xl">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-text-primary">エクスポート履歴</h2>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                      エクスポート日時
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                      対象期間
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                      出力形式
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                      ファイル名
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                      ステータス
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="(export_record, index) in exportHistory" :key="index">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                      {{ formatDateTime(export_record.created_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                      {{ formatPeriod(export_record.start_month, export_record.end_month) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                      {{ getExportTypeLabel(export_record.export_type) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-primary-blue">
                      <button 
                        v-if="export_record.status === 'completed'"
                        @click="downloadFile(export_record.file_name)"
                        class="hover:text-blue-700"
                      >
                        {{ export_record.file_name }}
                      </button>
                      <span v-else class="text-text-secondary">{{ export_record.file_name || '-' }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        export_record.status === 'completed' ? 'bg-green-100 text-green-800' : 
                        export_record.status === 'processing' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'
                      ]">
                        {{ getStatusLabel(export_record.status) }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
              
              <div v-if="exportHistory.length === 0" class="text-center py-12">
                <p class="text-text-secondary">エクスポート履歴がありません</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue'
  import api from '../services/api'
  import Navigation from '../components/Navigation.vue'
  
  const exportForm = ref({
    startMonth: '2025-01',
    endMonth: '2025-01',
    exportType: ''
  })
  
  const loading = ref(false)
  const exportHistory = ref([
    {
      id: 1,
      created_at: '2025-01-15T10:30:00',
      start_month: '2025-01',
      end_month: '2025-01',
      export_type: 'care_insurance',
      file_name: 'care_insurance_202501.csv',
      status: 'completed'
    },
    {
      id: 2,
      created_at: '2025-01-10T14:15:00',
      start_month: '2024-12',
      end_month: '2024-12',
      export_type: 'service_summary',
      file_name: 'service_summary_202412.csv',
      status: 'completed'
    }
  ])
  
  const handleExport = async () => {
    loading.value = true
    
    try {
      const response = await api.get('/export/care-insurance', {
        params: {
          start_month: exportForm.value.startMonth,
          end_month: exportForm.value.endMonth,
          export_type: exportForm.value.exportType
        }
      })
      
      // ダウンロードリンクを作成
      const blob = new Blob([response.data], { type: 'text/csv' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = generateFileName()
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
      
      // 履歴に追加
      exportHistory.value.unshift({
        id: Date.now(),
        created_at: new Date().toISOString(),
        start_month: exportForm.value.startMonth,
        end_month: exportForm.value.endMonth,
        export_type: exportForm.value.exportType,
        file_name: generateFileName(),
        status: 'completed'
      })
      
    } catch (error) {
      console.error('Export failed:', error)
      
      // デモ用にモックCSVを生成
      const mockCsv = generateMockCsv()
      const blob = new Blob([mockCsv], { type: 'text/csv;charset=utf-8' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = generateFileName()
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
      
      // 履歴に追加
      exportHistory.value.unshift({
        id: Date.now(),
        created_at: new Date().toISOString(),
        start_month: exportForm.value.startMonth,
        end_month: exportForm.value.endMonth,
        export_type: exportForm.value.exportType,
        file_name: generateFileName(),
        status: 'completed'
      })
    } finally {
      loading.value = false
    }
  }
  
  const generateFileName = () => {
    const typePrefix = {
      'care_insurance': 'care_insurance',
      'service_summary': 'service_summary',
      'payroll_summary': 'payroll_summary'
    }[exportForm.value.exportType] || 'export'
    
    const period = exportForm.value.startMonth === exportForm.value.endMonth 
      ? exportForm.value.startMonth.replace('-', '')
      : `${exportForm.value.startMonth.replace('-', '')}_${exportForm.value.endMonth.replace('-', '')}`
    
    return `${typePrefix}_${period}.csv`
  }
  
  const generateMockCsv = () => {
    const headers = ['利用者ID', '利用者名', 'サービス種別', '実施日', '開始時間', '終了時間', '時間数', '単価', '金額']
    const rows = [
      ['A001', '山田一郎', '入浴介護', '2025-01-15', '09:00', '11:00', '2.0', '1500', '3000'],
      ['A002', '田中花子', '掃除', '2025-01-15', '13:00', '14:00', '1.0', '1200', '1200'],
      ['A003', '佐々木健一', '生活援助', '2025-01-15', '15:00', '16:30', '1.5', '1300', '1950']
    ]
    
    return [headers.join(','), ...rows.map(row => row.join(','))].join('\n')
  }
  
  const downloadFile = (fileName) => {
    // デモ用にファイルを再生成
    const mockCsv = generateMockCsv()
    const blob = new Blob([mockCsv], { type: 'text/csv;charset=utf-8' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  }
  
  const formatDateTime = (dateTimeString) => {
    return new Date(dateTimeString).toLocaleString('ja-JP', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }
  
  const formatPeriod = (startMonth, endMonth) => {
    const formatMonth = (month) => {
      const [year, monthNum] = month.split('-')
      return `${year}年${monthNum}月`
    }
    
    if (startMonth === endMonth) {
      return formatMonth(startMonth)
    }
    return `${formatMonth(startMonth)} - ${formatMonth(endMonth)}`
  }
  
  const getExportTypeLabel = (type) => {
    const labels = {
      'care_insurance': '介護保険請求用',
      'service_summary': 'サービス実績一覧',
      'payroll_summary': '給与計算用'
    }
    return labels[type] || type
  }
  
  const getStatusLabel = (status) => {
    const labels = {
      'completed': '完了',
      'processing': '処理中',
      'failed': 'エラー'
    }
    return labels[status] || status
  }
  
  onMounted(() => {
    // Initialize with current month
    const now = new Date()
    const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
    exportForm.value.startMonth = currentMonth
    exportForm.value.endMonth = currentMonth
  })
  </script>