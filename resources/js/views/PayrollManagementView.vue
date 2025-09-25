<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-200">
      <Navigation />
      <div class="absolute inset-0 bg-slate-900 bg-opacity-5"></div>
      
      <div class="relative z-10 max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
          <div class="flex justify-between items-center mb-8">
            <h1 class="text-4xl font-bold text-slate-800 mb-8 text-center">
              <span class="bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent">
                給与管理
              </span>
            </h1>
            
            <!-- Month Selector -->
            <div class="flex items-center space-x-4">
              <select
                v-model="selectedMonth"
                @change="loadPayrollData"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-blue focus:border-primary-blue"
              >
                <option v-for="month in months" :key="month.value" :value="month.value">
                  {{ month.label }}
                </option>
              </select>
            </div>
          </div>
  
          <!-- Summary Card -->
          <div class="grid grid-cols-1 md:grid-cols-1 gap-6 mb-8">
            <div class="card-enhanced overflow-hidden rounded-2xl">
              <div class="p-6 text-center">
                <dt class="text-lg font-medium text-text-secondary mb-2">
                  対象従業員数
                </dt>
                <dd class="text-4xl font-bold text-text-primary">
                  {{ payrollList.length }}名
                </dd>
                <p class="text-sm text-text-secondary mt-2">
                  各従業員の詳細な給与情報は個別ページで確認できます
                </p>
              </div>
            </div>
          </div>
  
          <!-- Payroll List -->
          <div class="card-enhanced shadow overflow-hidden rounded-2xl">
            <div class="px-4 py-5 sm:px-6">
              <h2 class="text-lg leading-6 font-medium text-text-primary">
                従業員別給与詳細
              </h2>
            </div>
            
            <ul class="divide-y divide-gray-200">
              <li
                v-for="payroll in payrollList"
                :key="payroll.user_id"
                class="cursor-pointer hover:bg-gray-50 transition-colors duration-150"
                @click="viewPayrollDetail(payroll.user_id, selectedMonth)"
              >
                <div class="px-6 py-5 sm:px-8">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center">
                      <div>
                        <p class="text-lg font-medium text-text-primary">
                          {{ payroll.employee_name }}
                        </p>
                        <p class="text-sm text-text-secondary mt-1">
                          {{ formatMonth(selectedMonth) }}の給与詳細を確認
                        </p>
                      </div>
                    </div>

                    <div class="text-primary-blue text-xl">
                      →
                    </div>
                  </div>
                </div>
              </li>
            </ul>
  
            <!-- Loading State -->
            <div v-if="loading" class="text-center py-12">
              <div class="inline-flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary-blue" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-text-secondary">給与データを読み込み中...</span>
              </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="text-center py-12">
              <div class="text-red-600 mb-2">{{ error }}</div>
              <button
                @click="loadPayrollData"
                class="px-4 py-2 bg-primary-blue text-white rounded-md hover:bg-blue-700"
              >
                再読み込み
              </button>
            </div>

            <!-- Empty State -->
            <div v-else-if="payrollList.length === 0" class="text-center py-12">
              <p class="text-text-secondary">給与データがありません</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted } from 'vue'
  import { useRouter } from 'vue-router'
  import { usePayrollStore } from '../stores/payroll'
  import Navigation from '../components/Navigation.vue'
  
  const router = useRouter()
  const payrollStore = usePayrollStore()

  const selectedMonth = ref('2025-10')
  const payrollList = ref([])
  const loading = ref(false)
  const error = ref('')
  
  const months = [
    { value: '2025-10', label: '2025年10月' },
    { value: '2025-09', label: '2025年9月' }
  ]

  const formatMonth = (monthStr) => {
    const [year, monthNum] = monthStr.split('-')
    return `${year}年${monthNum}月`
  }
  
  // サマリーカード用（実際のデータは詳細ページで表示）
  const totalPayroll = computed(() => 0)
  const totalBaseSalary = computed(() => 0)
  const totalAdjustment = computed(() => 0)
  
  const loadPayrollData = async () => {
    loading.value = true
    error.value = ''

    console.log('=== 給与管理画面 従業員リスト表示 ===')

    try {
      // 従業員データのみ取得（給与APIは呼び出さない）
      await payrollStore.fetchEmployees()

      // 従業員リストを表示用に変換（名前のみ）
      payrollList.value = payrollStore.employees.map(employee => ({
        user_id: employee.id,
        employee_name: employee.name
      }))

      console.log('従業員リスト表示完了:', payrollList.value)
    } catch (err) {
      console.error('従業員データ取得エラー:', err)
      error.value = `従業員データの読み込みに失敗しました: ${err.message}`
      payrollList.value = []
    } finally {
      loading.value = false
    }
  }
  
  const viewPayrollDetail = (userId, month) => {
    router.push(`/admin/payroll/${userId}/${month}`)
  }
  
  onMounted(() => {
    loadPayrollData()
  })
  </script>