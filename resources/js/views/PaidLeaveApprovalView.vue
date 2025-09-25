<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-200">
    <Navigation />
    <div class="absolute inset-0 bg-slate-900 bg-opacity-5"></div>
    
    <div class="relative z-10 max-w-6xl mx-auto py-6 sm:px-6 lg:px-8">
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
              有給休暇申請承認
            </span>
          </h1>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="card-enhanced overflow-hidden rounded-2xl hover:scale-105 transition-transform duration-300">
            <div class="p-5">
              <dt class="text-sm font-medium text-text-secondary truncate">
                承認待ち
              </dt>
              <dd class="mt-1 text-3xl font-semibold text-primary-yellow">
                {{ pendingCount }}件
              </dd>
            </div>
          </div>
          
          <div class="card-enhanced overflow-hidden rounded-2xl hover:scale-105 transition-transform duration-300">
            <div class="p-5">
              <dt class="text-sm font-medium text-text-secondary truncate">
                今月承認済み
              </dt>
              <dd class="mt-1 text-3xl font-semibold text-primary-green">
                {{ approvedThisMonth }}件
              </dd>
            </div>
          </div>
          
          <div class="card-enhanced overflow-hidden rounded-2xl hover:scale-105 transition-transform duration-300">
            <div class="p-5">
              <dt class="text-sm font-medium text-text-secondary truncate">
                今月却下
              </dt>
              <dd class="mt-1 text-3xl font-semibold text-primary-red">
                {{ rejectedThisMonth }}件
              </dd>
            </div>
          </div>
          
          <div class="card-enhanced overflow-hidden rounded-2xl hover:scale-105 transition-transform duration-300">
            <div class="p-5">
              <dt class="text-sm font-medium text-text-secondary truncate">
                合計申請
              </dt>
              <dd class="mt-1 text-3xl font-semibold text-text-primary">
                {{ applications.length }}件
              </dd>
            </div>
          </div>
        </div>

        <!-- Filter Tabs -->
        <div class="mb-6">
          <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
              <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="activeTab = tab.key"
                :class="[
                  'py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                  activeTab === tab.key
                    ? 'border-primary-blue text-primary-blue'
                    : 'border-transparent text-text-secondary hover:text-text-primary hover:border-gray-300'
                ]"
              >
                {{ tab.label }} ({{ getFilteredApplications(tab.key).length }})
              </button>
            </nav>
          </div>
        </div>

        <!-- Applications List -->
        <div class="card-enhanced shadow overflow-hidden rounded-2xl">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                    申請者
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                    取得期間
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                    日数
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                    理由
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                    ステータス
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                    申請日
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                    操作
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr
                  v-for="application in getFilteredApplications(activeTab)"
                  :key="application.id"
                  class="hover:bg-gray-50"
                >
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                    {{ application.user?.name || 'Unknown' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                    {{ formatDateRange(application.start_date, application.end_date) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                    {{ application.requested_days }}日
                  </td>
                  <td class="px-6 py-4 text-sm text-text-secondary max-w-xs truncate">
                    {{ application.reason }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      getStatusClass(application.status)
                    ]">
                      {{ getStatusText(application.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                    {{ formatDate(application.created_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div v-if="application.status === 'pending'" class="flex space-x-2">
                      <button
                        @click="approveApplication(application.id)"
                        class="text-green-600 hover:text-green-900 transition-colors duration-200"
                      >
                        承認
                      </button>
                      <button
                        @click="rejectApplication(application.id)"
                        class="text-red-600 hover:text-red-900 transition-colors duration-200"
                      >
                        却下
                      </button>
                    </div>
                    <span v-else class="text-text-secondary">-</span>
                  </td>
                </tr>
              </tbody>
            </table>
            
            <div v-if="getFilteredApplications(activeTab).length === 0" class="text-center py-12">
              <p class="text-text-secondary">申請がありません</p>
            </div>
          </div>
        </div>

        <!-- Rejection Modal -->
        <div v-if="showRejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
              <h3 class="text-lg font-medium text-text-primary mb-4">申請却下</h3>
              
              <div class="mb-4">
                <label class="block text-sm font-medium text-text-primary mb-2">
                  却下理由 <span class="text-red-500">*</span>
                </label>
                <textarea
                  v-model="rejectionReason"
                  rows="4"
                  maxlength="500"
                  placeholder="却下理由を入力してください"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-blue"
                ></textarea>
                <p class="text-xs text-text-secondary mt-1">
                  {{ rejectionReason.length }}/500文字
                </p>
              </div>
              
              <div class="flex space-x-3">
                <button
                  @click="cancelReject"
                  class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-text-primary hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500"
                >
                  キャンセル
                </button>
                
                <button
                  @click="confirmReject"
                  :disabled="!rejectionReason.trim() || loading"
                  class="flex-1 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="loading">処理中...</span>
                  <span v-else>却下する</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { usePaidLeaveStore } from '../stores/paidLeave'
import Navigation from '../components/Navigation.vue'

const router = useRouter()
const paidLeaveStore = usePaidLeaveStore()

const applications = ref([])
const loading = ref(false)
const activeTab = ref('pending')
const showRejectModal = ref(false)
const currentRejectId = ref(null)
const rejectionReason = ref('')

const tabs = [
  { key: 'pending', label: '承認待ち' },
  { key: 'approved', label: '承認済み' },
  { key: 'rejected', label: '却下済み' },
  { key: 'all', label: '全て' }
]

const pendingCount = computed(() => {
  return applications.value.filter(app => app.status === 'pending').length
})

const approvedThisMonth = computed(() => {
  const currentMonth = new Date().getMonth()
  const currentYear = new Date().getFullYear()
  
  return applications.value.filter(app => {
    if (app.status !== 'approved' || !app.approved_at) return false
    
    const approvedDate = new Date(app.approved_at)
    return approvedDate.getMonth() === currentMonth && approvedDate.getFullYear() === currentYear
  }).length
})

const rejectedThisMonth = computed(() => {
  const currentMonth = new Date().getMonth()
  const currentYear = new Date().getFullYear()
  
  return applications.value.filter(app => {
    if (app.status !== 'rejected' || !app.rejected_at) return false
    
    const rejectedDate = new Date(app.rejected_at)
    return rejectedDate.getMonth() === currentMonth && rejectedDate.getFullYear() === currentYear
  }).length
})

const getFilteredApplications = (status) => {
  if (status === 'all') return applications.value
  return applications.value.filter(app => app.status === status)
}

const getStatusClass = (status) => {
  switch (status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'approved':
      return 'bg-green-100 text-green-800'
    case 'rejected':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getStatusText = (status) => {
  switch (status) {
    case 'pending':
      return '承認待ち'
    case 'approved':
      return '承認済み'
    case 'rejected':
      return '却下済み'
    default:
      return '不明'
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('ja-JP', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDateRange = (startDate, endDate) => {
  const start = new Date(startDate)
  const end = new Date(endDate)
  
  if (start.toDateString() === end.toDateString()) {
    return start.toLocaleDateString('ja-JP', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  }
  
  return `${start.toLocaleDateString('ja-JP', {
    month: 'long',
    day: 'numeric'
  })} - ${end.toLocaleDateString('ja-JP', {
    month: 'long',
    day: 'numeric'
  })}`
}

const approveApplication = async (applicationId) => {
  if (!confirm('この申請を承認しますか？（freee API経由で処理されます）')) return
  
  loading.value = true
  
  try {
    // freee API経由で承認
    await paidLeaveStore.approveApplicationViaFreee(applicationId)
    alert('申請がfreee API経由で承認されました。')
    await loadApplications()
  } catch (error) {
    console.error('Approval via freee failed:', error)
    
    // freee API失敗時はローカル承認にフォールバック
    try {
      await paidLeaveStore.approveApplication(applicationId)
      alert('申請が承認されました（ローカル管理）。')
      await loadApplications()
    } catch (fallbackError) {
      console.error('Fallback approval failed:', fallbackError)
      alert('承認に失敗しました。')
    }
  } finally {
    loading.value = false
  }
}

const rejectApplication = (applicationId) => {
  currentRejectId.value = applicationId
  rejectionReason.value = ''
  showRejectModal.value = true
}

const confirmReject = async () => {
  if (!rejectionReason.value.trim()) return
  
  loading.value = true
  
  try {
    // freee API経由で却下
    await paidLeaveStore.rejectApplicationViaFreee(currentRejectId.value, rejectionReason.value)
    alert('申請がfreee API経由で却下されました。')
    await loadApplications()
    showRejectModal.value = false
  } catch (error) {
    console.error('Rejection via freee failed:', error)
    
    // freee API失敗時はローカル却下にフォールバック
    try {
      await paidLeaveStore.rejectApplication(currentRejectId.value, rejectionReason.value)
      alert('申請が却下されました（ローカル管理）。')
      await loadApplications()
      showRejectModal.value = false
    } catch (fallbackError) {
      console.error('Fallback rejection failed:', fallbackError)
      alert('却下に失敗しました。')
    }
  } finally {
    loading.value = false
  }
}

const cancelReject = () => {
  showRejectModal.value = false
  currentRejectId.value = null
  rejectionReason.value = ''
}

const loadApplications = async () => {
  try {
    const data = await paidLeaveStore.fetchApplications()
    applications.value = data
  } catch (error) {
    console.error('Failed to load applications:', error)
  }
}

const goBack = () => {
  router.push('/admin/paid-leave')
}

onMounted(() => {
  loadApplications()
})
</script>
