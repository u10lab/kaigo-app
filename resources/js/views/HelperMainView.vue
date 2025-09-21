<template>
    <div class="min-h-screen gradient-bg">
      <div class="absolute inset-0 bg-black bg-opacity-10"></div>
      <div class="relative z-10">
      <!-- Status Display -->
      <div class="p-4">
        <StatusDisplay 
          :status="freeeStore.workStatus"
          :current-service="serviceStore.currentService"
          :work-start-time="freeeStore.workStartTime"
          class="float"
        />
      </div>
  
      <!-- Today's Service Records -->
      <div v-if="serviceStore.todayServiceRecords.length > 0" class="p-4">
        <div class="card-enhanced rounded-2xl p-6">
          <h3 class="text-xl font-bold text-text-primary mb-4 flex items-center">
            <span class="mr-3 text-2xl">📋</span>
            本日のサービス記録
          </h3>
          <div class="space-y-2">
            <div
              v-for="(record, index) in serviceStore.todayServiceRecords"
              :key="index"
              class="flex justify-between items-center p-4 bg-gradient-to-r from-gray-50 to-blue-50 rounded-xl border border-gray-100 hover:shadow-md transition-all duration-300"
            >
              <div>
                <p class="text-xs text-text-secondary mb-1 font-medium">{{ record.employee_name || authStore.user?.name }}</p>
                <p class="font-bold text-text-primary text-lg">{{ record.client_name }}</p>
                <p class="text-sm text-blue-600 font-medium">{{ record.service_type }}</p>
              </div>
              <div class="text-right">
                <p class="text-sm text-text-primary font-mono font-medium">
                  {{ formatTime(record.start_time) }} - {{ formatTime(record.end_time) }}
                </p>
                <p class="text-xs text-green-600 font-bold">{{ record.duration }}分</p>
              </div>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Action Buttons -->
      <div class="p-6 space-y-6">
        <ClockButton
          v-if="freeeStore.workStatus === 'idle'"
          type="work-start"
          label="出勤"
          @click="handleClockIn"
        />
  
        <div v-if="freeeStore.workStatus === 'working' && !serviceStore.currentService" class="space-y-4">
          <ClockButton
            type="service-start"
            label="サービス開始"
            @click="navigateToServiceStart"
          />
          
          <ClockButton
            type="work-end"
            label="退勤"
            @click="handleClockOut"
          />
        </div>
  
        <div v-if="freeeStore.workStatus === 'working' && serviceStore.currentService" class="space-y-4">
          <ClockButton
            type="service-end"
            label="サービス終了"
            @click="handleServiceEnd"
          />
        </div>
  
        <div v-if="freeeStore.workStatus === 'finished'" class="text-center p-8">
          <div class="card-enhanced rounded-2xl p-8">
            <div class="text-6xl mb-4">🌙</div>
            <p class="text-2xl font-bold text-text-primary mb-2">お疲れ様でした</p>
            <p class="text-text-secondary">今日も一日ありがとうございました</p>
          </div>
        </div>
      </div>
  
      <!-- Paid Leave Status -->
      <div class="p-4">
        <div class="card-enhanced rounded-2xl p-6">
          <h3 class="text-xl font-bold text-text-primary mb-4 flex items-center">
            <span class="mr-3 text-2xl">🏖️</span>
            有給休暇
          </h3>
          <div class="flex justify-between items-center mb-4">
            <span class="text-text-secondary">残り日数</span>
            <span class="text-3xl font-bold text-primary-blue digital-clock">
              {{ paidLeaveStatus.remaining_days || 0 }}日
            </span>
          </div>
          <div class="flex justify-between items-center mb-4">
            <span class="text-text-secondary">年5日取得義務</span>
            <span :class="[
              'font-bold text-lg',
              (paidLeaveStatus.annual_requirement_used || 0) >= 5 ? 'text-primary-green' : 'text-primary-yellow'
            ]">
              {{ paidLeaveStatus.annual_requirement_used || 0 }}/5日
            </span>
          </div>
          
          <router-link 
            to="/paid-leave/status"
            class="block w-full text-center py-3 px-6 bg-gradient-to-r from-gray-100 to-blue-100 hover:from-gray-200 hover:to-blue-200 rounded-xl text-text-primary font-medium transition-all duration-300 hover:shadow-md"
          >
            詳細を見る
          </router-link>
        </div>
      </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue'
  import { useRouter } from 'vue-router'
  import { useFreeeStore } from '../stores/freee'
  import { useServiceStore } from '../stores/service'
  import { usePaidLeaveStore } from '../stores/paidLeave'
  import { useAuthStore } from '../stores/auth'
  import ClockButton from '../components/ClockButton.vue'
  import StatusDisplay from '../components/StatusDisplay.vue'
  
  const router = useRouter()
  const freeeStore = useFreeeStore()
  const serviceStore = useServiceStore()
  const paidLeaveStore = usePaidLeaveStore()
  const authStore = useAuthStore()
  
  const paidLeaveStatus = ref({})
  
  const handleClockIn = async () => {
    try {
      await freeeStore.clockIn()
    } catch (error) {
      console.error('Clock in failed:', error)
      // デモ用にAPI無しでも動作を許可
      freeeStore.workStatus = 'working'
      freeeStore.workStartTime = new Date().toISOString()
    }
  }
  
  const handleClockOut = async () => {
    try {
      await freeeStore.clockOut()
    } catch (error) {
      console.error('Clock out failed:', error)
      // デモ用にAPI無しでも動作を許可
      freeeStore.workStatus = 'finished'
      freeeStore.currentService = null
    }
  }
  
  const handleServiceEnd = async () => {
    try {
      await serviceStore.endService()
      console.log('Service ended successfully')
    } catch (error) {
      console.error('Service end failed:', error)
    }
  }
  
  const navigateToServiceStart = () => {
    router.push('/service/start')
  }
  
  const formatTime = (time) => {
    if (!time) return ''
    return new Date(time).toLocaleTimeString('ja-JP', { 
      hour: '2-digit', 
      minute: '2-digit' 
    })
  }
  
  onMounted(async () => {
    // 有給休暇ステータスを読み込み
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