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
  
          <!-- Summary Cards -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="card-enhanced overflow-hidden rounded-2xl hover:scale-105 transition-transform duration-300">
              <div class="p-5">
                <dt class="text-sm font-medium text-text-secondary truncate">
                  総支給額
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-text-primary">
                  ¥{{ totalPayroll.toLocaleString() }}
                </dd>
              </div>
            </div>
            
            <div class="card-enhanced overflow-hidden rounded-2xl hover:scale-105 transition-transform duration-300">
              <div class="p-5">
                <dt class="text-sm font-medium text-text-secondary truncate">
                  サービス調整額
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-primary-blue">
                  ¥{{ totalAdjustment.toLocaleString() }}
                </dd>
              </div>
            </div>
            
            <div class="card-enhanced overflow-hidden rounded-2xl hover:scale-105 transition-transform duration-300">
              <div class="p-5">
                <dt class="text-sm font-medium text-text-secondary truncate">
                  対象従業員数
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-text-primary">
                  {{ payrollList.length }}名
                </dd>
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
                class="cursor-pointer hover:bg-gray-50"
                @click="viewPayrollDetail(payroll.user_id, selectedMonth)"
              >
                <div class="px-4 py-4 sm:px-6">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center">
                      <div>
                        <p class="text-sm font-medium text-text-primary">
                          {{ payroll.employee_name }}
                        </p>
                        <p class="text-sm text-text-secondary">
                          基本給: ¥{{ payroll.base_salary?.toLocaleString() }}
                        </p>
                      </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                      <div class="text-right">
                        <p class="text-sm text-text-secondary">サービス調整</p>
                        <p class="text-sm font-medium text-primary-blue">
                          +¥{{ payroll.service_adjustments?.toLocaleString() }}
                        </p>
                      </div>
                      
                      <div class="text-right">
                        <p class="text-sm text-text-secondary">総支給額</p>
                        <p class="text-lg font-semibold text-text-primary">
                          ¥{{ payroll.total_salary?.toLocaleString() }}
                        </p>
                      </div>
                      
                      <div class="text-primary-blue">
                        →
                      </div>
                    </div>
                  </div>
                  
                  <div class="mt-2">
                    <div class="flex items-center text-sm text-text-secondary space-x-4">
                      <span>総労働時間: {{ payroll.total_hours || 0 }}h</span>
                      <span v-if="payroll.overtime_hours">
                        残業: {{ payroll.overtime_hours }}h
                      </span>
                    </div>
                  </div>
                </div>
              </li>
            </ul>
  
            <div v-if="payrollList.length === 0" class="text-center py-12">
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
  
  const selectedMonth = ref('2025-01')
  const payrollList = ref([])
  
  const months = [
    { value: '2025-01', label: '2025年1月' },
    { value: '2024-12', label: '2024年12月' },
    { value: '2024-11', label: '2024年11月' },
    { value: '2024-10', label: '2024年10月' }
  ]
  
  const totalPayroll = computed(() => {
    return payrollList.value.reduce((sum, payroll) => sum + (payroll.total_salary || 0), 0)
  })
  
  const totalAdjustment = computed(() => {
    return payrollList.value.reduce((sum, payroll) => sum + (payroll.service_adjustments || 0), 0)
  })
  
  const loadPayrollData = async () => {
    try {
      const data = await payrollStore.fetchAllPayrollData(selectedMonth.value)
      payrollList.value = data
    } catch (error) {
      console.error('Failed to load payroll data:', error)
    }
  }
  
  const viewPayrollDetail = (userId, month) => {
    router.push(`/admin/payroll/${userId}/${month}`)
  }
  
  onMounted(() => {
    loadPayrollData()
  })
  </script>