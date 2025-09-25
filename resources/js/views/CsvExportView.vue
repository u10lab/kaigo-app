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
                <div>
                  <label class="block text-sm font-medium text-text-primary mb-2">
                    出力対象月
                  </label>
                  <input
                    type="month"
                    v-model="exportForm.targetMonth"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-blue focus:border-primary-blue"
                    required
                  >
                  <p class="text-sm text-gray-600 mt-1">選択した月の1日から末日までのサービス記録を出力します。</p>
                </div>

                <!-- Client Filter -->
                <div>
                  <label class="block text-sm font-medium text-text-primary mb-2">
                    利用者絞り込み
                  </label>
                  <select
                    v-model="exportForm.clientId"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-blue focus:border-primary-blue"
                  >
                    <option value="">全利用者</option>
                    <option v-for="client in clients" :key="client.id" :value="client.id">
                      {{ client.name }}
                    </option>
                  </select>
                  <p class="text-sm text-gray-600 mt-1">特定の利用者のみを出力する場合は選択してください。利用者を選択した場合、その利用者のみのデータがCSVに出力されます。</p>
                </div>

                <!-- Export Button -->
                <div class="flex justify-end">
                  <button
                    type="submit"
                    :disabled="loading"
                    class="px-6 py-3 bg-primary-blue text-white rounded-md hover:bg-blue-700 disabled:opacity-50 transition-colors"
                  >
                    {{ loading ? 'エクスポート中...' : '介護保険CSVエクスポート' }}
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
                      出力件数
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                      ファイル名
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                      実行者
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="export_record in exportHistory" :key="export_record.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                      {{ formatDateTime(export_record.exported_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                      {{ formatPeriod(export_record.period) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                      {{ export_record.record_count }}件
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-primary-blue">
                      <button
                        @click="downloadFile(export_record)"
                        class="hover:text-blue-700 underline"
                      >
                        {{ export_record.file_name }}
                      </button>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                      {{ export_record.exported_by }}
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
    targetMonth: '2025-09',
    clientId: ''
  })

  const clients = ref([])
  
  const loading = ref(false)
  const exportHistory = ref([])
  const currentUser = ref(null)
  
  const handleExport = async () => {
    loading.value = true

    try {
      // API URLを構築
      const params = new URLSearchParams()
      if (exportForm.value.clientId) {
        params.append('client_id', exportForm.value.clientId)
      }

      const url = `/export/care-insurance/${exportForm.value.targetMonth}${params.toString() ? '?' + params.toString() : ''}`

      const response = await api.get(url, {
        responseType: 'blob'
      })

      // ファイル名を取得
      const contentDisposition = response.headers['content-disposition']
      let filename = generateFileName()
      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename="(.+)"/)
        if (filenameMatch) {
          filename = filenameMatch[1]
        }
      }

      // ダウンロードリンクを作成
      const blob = new Blob([response.data], { 
        type: 'text/csv;charset=utf-8'
      })
      const downloadUrl = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = downloadUrl
      link.download = filename
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(downloadUrl)

      // エクスポート後に履歴を再取得
      await fetchExportHistory()

      console.log('CSV export completed:', filename)

    } catch (error) {
      console.error('Export failed:', error)
      alert('CSV出力に失敗しました。')
    } finally {
      loading.value = false
    }
  }
  
  const generateFileName = () => {
    const period = exportForm.value.targetMonth.replace('-', '')
    let filename = `care_insurance_${period}`

    if (exportForm.value.clientId) {
      const client = clients.value.find(c => c.id === exportForm.value.clientId)
      if (client) {
        filename += `_${client.name}`
      }
    }

    return `${filename}.csv`
  }
  
  
  const downloadFile = async (exportRecord) => {
    try {
      loading.value = true
      
      // 認証状態をチェック
      try {
        const authCheck = await api.get('/user')
        console.log('Auth check response:', authCheck.data)
        
        if (!authCheck.data) {
          console.error('No user data received')
          alert('ユーザー情報が取得できませんでした。')
          return
        }
        
        if (authCheck.data.role !== 'admin') {
          console.error('User role is not admin:', authCheck.data.role)
          alert(`管理者権限が必要です。現在の権限: ${authCheck.data.role}`)
          return
        }
        
        console.log('Authentication check passed, user is admin')
      } catch (authError) {
        console.error('Authentication check failed:', authError)
        alert('認証エラーが発生しました。ログインし直してください。')
        return
      }
      
      // バックエンドAPIを呼び出してファイルをダウンロード
      console.log('Attempting to download file for history ID:', exportRecord.id)
      const response = await api.get(`/export/download/${exportRecord.id}`, {
        responseType: 'blob'
      })
      console.log('Download response received:', response)
      
      // レスポンスがHTMLかどうかチェック（認証エラーの場合）
      if (response.data instanceof Blob) {
        const text = await response.data.text()
        if (text.includes('<!DOCTYPE html>') || text.includes('<html')) {
          console.error('Received HTML instead of CSV, likely authentication error')
          alert('認証エラーが発生しました。ログインし直してください。')
          return
        }
        
        // 正しいCSVデータの場合
        const csvBlob = new Blob([response.data], { 
          type: 'text/csv;charset=utf-8'
        })
        const url = window.URL.createObjectURL(csvBlob)
        const link = document.createElement('a')
        link.href = url
        link.download = exportRecord.file_name
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
        
        console.log('File download completed:', exportRecord.file_name)
      }
    } catch (error) {
      console.error('File download failed:', error)
      if (error.response && error.response.status === 401) {
        alert('認証エラーが発生しました。ログインし直してください。')
      } else if (error.response && error.response.status === 403) {
        alert('管理者権限が必要です。')
      } else {
        alert('ファイルのダウンロードに失敗しました。')
      }
    } finally {
      loading.value = false
    }
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
  
  const formatPeriod = (period) => {
    const [year, month] = period.split('-')
    return `${year}年${month}月`
  }
  
  const getExportTypeLabel = (type) => {
    return '介護保険請求用'
  }
  
  
  const fetchClients = async () => {
    try {
      const response = await api.get('/service/clients')
      clients.value = response.data.data || []
    } catch (error) {
      console.error('Failed to fetch clients:', error)
      clients.value = []
    }
  }

  const fetchExportHistory = async () => {
    try {
      const response = await api.get('/export/history')
      exportHistory.value = response.data.export_history || []
    } catch (error) {
      console.error('Failed to fetch export history:', error)
      exportHistory.value = []
    }
  }

  onMounted(async () => {
    await fetchClients()
    await fetchExportHistory()

    // Initialize with current month (September 2025 as per requirements)
    const now = new Date()
    const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
    exportForm.value.targetMonth = currentMonth
  })
  </script>