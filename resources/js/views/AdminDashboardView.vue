<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-200">
      <Navigation />
      <div class="absolute inset-0 bg-slate-900 bg-opacity-5"></div>
      
      <!-- freee認証結果メッセージ -->
      <div v-if="freeeAuthMessage" class="fixed top-4 right-4 z-50 max-w-sm">
        <div :class="freeeAuthMessage.type === 'success' ? 'bg-green-500' : 'bg-red-500'" 
             class="text-white px-4 py-3 rounded-lg shadow-lg">
          {{ freeeAuthMessage.text }}
        </div>
      </div>
      
      <div class="relative z-10 max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
          <h1 class="text-4xl font-bold text-slate-800 mb-8 text-center">
            <span class="bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent">
              管理者ダッシュボード
            </span>
          </h1>
          
          <!-- Quick Stats -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stats-card overflow-hidden rounded-2xl">
              <div class="p-6">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-emerald-500 rounded-xl flex items-center justify-center shadow-lg">
                      <span class="text-white text-lg">🌅</span>
                    </div>
                  </div>
                  <div class="ml-5 w-0 flex-1">
                    <dl>
                      <dt class="text-sm font-semibold text-text-secondary truncate">
                        出勤中スタッフ
                      </dt>
                      <dd class="text-2xl font-bold text-text-primary">
                        3名
                      </dd>
                    </dl>
                  </div>
                </div>
              </div>
            </div>
  
            <div class="stats-card overflow-hidden rounded-2xl">
              <div class="p-6">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-400 to-purple-500 rounded-xl flex items-center justify-center shadow-lg">
                      <span class="text-white text-lg">🏥</span>
                    </div>
                  </div>
                  <div class="ml-5 w-0 flex-1">
                    <dl>
                      <dt class="text-sm font-semibold text-text-secondary truncate">
                        実施中サービス
                      </dt>
                      <dd class="text-2xl font-bold text-text-primary">
                        2件
                      </dd>
                    </dl>
                  </div>
                </div>
              </div>
            </div>
  
            <div class="stats-card overflow-hidden rounded-2xl">
              <div class="p-6">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center shadow-lg">
                      <span class="text-white text-lg">🏖️</span>
                    </div>
                  </div>
                  <div class="ml-5 w-0 flex-1">
                    <dl>
                      <dt class="text-sm font-semibold text-text-secondary truncate">
                        有給未達成者
                      </dt>
                      <dd class="text-2xl font-bold text-text-primary">
                        1名
                      </dd>
                    </dl>
                  </div>
                </div>
              </div>
            </div>
  
            <div class="stats-card overflow-hidden rounded-2xl">
              <div class="p-6">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-r from-red-400 to-pink-500 rounded-xl flex items-center justify-center shadow-lg">
                      <span class="text-white text-lg">⏰</span>
                    </div>
                  </div>
                  <div class="ml-5 w-0 flex-1">
                    <dl>
                      <dt class="text-sm font-semibold text-text-secondary truncate">
                        残業時間（月）
                      </dt>
                      <dd class="text-2xl font-bold text-text-primary">
                        15時間
                      </dd>
                    </dl>
                  </div>
                </div>
              </div>
            </div>
          </div>
  
          <!-- Clickable Function Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <router-link
              to="/admin/payroll"
              class="function-card overflow-hidden rounded-2xl hover:shadow-2xl transition-all duration-300 hover:scale-105 cursor-pointer"
            >
              <div class="p-8">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="w-14 h-14 bg-gradient-to-r from-green-400 to-emerald-500 rounded-2xl flex items-center justify-center shadow-lg pulse-on-hover">
                      <span class="text-white text-2xl">💰</span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-xl font-bold text-text-primary">給与管理</h3>
                    <p class="text-sm text-blue-600 font-medium">→ クリックして詳細へ</p>
                  </div>
                </div>
              </div>
            </router-link>
  
            <router-link
              to="/admin/paid-leave"
              class="function-card overflow-hidden rounded-2xl hover:shadow-2xl transition-all duration-300 hover:scale-105 cursor-pointer"
            >
              <div class="p-8">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="w-14 h-14 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-2xl flex items-center justify-center shadow-lg pulse-on-hover">
                      <span class="text-white text-2xl">🏖️</span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-xl font-bold text-text-primary">有給管理</h3>
                    <p class="text-sm text-blue-600 font-medium">→ クリックして詳細へ</p>
                  </div>
                </div>
              </div>
            </router-link>
  
            <router-link
              to="/admin/service-records"
              class="function-card overflow-hidden rounded-2xl hover:shadow-2xl transition-all duration-300 hover:scale-105 cursor-pointer"
            >
              <div class="p-8">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="w-14 h-14 bg-gradient-to-r from-blue-400 to-purple-500 rounded-2xl flex items-center justify-center shadow-lg pulse-on-hover">
                      <span class="text-white text-2xl">📋</span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-xl font-bold text-text-primary">サービス記録</h3>
                    <p class="text-sm text-blue-600 font-medium">→ クリックして詳細へ</p>
                  </div>
                </div>
              </div>
            </router-link>
  
            <router-link
              to="/admin/csv-export"
              class="function-card overflow-hidden rounded-2xl hover:shadow-2xl transition-all duration-300 hover:scale-105 cursor-pointer"
            >
              <div class="p-8">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="w-14 h-14 bg-gradient-to-r from-gray-500 to-gray-600 rounded-2xl flex items-center justify-center shadow-lg pulse-on-hover">
                      <span class="text-white text-2xl">📊</span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-xl font-bold text-text-primary">CSV出力</h3>
                    <p class="text-sm text-blue-600 font-medium">→ クリックして詳細へ</p>
                  </div>
                </div>
              </div>
            </router-link>
          </div>
  
          <!-- freee Integration -->
          <div class="function-card rounded-2xl">
            <div class="px-8 py-6 border-b border-gray-200">
              <h2 class="text-xl font-bold text-text-primary flex items-center">
                <span class="mr-3 text-2xl">🔗</span>
                freee連携設定
              </h2>
            </div>
            <div class="p-8">
              <!-- 認証ステータス -->
              <div class="flex items-center justify-between mb-6">
                <div>
                  <h3 class="text-lg font-semibold text-text-primary">認証ステータス</h3>
                  <p :class="[
                    'text-sm font-medium',
                    freeeAuthenticated ? 'text-primary-green' : 'text-text-secondary'
                  ]">
                    {{ freeeAuthenticated ? '認証済み' : '未認証' }}
                  </p>
                </div>
                <button
                  v-if="!freeeAuthenticated"
                  @click="handleFreeeAuth"
                  class="px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl hover:from-blue-600 hover:to-purple-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl"
                >
                  freee認証
                </button>
                <span v-else class="px-6 py-3 bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 rounded-xl font-semibold">
                  連携中
                </span>
              </div>

              <!-- 従業員ID管理 -->
              <div v-if="freeeAuthenticated" class="border-t border-gray-200 pt-6">
                <div class="flex items-center justify-between mb-4">
                  <div>
                    <h3 class="text-lg font-semibold text-text-primary">従業員ID管理</h3>
                    <p class="text-sm font-medium text-text-secondary">
                      freee APIから従業員IDを取得して更新
                    </p>
                  </div>
                  <div class="flex gap-3">
                    <button
                      @click="handleUpdateEmployeeIds"
                      :disabled="isUpdatingEmployees"
                      class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {{ isUpdatingEmployees ? '更新中...' : '従業員ID更新' }}
                    </button>
                    <button
                      @click="handleFetchEmployees"
                      :disabled="isFetchingEmployees"
                      class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-blue-600 text-white rounded-xl hover:from-indigo-600 hover:to-blue-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {{ isFetchingEmployees ? '取得中...' : '従業員一覧' }}
                    </button>
                  </div>
                </div>
                
                <!-- 更新結果表示 -->
                <div v-if="employeeUpdateResult" class="p-4 rounded-lg" :class="employeeUpdateResult.type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                  <h4 class="font-semibold" :class="employeeUpdateResult.type === 'success' ? 'text-green-800' : 'text-red-800'">
                    {{ employeeUpdateResult.title }}
                  </h4>
                  <p class="text-sm mt-1" :class="employeeUpdateResult.type === 'success' ? 'text-green-700' : 'text-red-700'">
                    {{ employeeUpdateResult.message }}
                  </p>
                  <!-- 従業員一覧表示 -->
                  <div v-if="employeeUpdateResult.employees && employeeUpdateResult.employees.length > 0" class="mt-4">
                    <h5 class="text-sm font-medium text-blue-700 mb-2">freee API従業員一覧:</h5>
                    <div class="max-h-60 overflow-y-auto">
                      <table class="min-w-full text-xs">
                        <thead class="bg-gray-50">
                          <tr>
                            <th class="px-2 py-1 text-left text-gray-600">ID</th>
                            <th class="px-2 py-1 text-left text-gray-600">従業員番号</th>
                            <th class="px-2 py-1 text-left text-gray-600">名前</th>
                            <th class="px-2 py-1 text-left text-gray-600">入社日</th>
                          </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                          <tr v-for="emp in employeeUpdateResult.employees" :key="emp.id" class="hover:bg-gray-50">
                            <td class="px-2 py-1 text-gray-900">{{ emp.id }}</td>
                            <td class="px-2 py-1 text-gray-900">{{ emp.num }}</td>
                            <td class="px-2 py-1 text-gray-900">{{ emp.display_name }}</td>
                            <td class="px-2 py-1 text-gray-900">{{ emp.entry_date }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                  
                  <!-- マッチングできなかった従業員 -->
                  <div v-if="employeeUpdateResult.notMatched && employeeUpdateResult.notMatched.length > 0" class="mt-2">
                    <p class="text-sm font-medium text-orange-700">マッチングできなかった従業員:</p>
                    <ul class="text-sm text-orange-600 mt-1">
                      <li v-for="emp in employeeUpdateResult.notMatched" :key="emp.freee_id" class="flex justify-between">
                        <span>{{ emp.display_name }} ({{ emp.num }})</span>
                        <span class="text-gray-500">ID: {{ emp.freee_id }}</span>
                      </li>
                    </ul>
                  </div>
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
import { useFreeeStore } from '../stores/freee'
import Navigation from '../components/Navigation.vue'

const freeeStore = useFreeeStore()

const freeeAuthenticated = ref(false)
const freeeAuthMessage = ref(null)

// 従業員ID更新関連
const isUpdatingEmployees = ref(false)
const isFetchingEmployees = ref(false)
const employeeUpdateResult = ref(null)

// 認証状態の確認とURLパラメータから認証結果を確認
onMounted(async () => {
  // まず現在の認証状態を確認
  await checkFreeeAuthStatus()
  
  const urlParams = new URLSearchParams(window.location.search)
  const authStatus = urlParams.get('freee_auth')
  const companyId = urlParams.get('company_id')
  const errorMessage = urlParams.get('message')
  
  if (authStatus === 'success') {
    freeeAuthenticated.value = true
    freeeAuthMessage.value = {
      type: 'success',
      text: `freee認証が完了しました。事業所ID: ${companyId}`
    }
    // 3秒後にメッセージを非表示
    setTimeout(() => {
      freeeAuthMessage.value = null
    }, 3000)
  } else if (authStatus === 'error') {
    freeeAuthMessage.value = {
      type: 'error',
      text: `認証エラー: ${errorMessage}`
    }
    // 5秒後にメッセージを非表示
    setTimeout(() => {
      freeeAuthMessage.value = null
    }, 5000)
  }
})

const checkFreeeAuthStatus = async () => {
  try {
    const authStatus = await freeeStore.checkAuthStatus()
    freeeAuthenticated.value = authStatus.authenticated
    console.log('Freee auth status:', authStatus)
  } catch (error) {
    console.error('Failed to check freee auth status:', error)
    freeeAuthenticated.value = false
  }
}

const handleFreeeAuth = async () => {
  try {
    const authUrl = await freeeStore.getAuthUrl()
    window.location.href = authUrl
  } catch (error) {
    console.error('freee auth failed:', error)
    freeeAuthMessage.value = {
      type: 'error',
      text: '認証URL取得に失敗しました。'
    }
  }
}

// 従業員ID更新
const handleUpdateEmployeeIds = async () => {
  isUpdatingEmployees.value = true
  employeeUpdateResult.value = null
  
  try {
    const result = await freeeStore.updateEmployeeIds()
    
    employeeUpdateResult.value = {
      type: 'success',
      title: '従業員ID更新完了',
      message: `${result.updated_count}名の従業員IDを更新しました。（全${result.total_employees}名中）`,
      notMatched: result.not_matched_employees || []
    }
  } catch (error) {
    console.error('Failed to update employee IDs:', error)
    employeeUpdateResult.value = {
      type: 'error',
      title: '従業員ID更新エラー',
      message: error.response?.data?.error || '従業員IDの更新に失敗しました。'
    }
  } finally {
    isUpdatingEmployees.value = false
  }
}

// 従業員一覧取得
const handleFetchEmployees = async () => {
  isFetchingEmployees.value = true
  employeeUpdateResult.value = null
  
  try {
    const result = await freeeStore.fetchEmployees()
    console.log('Employees data:', result)
    
    employeeUpdateResult.value = {
      type: 'success',
      title: '従業員一覧取得完了',
      message: `${result.data.employees?.length || 0}名の従業員データを取得しました。`,
      employees: result.data.employees || []
    }
  } catch (error) {
    console.error('Failed to fetch employees:', error)
    employeeUpdateResult.value = {
      type: 'error',
      title: '従業員一覧取得エラー',
      message: error.response?.data?.error || '従業員一覧の取得に失敗しました。'
    }
  } finally {
    isFetchingEmployees.value = false
  }
}
</script>