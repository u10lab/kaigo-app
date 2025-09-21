<template>
    <div class="card-enhanced rounded-2xl p-6 mb-6 status-indicator">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-bold text-text-primary">現在の状況</h3>
        <div class="digital-clock text-2xl font-mono">
          {{ currentTime }}
        </div>
      </div>
      
      <div class="flex items-center mb-4">
        <div 
          :class="[
            'w-4 h-4 rounded-full mr-3 transition-all duration-300',
            statusColor,
            status === 'working' ? 'pulse-green' : status === 'on_service' ? 'pulse-blue' : ''
          ]"
        ></div>
        <span class="text-text-primary font-semibold text-lg">{{ statusText }}</span>
      </div>
  
      <div v-if="currentService" class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl border border-blue-100">
        <h4 class="font-bold text-blue-800 mb-2 flex items-center">
          <span class="mr-2">🏥</span>
          サービス実施中
        </h4>
        <p class="text-blue-700 font-medium">{{ currentService.client_name }}</p>
        <p class="text-blue-600 text-sm">{{ currentService.service_type }}</p>
        <p class="text-blue-500 text-xs mt-2 font-mono">
          開始時刻: {{ formatTime(currentService.start_time) }}
        </p>
        <div class="mt-2 text-xs text-blue-400">
          経過時間: {{ getElapsedTime(currentService.start_time) }}
        </div>
      </div>
  
      <div v-if="workStartTime" class="mt-4 p-3 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-100">
        <p class="text-green-700 font-medium flex items-center">
          <span class="mr-2">🌅</span>
          勤務開始: {{ formatTime(workStartTime) }}
        </p>
        <p class="text-green-600 text-xs mt-1 font-mono">
          勤務時間: {{ getElapsedTime(workStartTime) }}
        </p>
      </div>
    </div>
  </template>
  
  <script setup>
  import { computed, ref, onMounted, onUnmounted } from 'vue'
  
  const props = defineProps({
    status: {
      type: String,
      default: 'idle'
    },
    currentService: {
      type: Object,
      default: null
    },
    workStartTime: {
      type: String,
      default: null
    }
  })
  
  const currentTime = ref('')
  let timeInterval = null
  
  const updateTime = () => {
    const now = new Date()
    currentTime.value = now.toLocaleTimeString('ja-JP', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    })
  }
  
  onMounted(() => {
    updateTime()
    timeInterval = setInterval(updateTime, 1000)
  })
  
  onUnmounted(() => {
    if (timeInterval) {
      clearInterval(timeInterval)
    }
  })
  
  const statusColor = computed(() => {
    switch (props.status) {
      case 'working':
        return 'bg-primary-green'
      case 'on_service':
        return 'bg-primary-blue'
      case 'finished':
        return 'bg-gray-400'
      default:
        return 'bg-gray-400'
    }
  })
  
  const statusText = computed(() => {
    switch (props.status) {
      case 'working':
        return props.currentService ? 'サービス実施中' : '勤務中'
      case 'finished':
        return '勤務終了'
      default:
        return '未出勤'
    }
  })
  
  const formatTime = (time) => {
    if (!time) return ''
    return new Date(time).toLocaleTimeString('ja-JP', { 
      hour: '2-digit',
      minute: '2-digit'
    })
  }
  
  const getElapsedTime = (startTime) => {
    if (!startTime) return ''
    const start = new Date(startTime)
    const now = new Date()
    const diff = now - start
    const hours = Math.floor(diff / (1000 * 60 * 60))
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
    return `${hours}時間${minutes}分`
  }
  </script>