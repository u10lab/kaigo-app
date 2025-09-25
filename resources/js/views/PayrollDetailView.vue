<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-200">
      <Navigation />
      <div class="absolute inset-0 bg-slate-900 bg-opacity-5"></div>
      
      <div class="relative z-10 max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
          <!-- Header -->
          <div class="mb-8">
            <button
              @click="goBack"
              class="flex items-center text-slate-700 hover:text-slate-900 mb-4 font-medium transition-colors duration-200"
            >
              ← 給与管理に戻る
            </button>
            <h1 class="text-4xl font-bold text-slate-800 text-center">
              <span class="bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent">
                給与詳細 - {{ employeeName }} ({{ formatMonth(month) }})
              </span>
            </h1>
          </div>
  
          <div class="space-y-6">
            <!-- Summary Card -->
            <div class="card-enhanced shadow rounded-2xl">
              <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-text-primary">給与サマリー</h2>
              </div>
              <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div class="text-center">
                    <div class="text-2xl font-bold text-text-primary">
                      ¥{{ payrollDetail.base_salary?.toLocaleString() }}
                    </div>
                    <div class="text-sm text-text-secondary">基本給</div>
                  </div>
                  
                  <div class="text-center">
                    <div class="text-2xl font-bold text-primary-blue">
                      +¥{{ payrollDetail.service_adjustments?.toLocaleString() }}
                    </div>
                    <div class="text-sm text-text-secondary">サービス調整</div>
                  </div>
                  
                  <div class="text-center">
                    <div class="text-3xl font-bold text-primary-green">
                      ¥{{ payrollDetail.total_salary?.toLocaleString() }}
                    </div>
                    <div class="text-sm text-text-secondary">総支給額</div>
                  </div>
                </div>
              </div>
            </div>
  
            <!-- Work Hours -->
            <div class="card-enhanced shadow rounded-2xl">
              <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-text-primary">労働時間</h2>
              </div>
              <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div>
                    <div class="text-xl font-semibold text-text-primary">
                      {{ payrollDetail.total_hours || 0 }}時間
                    </div>
                    <div class="text-sm text-text-secondary">総労働時間</div>
                  </div>
                  
                  <div>
                    <div class="text-xl font-semibold text-text-primary">
                      ¥{{ payrollDetail.base_hourly_rate?.toLocaleString() || 0 }}/時
                    </div>
                    <div class="text-sm text-text-secondary">基本時給</div>
                  </div>
                  
                  <div v-if="payrollDetail.overtime_hours">
                    <div class="text-xl font-semibold text-primary-red">
                      {{ payrollDetail.overtime_hours }}時間
                    </div>
                    <div class="text-sm text-text-secondary">残業時間</div>
                  </div>
                </div>
              </div>
            </div>
  
            <!-- Service Breakdown -->
            <div class="card-enhanced shadow rounded-2xl">
              <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-text-primary">サービス別内訳</h2>
              </div>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                        サービス種別
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                        時間数
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                        調整額
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                      v-for="(breakdown, index) in payrollDetail.service_breakdown"
                      :key="index"
                    >
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text-primary">
                        {{ breakdown.service_type }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                        {{ breakdown.hours }}時間
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-primary-blue">
                        +¥{{ breakdown.adjustment?.toLocaleString() }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
  
            <!-- Actions -->
            <div class="flex justify-end space-x-4">
              <button
                @click="recalculatePayroll"
                :disabled="loading"
                class="px-4 py-2 bg-primary-blue text-white rounded-md hover:bg-blue-700 disabled:opacity-50 transition-colors"
              >
                {{ loading ? '再計算中...' : '給与再計算' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { usePayrollStore } from '../stores/payroll'
  import Navigation from '../components/Navigation.vue'
  
  const route = useRoute()
  const router = useRouter()
  const payrollStore = usePayrollStore()
  
  const userId = route.params.userId
  const month = route.params.month
  
  const payrollDetail = ref({})
  const employeeName = ref('')
  const loading = ref(false)
  
  const formatMonth = (monthStr) => {
    const [year, monthNum] = monthStr.split('-')
    return `${year}年${monthNum}月`
  }
  
  const loadPayrollDetail = async () => {
    console.log('=== 給与詳細ページ データ取得開始 ===')
    console.log('パラメータ:', { userId, month })

    try {
      console.log('給与計算API呼び出し...')
      const data = await payrollStore.fetchPayrollData(month, userId)

      console.log('給与詳細データ取得完了:', data)
      payrollDetail.value = data

      // ストアから従業員名を取得
      const employee = payrollStore.employees.find(emp => emp.id == userId)
      employeeName.value = employee?.name || data.employee_name || 'Unknown'

      console.log('従業員名:', employeeName.value)
      console.log('=== 給与詳細ページ データ取得完了 ===')
    } catch (error) {
      console.error('給与詳細取得エラー:', error)
      console.error('エラー詳細:', {
        message: error.message,
        response: error.response?.data,
        status: error.response?.status
      })
    }
  }
  
  const recalculatePayroll = async () => {
    loading.value = true
    try {
      await payrollStore.recalculatePayroll(month, userId)
      await loadPayrollDetail() // データを再読み込み
    } catch (error) {
      console.error('Failed to recalculate payroll:', error)
    } finally {
      loading.value = false
    }
  }
  
  const goBack = () => {
    router.push('/admin/payroll')
  }
  
  onMounted(() => {
    loadPayrollDetail()
  })
  </script>