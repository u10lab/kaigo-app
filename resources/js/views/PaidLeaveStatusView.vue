<template>
    <div class="min-h-screen bg-bg-secondary p-4">
      <div class="max-w-md mx-auto">
        <!-- Header -->
        <div class="mb-6">
          <button
            @click="goBack"
            class="flex items-center text-primary-blue hover:text-blue-700 mb-4"
          >
            ← 戻る
          </button>
          <h1 class="text-2xl font-bold text-text-primary">有給休暇状況</h1>
        </div>
  
        <!-- Status Cards -->
        <div class="space-y-4">
          <!-- Remaining Days -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-text-primary mb-4">残り日数</h2>
            <div class="text-center">
              <div class="text-4xl font-bold text-primary-blue mb-2">
                {{ paidLeaveStatus.remaining_days || 0 }}
              </div>
              <div class="text-text-secondary">
                残り日数 / {{ paidLeaveStatus.total_days || 10 }}日 (年間付与)
              </div>
            </div>
          </div>
  
          <!-- Annual Requirement -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-text-primary mb-4">年5日取得義務</h2>
            <div class="flex items-center justify-between mb-3">
              <span class="text-text-secondary">取得済み</span>
              <span :class="[
                'text-xl font-bold',
                (paidLeaveStatus.annual_requirement_used || 0) >= 5 ? 'text-primary-green' : 'text-primary-yellow'
              ]">
                {{ paidLeaveStatus.annual_requirement_used || 0 }}/5日
              </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div 
                :class="[
                  'h-2 rounded-full transition-all',
                  (paidLeaveStatus.annual_requirement_used || 0) >= 5 ? 'bg-primary-green' : 'bg-primary-yellow'
                ]"
                :style="{ width: `${Math.min(((paidLeaveStatus.annual_requirement_used || 0) / 5) * 100, 100)}%` }"
              ></div>
            </div>
            <div class="mt-2 text-sm text-text-secondary">
              {{ (paidLeaveStatus.annual_requirement_used || 0) >= 5 ? '達成済み' : `残り${5 - (paidLeaveStatus.annual_requirement_used || 0)}日` }}
            </div>
          </div>
  
          <!-- Usage History -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-text-primary mb-4">取得履歴</h2>
            <div v-if="paidLeaveStatus.usage_history?.length" class="space-y-3">
              <div
                v-for="(record, index) in paidLeaveStatus.usage_history"
                :key="index"
                class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0"
              >
                <div>
                  <div class="font-medium text-text-primary">
                    {{ formatDate(record.date) }}
                  </div>
                  <div class="text-sm text-text-secondary">
                    {{ record.reason }}
                  </div>
                </div>
                <div :class="[
                  'px-2 py-1 rounded text-xs',
                  record.status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                ]">
                  {{ getStatusText(record.status) }}
                </div>
              </div>
            </div>
            <div v-else class="text-center text-text-secondary py-4">
              取得履歴がありません
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
  
  const goBack = () => {
    router.push('/helper')
  }
  
  onMounted(async () => {
    try {
      const userId = authStore.user?.id
      if (userId) {
        const status = await paidLeaveStore.fetchPaidLeaveStatus(userId)
        paidLeaveStatus.value = status
      }
    } catch (error) {
      console.error('Failed to load paid leave status:', error)
    }
  })
  </script>