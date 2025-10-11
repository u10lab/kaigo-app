<template>
    <div class="page-container">
      <Navigation />
      <div class="page-overlay"></div>
      
      <div class="page-content-4xl">
        <div class="px-4 py-6 sm:px-0">
          <!-- Header -->
          <div class="mb-8">
            <button
              @click="goBack"
              class="flex items-center text-slate-700 hover:text-slate-900 mb-4 font-medium transition-colors duration-200"
            >
              ← 有給管理に戻る
            </button>
            <h1 class="text-4xl font-bold text-slate-800 text-center">
              <span class="bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent">
                有給詳細 - {{ employeeName }}
              </span>
            </h1>
          </div>
  
          <div class="space-y-6">
            <!-- Status Overview -->
            <div class="card-enhanced shadow rounded-2xl">
              <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-text-primary">有給取得状況</h2>
              </div>
              <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                  <div class="text-center">
                    <div class="text-2xl font-bold text-text-primary">
                      {{ paidLeaveStatus.granted_days || 10 }}
                    </div>
                    <div class="text-sm text-text-secondary">年間付与日数</div>
                  </div>
                  
                  <div class="text-center">
                    <div class="text-2xl font-bold text-primary-blue">
                      {{ paidLeaveStatus.used_days || 0 }}
                    </div>
                    <div class="text-sm text-text-secondary">使用済み日数</div>
                  </div>
                  
                  <div class="text-center">
                    <div class="text-2xl font-bold text-primary-green">
                      {{ paidLeaveStatus.remaining_days || 0 }}
                    </div>
                    <div class="text-sm text-text-secondary">残り日数</div>
                  </div>
                  
                  <div class="text-center">
                    <div :class="[
                      'text-2xl font-bold',
                      (paidLeaveStatus.annual_requirement_used || 0) >= 5 ? 'text-primary-green' : 'text-primary-red'
                    ]">
                      {{ paidLeaveStatus.annual_requirement_used || 0 }}/5
                    </div>
                    <div class="text-sm text-text-secondary">年5日義務</div>
                  </div>
                </div>
              </div>
            </div>
  
            <!-- Annual Requirement Progress -->
            <div class="card-enhanced shadow rounded-2xl">
              <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-text-primary">年5日取得義務 進捗</h2>
              </div>
              <div class="p-6">
                <div class="w-full bg-gray-200 rounded-full h-4 mb-4">
                  <div 
                    :class="[
                      'h-4 rounded-full transition-all',
                      (paidLeaveStatus.annual_requirement_used || 0) >= 5 ? 'bg-primary-green' : 'bg-primary-yellow'
                    ]"
                    :style="{ width: `${Math.min(((paidLeaveStatus.annual_requirement_used || 0) / 5) * 100, 100)}%` }"
                  ></div>
                </div>
                <div class="flex justify-between text-sm text-text-secondary">
                  <span>0日</span>
                  <span :class="[
                    'font-medium',
                    (paidLeaveStatus.annual_requirement_used || 0) >= 5 ? 'text-primary-green' : 'text-primary-red'
                  ]">
                    {{ (paidLeaveStatus.annual_requirement_used || 0) >= 5 ? '達成' : `残り${5 - (paidLeaveStatus.annual_requirement_used || 0)}日` }}
                  </span>
                  <span>5日</span>
                </div>
              </div>
            </div>
  
            <!-- Usage History -->
            <div class="card-enhanced shadow rounded-2xl">
              <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-text-primary">取得履歴</h2>
              </div>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                        取得日
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                        理由
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                        ステータス
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                      v-for="(record, index) in paidLeaveStatus.usage_history"
                      :key="index"
                    >
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                        {{ formatDate(record.date) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                        {{ record.reason }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span :class="[
                          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                          record.status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                        ]">
                          {{ getStatusText(record.status) }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
                
                <div v-if="!paidLeaveStatus.usage_history?.length" class="text-center py-12">
                  <p class="text-text-secondary">取得履歴がありません</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { usePaidLeaveStore } from '../stores/paidLeave'
  import Navigation from '../components/Navigation.vue'
  
  const route = useRoute()
  const router = useRouter()
  const paidLeaveStore = usePaidLeaveStore()
  
  const userId = route.params.userId
  
  const paidLeaveStatus = ref({})
  const employeeName = ref('')
  
  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('ja-JP', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  }
  
  const getStatusText = (status) => {
    switch (status) {
      case 'approved':
        return '承認済み'
      case 'pending':
        return '承認待ち'
      case 'rejected':
        return '却下'
      default:
        return '不明'
    }
  }
  
  const loadPaidLeaveDetail = async () => {
    try {
      const status = await paidLeaveStore.fetchPaidLeaveStatus(userId)
      paidLeaveStatus.value = status
      
      // 有給休暇ストアから従業員名を取得
      const allEmployees = await paidLeaveStore.fetchAllEmployeesLeaveStatus()
      const employee = allEmployees.find(emp => emp.user_id == userId)
      employeeName.value = employee?.user_name || 'Unknown'
    } catch (error) {
      console.error('Failed to load paid leave detail:', error)
    }
  }
  
  const goBack = () => {
    router.push('/admin/paid-leave')
  }
  
  onMounted(() => {
    loadPaidLeaveDetail()
  })
  </script>