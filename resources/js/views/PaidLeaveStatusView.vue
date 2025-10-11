<template>
    <div class="page-container">
      <div class="page-content-4xl">
        <!-- Header -->
        <div class="mb-6">
          <button @click="goBack" class="btn-link-blue mb-4">
            ← 戻る
          </button>
          <h1 class="text-2xl font-bold text-text-primary">有給休暇状況</h1>
        </div>
  
        <!-- Status Cards -->
        <div class="space-y-4">
          <!-- Remaining Days -->
          <div class="card-enhanced rounded-2xl p-6">
            <h2 class="text-lg font-semibold text-text-primary mb-4">残り日数</h2>
            <div class="text-center">
              <div class="stat-value-lg">
                {{ paidLeaveStatus.remaining_days || 0 }}
              </div>
              <div class="stat-label">
                残り日数 / {{ paidLeaveStatus.granted_days || 10 }}日 (年間付与)
              </div>
            </div>
          </div>
  
          <!-- Annual Requirement -->
          <div class="card-enhanced rounded-2xl p-6">
            <h2 class="text-lg font-semibold text-text-primary mb-4">年5日取得義務</h2>
            <div class="flex items-center justify-between mb-3">
              <span class="stat-label">取得済み</span>
              <span :class="[
                'stat-value-xl',
                (paidLeaveStatus.annual_requirement_used || 0) >= 5 ? 'text-primary-green' : 'text-primary-yellow'
              ]">
                {{ paidLeaveStatus.annual_requirement_used || 0 }}/5日
              </span>
            </div>
            <div class="progress-bar-container">
              <div 
                :class="[
                  'progress-bar',
                  (paidLeaveStatus.annual_requirement_used || 0) >= 5 ? 'bg-primary-green' : 'bg-primary-yellow'
                ]"
                :style="{ width: `${Math.min(((paidLeaveStatus.annual_requirement_used || 0) / 5) * 100, 100)}%` }"
              ></div>
            </div>
            <div class="mt-2 stat-label-sm">
              {{ (paidLeaveStatus.annual_requirement_used || 0) >= 5 ? '達成済み' : `残り${5 - (paidLeaveStatus.annual_requirement_used || 0)}日` }}
            </div>
          </div>
  
          <!-- Application Button -->
          <div class="card-enhanced rounded-2xl p-6">
            <div class="flex justify-center">
              <button @click="goToApplication" class="w-full btn-submit-lg">
                有給休暇を申請する
              </button>
            </div>
          </div>

          <!-- Usage History -->
          <div class="card-enhanced rounded-2xl p-6">
            <h2 class="text-lg font-semibold text-text-primary mb-4">取得履歴</h2>
            <div v-if="applicationHistory.length > 0" class="space-y-3">
              <div
                v-for="(record, index) in applicationHistory"
                :key="index"
                class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0"
              >
                <div>
                  <div class="font-medium text-text-primary">
                    {{ formatDateRange(record.start_date, record.end_date) }}
                  </div>
                  <div class="text-sm text-text-secondary">
                    {{ record.reason }}
                  </div>
                  <div class="text-xs text-text-secondary">
                    申請日: {{ formatDate(record.created_at) }}
                  </div>
                </div>
                <div :class="[
                  'px-2 py-1 rounded text-xs',
                  getStatusClass(record.status)
                ]">
                  {{ getStatusText(record.status) }}
                </div>
              </div>
            </div>
            <div v-else class="text-center text-text-secondary py-4">
              申請履歴がありません
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue'
  import { useRouter } from 'vue-router'
  import { usePaidLeaveStore } from '../stores/paidLeave'
  import { useAuthStore } from '../stores/auth'
  
  const router = useRouter()
  const paidLeaveStore = usePaidLeaveStore()
  const authStore = useAuthStore()
  
  const paidLeaveStatus = ref({})
  const applicationHistory = ref([])
  
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

  const getStatusClass = (status) => {
    switch (status) {
      case 'approved':
        return 'bg-green-100 text-green-800'
      case 'pending':
        return 'bg-yellow-100 text-yellow-800'
      case 'rejected':
        return 'bg-red-100 text-red-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
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

  const loadApplicationHistory = async () => {
    try {
      const data = await paidLeaveStore.fetchApplications()
      applicationHistory.value = data
    } catch (error) {
      console.error('Failed to load application history:', error)
    }
  }
  
  const goBack = () => {
    router.push('/helper')
  }

  const goToApplication = () => {
    router.push('/paid-leave/application')
  }
  
  onMounted(async () => {
    try {
      const userId = authStore.user?.id
      if (userId) {
        const status = await paidLeaveStore.fetchPaidLeaveStatus(userId)
        paidLeaveStatus.value = status
      }
      await loadApplicationHistory()
    } catch (error) {
      console.error('Failed to load paid leave status:', error)
    }
  })
  </script>
