<template>
  <div class="page-container">
    <div class="page-content-4xl">
      <!-- Header -->
      <div class="mb-6">
        <button @click="goBack" class="btn-link-blue mb-4">
          ← 戻る
        </button>
        <h1 class="text-2xl font-bold text-text-primary">有給休暇申請</h1>
      </div>

      <!-- Current Status -->
      <div class="card-enhanced rounded-2xl p-6 mb-6">
        <h2 class="text-lg font-semibold text-text-primary mb-4">現在の状況</h2>
        <div class="grid grid-cols-2 gap-4">
          <div class="text-center">
            <div class="stat-value-xl text-primary-blue">
              {{ paidLeaveStatus.remaining_days || 0 }}
            </div>
            <div class="stat-label-sm">残り日数</div>
          </div>
          <div class="text-center">
            <div class="stat-value-xl text-primary-green">
              {{ paidLeaveStatus.granted_days || 0 }}
            </div>
            <div class="stat-label-sm">年間付与</div>
          </div>
        </div>
      </div>

      <!-- Application Form -->
      <form @submit.prevent="submitApplication" class="space-y-6">
        <!-- Date Selection -->
        <div class="card-enhanced rounded-2xl p-6">
          <h2 class="text-lg font-semibold text-text-primary mb-4">取得日</h2>

          <div class="space-y-4">
            <div>
              <label class="form-label">
                有給休暇取得日 <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.date"
                type="date"
                :min="minDate"
                required
                @input="updateFormDates"
                class="form-input"
              />
            </div>

            <!-- 半日休暇オプション -->
            <div v-if="form.date" class="info-card-blue">
              <h3 class="text-sm font-medium text-blue-800 mb-3">半日休暇オプション</h3>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input
                    v-model="form.half_day"
                    type="checkbox"
                    class="mr-2"
                  />
                  <span class="text-sm text-blue-700">
                    半日休暇（午前または午後）
                  </span>
                </label>
                <div v-if="form.half_day" class="ml-6">
                  <label class="block text-sm font-medium text-blue-700 mb-1">
                    休暇時間
                  </label>
                  <select
                    v-model="form.half_day_period"
                    class="form-select"
                  >
                    <option value="morning">午前（9:00-12:00）</option>
                    <option value="afternoon">午後（13:00-17:00）</option>
                  </select>
                </div>
              </div>
            </div>
            
            <div v-if="calculatedDays > 0" class="info-card-blue">
              <p class="text-sm text-primary-blue">
                申請日数: <span class="font-semibold">{{ calculatedDays }}日</span>
              </p>
            </div>
          </div>
        </div>


        <!-- Reason -->
        <div class="card-enhanced rounded-2xl p-6">
          <h2 class="text-lg font-semibold text-text-primary mb-4">申請理由</h2>
          
          <div>
            <label class="form-label">
              理由 <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="form.reason"
              rows="4"
              maxlength="500"
              placeholder="申請理由を入力してください"
              required
              class="form-textarea"
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
          <button type="button" @click="goBack" class="flex-1 btn-secondary">
            キャンセル
          </button>
          
          <button type="submit" :disabled="loading || !isFormValid" class="flex-1 btn-submit">
            <span v-if="loading">申請中...</span>
            <span v-else>申請する</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
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
  date: '',
  start_date: '', // API用に内部で設定
  end_date: '', // API用に内部で設定
  reason: '',
  half_day: false,
  half_day_period: 'morning'
})

const minDate = computed(() => {
  return new Date().toISOString().split('T')[0]
})

const calculatedDays = computed(() => {
  if (!form.value.date) return 0

  // 半日休暇の場合は0.5日、それ以外は1日
  return form.value.half_day ? 0.5 : 1
})

// 日付選択時にstart_dateとend_dateを自動設定
const updateFormDates = () => {
  if (form.value.date) {
    form.value.start_date = form.value.date
    form.value.end_date = form.value.date
  }
}

const isFormValid = computed(() => {
  return form.value.date &&
         form.value.reason.trim() &&
         calculatedDays.value > 0
})

const validateForm = () => {
  validationErrors.value = []

  if (!form.value.date) {
    validationErrors.value.push('有給休暇取得日を入力してください')
  }

  if (form.value.date) {
    const selectedDate = new Date(form.value.date)
    const today = new Date()
    today.setHours(0, 0, 0, 0)

    if (selectedDate < today) {
      validationErrors.value.push('取得日は今日以降を選択してください')
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

  // 日付を自動設定
  updateFormDates()

  loading.value = true
  validationErrors.value = []

  try {
    const applicationData = {
      start_date: form.value.start_date,
      end_date: form.value.end_date,
      reason: form.value.reason.trim(),
      half_day: form.value.half_day,
      half_day_period: form.value.half_day_period
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
