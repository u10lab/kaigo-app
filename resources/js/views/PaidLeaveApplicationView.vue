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
        <h1 class="text-2xl font-bold text-text-primary">有給休暇申請</h1>
      </div>

      <!-- Current Status -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-text-primary mb-4">現在の状況</h2>
        <div class="grid grid-cols-2 gap-4">
          <div class="text-center">
            <div class="text-2xl font-bold text-primary-blue">
              {{ paidLeaveStatus.remaining_days || 0 }}
            </div>
            <div class="text-sm text-text-secondary">残り日数</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-primary-green">
              {{ paidLeaveStatus.granted_days || 0 }}
            </div>
            <div class="text-sm text-text-secondary">年間付与</div>
          </div>
        </div>
      </div>

      <!-- Application Form -->
      <form @submit.prevent="submitApplication" class="space-y-6">
        <!-- Date Range -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-lg font-semibold text-text-primary mb-4">取得期間</h2>
          
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-text-primary mb-2">
                開始日 <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.start_date"
                type="date"
                :min="minDate"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-blue"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-text-primary mb-2">
                終了日 <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.end_date"
                type="date"
                :min="form.start_date || minDate"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-blue"
              />
            </div>
            
            <div v-if="calculatedDays > 0" class="bg-blue-50 p-3 rounded-md">
              <p class="text-sm text-primary-blue">
                申請日数: <span class="font-semibold">{{ calculatedDays }}日</span>
              </p>
            </div>
          </div>
        </div>


        <!-- Reason -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-lg font-semibold text-text-primary mb-4">申請理由</h2>
          
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">
              理由 <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="form.reason"
              rows="4"
              maxlength="500"
              placeholder="申請理由を入力してください"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-blue"
            ></textarea>
            <p class="text-xs text-text-secondary mt-1">
              {{ form.reason.length }}/500文字
            </p>
          </div>
        </div>

        <!-- Validation Messages -->
        <div v-if="validationErrors.length > 0" class="bg-red-50 border border-red-200 rounded-md p-4">
          <ul class="text-sm text-red-600 space-y-1">
            <li v-for="error in validationErrors" :key="error">
              {{ error }}
            </li>
          </ul>
        </div>

        <!-- Submit Button -->
        <div class="flex space-x-4">
          <button
            type="button"
            @click="goBack"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-text-primary hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500"
          >
            キャンセル
          </button>
          
          <button
            type="submit"
            :disabled="loading || !isFormValid"
            class="flex-1 px-4 py-2 bg-primary-blue text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-primary-blue disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="loading">申請中...</span>
            <span v-else>申請する</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { usePaidLeaveStore } from '../stores/paidLeave'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const paidLeaveStore = usePaidLeaveStore()
const authStore = useAuthStore()

const paidLeaveStatus = ref({})
const loading = ref(false)
const validationErrors = ref([])

const form = ref({
  start_date: '',
  end_date: '',
  reason: ''
})

const minDate = computed(() => {
  return new Date().toISOString().split('T')[0]
})

const calculatedDays = computed(() => {
  if (!form.value.start_date || !form.value.end_date) return 0
  
  const startDate = new Date(form.value.start_date)
  const endDate = new Date(form.value.end_date)
  
  if (startDate > endDate) return 0
  
  const diffTime = endDate.getTime() - startDate.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
  
  return diffDays
})

const isFormValid = computed(() => {
  return form.value.start_date &&
         form.value.end_date &&
         form.value.reason.trim() &&
         calculatedDays.value > 0
})

const validateForm = () => {
  validationErrors.value = []
  
  if (!form.value.start_date) {
    validationErrors.value.push('開始日を入力してください')
  }
  
  if (!form.value.end_date) {
    validationErrors.value.push('終了日を入力してください')
  }
  
  if (form.value.start_date && form.value.end_date) {
    const startDate = new Date(form.value.start_date)
    const endDate = new Date(form.value.end_date)
    
    if (startDate > endDate) {
      validationErrors.value.push('終了日は開始日以降を選択してください')
    }
    
    if (startDate < new Date()) {
      validationErrors.value.push('開始日は今日以降を選択してください')
    }
  }
  
  if (!form.value.reason.trim()) {
    validationErrors.value.push('申請理由を入力してください')
  }
  
  if (calculatedDays.value > (paidLeaveStatus.value.remaining_days || 0)) {
    validationErrors.value.push('申請日数が残り日数を超えています')
  }
  
  return validationErrors.value.length === 0
}

const submitApplication = async () => {
  if (!validateForm()) return
  
  loading.value = true
  validationErrors.value = []
  
  try {
    const applicationData = {
      start_date: form.value.start_date,
      end_date: form.value.end_date,
      reason: form.value.reason.trim()
    }
    
    // 独自アプリで申請を送信
    const result = await paidLeaveStore.applyPaidLeave(applicationData)
    
    // 申請成功時の処理
    alert('有給休暇申請が完了しました。')
    router.push('/paid-leave/status')
    
  } catch (error) {
    console.error('Application submission failed:', error)
    
    if (error.response?.data?.message) {
      validationErrors.value.push(error.response.data.message)
    } else {
      validationErrors.value.push('申請に失敗しました。再度お試しください。')
    }
  } finally {
    loading.value = false
  }
}

const loadPaidLeaveStatus = async () => {
  try {
    const userId = authStore.user?.id
    if (userId) {
      const status = await paidLeaveStore.fetchPaidLeaveStatus(userId)
      paidLeaveStatus.value = status
    }
  } catch (error) {
    console.error('Failed to load paid leave status:', error)
  }
}

const goBack = () => {
  router.push('/paid-leave/status')
}

onMounted(() => {
  loadPaidLeaveStatus()
})
</script>
