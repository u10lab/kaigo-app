<template>
    <div class="page-container-dark gradient-bg">
      <div class="page-overlay-dark"></div>
      <div class="relative z-10">
        <div class="page-content-4xl">
          <!-- Header with Logout Button -->
          <div class="flex justify-between items-center mb-6">
            <div class="flex items-center">
              <button @click="goBack" class="btn-link-blue text-white hover:text-blue-200 mr-4">
                ← 戻る
              </button>
              <h1 class="text-2xl font-bold text-white">サービス開始</h1>
            </div>
            <button @click="handleLogout" class="logout-button text-sm">
              <span>🚪</span>
              <span>ログアウト</span>
            </button>
          </div>
    
          <!-- Service Form -->
          <div class="card-enhanced rounded-2xl p-6 space-y-6">
            <!-- Client Selection -->
            <div>
              <label class="form-label">
                利用者選択
              </label>
              <select
                v-model="form.clientId"
                class="form-select"
                required
              >
                <option value="">利用者を選択してください</option>
                <option
                  v-for="client in clients"
                  :key="client.id"
                  :value="client.id"
                >
                  {{ client.id }}: {{ client.name }} ({{ client.care_level }})
                </option>
              </select>
            </div>
    
            <!-- Service Type Selection -->
            <div>
              <label class="form-label">
                サービス種別
              </label>
              <select
                v-model="form.serviceTypeId"
                class="form-select"
                required
              >
                <option value="">サービスを選択してください</option>
                <option
                  v-for="serviceType in serviceTypes"
                  :key="serviceType.id"
                  :value="serviceType.id"
                >
                  {{ serviceType.name }} ({{ serviceType.hourly_rate }}円/時)
                </option>
              </select>
            </div>
    
            <!-- Service Details Display -->
            <div v-if="selectedClient && selectedServiceType" class="info-card-blue">
              <h3 class="font-medium text-blue-800 mb-2">サービス詳細</h3>
              <div class="text-blue-700 text-sm space-y-1">
                <p><strong>利用者:</strong> {{ selectedClient.name }}</p>
                <p><strong>要介護度:</strong> {{ selectedClient.care_level }}</p>
                <p><strong>サービス:</strong> {{ selectedServiceType.name }}</p>
                <p><strong>時給:</strong> {{ selectedServiceType.hourly_rate }}円</p>
              </div>
            </div>
    
            <!-- Start Button -->
            <div class="pt-4">
              <ClockButton
                type="service-start"
                label="サービス開始"
                :disabled="!form.clientId || !form.serviceTypeId || loading"
                @click="handleServiceStart"
                class="w-full"
              />
            </div>
    
            <!-- Error Message -->
            <div v-if="error" class="text-red-600 text-sm text-center">
              {{ error }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted } from 'vue'
  import { useRouter } from 'vue-router'
  import { useServiceStore } from '../stores/service'
  import { useAuthStore } from '../stores/auth'
  import ClockButton from '../components/ClockButton.vue'
  
  const router = useRouter()
  const serviceStore = useServiceStore()
  const authStore = useAuthStore()
  
  const form = ref({
    clientId: '',
    serviceTypeId: ''
  })
  
  const loading = ref(false)
  const error = ref('')
  
  const clients = computed(() => serviceStore.clients)
  const serviceTypes = computed(() => serviceStore.serviceTypes)
  
  const selectedClient = computed(() => 
    clients.value.find(client => client.id === form.value.clientId)
  )
  
  const selectedServiceType = computed(() => 
    serviceTypes.value.find(type => type.id === form.value.serviceTypeId)
  )
  
  const handleServiceStart = async () => {
    loading.value = true
    error.value = ''
    
    try {
      await serviceStore.startService(form.value.clientId, form.value.serviceTypeId)
      
      // サービス開始後にヘルパーメイン画面に戻る
      router.push('/helper')
    } catch (err) {
      console.error('Service start failed:', err)
      router.push('/helper')
    } finally {
      loading.value = false
    }
  }
  
  const goBack = () => {
    router.push('/helper')
  }
  
  const handleLogout = async () => {
    try {
      // ログアウト処理
      await authStore.logout()
      
      // ローカルストレージをクリア
      localStorage.removeItem('workStatus')
      localStorage.removeItem('workStartTime')
      
      // ログインページにリダイレクト
      router.push('/login')
    } catch (error) {
      console.error('Logout failed:', error)
      // エラーが発生してもログインページにリダイレクト
      router.push('/login')
    }
  }
  
  onMounted(async () => {
    try {
      await serviceStore.fetchClients()
      await serviceStore.fetchServiceTypes()
    } catch (error) {
      console.error('Failed to load service data:', error)
    }
  })
  </script>
