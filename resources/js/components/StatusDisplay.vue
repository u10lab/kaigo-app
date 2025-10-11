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
  
      <div v-if="currentService" class="mt-4 info-card-service">
        <h4 class="font-bold text-blue-800 mb-2 flex items-center">
          <span class="mr-2">🏥</span>
          サービス実施中
        </h4>
        <p class="text-blue-700 font-medium">
          サービス内容：{{ currentService?.service_type?.name || currentService?.name || 'サービス実施中' }}
        </p>
      </div>
  
      <div v-if="workStartTime" class="mt-4 info-card-green">
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
      case 'completed':
        return 'bg-gray-500'
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
      case 'completed':
        return '退勤済み'
      default:
        return '未出勤'
    }
  })
  
  const formatTime = (time) => {
    if (!time) return ''
    
    // 時刻文字列（例：09:30）をそのまま返す
    if (typeof time === 'string' && time.includes(':')) {
      return time
    }
    
    // Dateオブジェクトの場合
    if (time instanceof Date) {
      return time.toLocaleTimeString('ja-JP', { 
        hour: '2-digit',
        minute: '2-digit'
      })
    }
    
    // その他の場合は文字列として返す
    return String(time)
  }
  
  const getElapsedTime = (startTime) => {
    if (!startTime) return ''
    
    try {
      let start
      
      // 時刻文字列（例：09:30）の場合、今日の日付と組み合わせる
      if (typeof startTime === 'string' && startTime.includes(':')) {
        const today = new Date().toISOString().split('T')[0] // YYYY-MM-DD形式
        start = new Date(`${today}T${startTime}:00`)
      } else if (startTime instanceof Date) {
        start = startTime
      } else {
        start = new Date(startTime)
      }
      
      // 無効な日付の場合は空文字を返す
      if (isNaN(start.getTime())) {
        return ''
      }
      
      const now = new Date()
      const diff = now - start
      
      // 負の値の場合は0時間0分を返す
      if (diff < 0) {
        return '0時間0分'
      }
      
      const hours = Math.floor(diff / (1000 * 60 * 60))
      const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
      return `${hours}時間${minutes}分`
    } catch (error) {
      console.error('Error calculating elapsed time:', error)
      return ''
    }
  }
  
  // サービス表示名を取得する関数
  const getServiceDisplayName = (service) => {
    if (!service) return ''
    
    // デバッグ用：サービスオブジェクトの構造をログ出力
    console.log('StatusDisplay - currentService:', service)
    console.log('StatusDisplay - service type:', typeof service)
    
    // 文字列の場合（JSON文字列の可能性）
    if (typeof service === 'string') {
      try {
        const parsed = JSON.parse(service)
        return parsed.name || 'サービス実施中'
      } catch (e) {
        return 'サービス実施中'
      }
    }
    
    // オブジェクトの場合
    if (typeof service === 'object') {
      // APIレスポンスのデータ構造に基づいてサービス名を取得
      if (service.serviceType && service.serviceType.name) {
        return service.serviceType.name
      }
      if (service.name) {
        return service.name
      }
      if (service.service_type) {
        return service.service_type
      }
    }
    
    return 'サービス実施中'
  }
  
  // サービス開始時刻を取得する関数
  const getServiceStartTime = (service) => {
    if (!service) return ''
    
    // APIレスポンスのデータ構造に基づいて開始時刻を取得
    if (service.service_start_time) {
      return service.service_start_time
    }
    if (service.start_time) {
      return service.start_time
    }
    if (service.created_at) {
      // created_atから時刻部分を抽出
      const date = new Date(service.created_at)
      return date.toLocaleTimeString('ja-JP', { 
        hour: '2-digit',
        minute: '2-digit'
      })
    }
    
    return ''
  }
  </script>