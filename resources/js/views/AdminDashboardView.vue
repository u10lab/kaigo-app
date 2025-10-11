<template>
    <div class="page-container">
      <Navigation />
      <div class="page-overlay"></div>
      
      <!-- freee認証結果メッセージ -->
      <div v-if="freeeAuthMessage" class="message-fixed">
        <div :class="freeeAuthMessage.type === 'success' ? 'message-success' : 'message-error'">
          {{ freeeAuthMessage.text }}
        </div>
      </div>
      
      <div class="page-content">
        <div class="px-4 py-6 sm:px-0">
          <h1 class="page-title-gradient">
            <span>管理者ダッシュボード</span>
          </h1>
          
          <!-- Clickable Function Cards -->
          <div class="grid-1-md-2-lg-4 mb-8">
            <router-link to="/admin/payroll" class="function-card-link">
              <div class="p-8">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="icon-container-green pulse-on-hover">
                      <span class="text-white text-2xl">💰</span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-xl font-bold text-text-primary">給与管理</h3>
                    <p class="text-highlight-blue">→ クリックして詳細へ</p>
                  </div>
                </div>
              </div>
            </router-link>
  
            <router-link to="/admin/paid-leave" class="function-card-link">
              <div class="p-8">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="icon-container-yellow pulse-on-hover">
                      <span class="text-white text-2xl">🏖️</span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-xl font-bold text-text-primary">有給管理</h3>
                    <p class="text-highlight-blue">→ クリックして詳細へ</p>
                  </div>
                </div>
              </div>
            </router-link>
  
            <router-link to="/admin/service-records" class="function-card-link">
              <div class="p-8">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="icon-container-blue pulse-on-hover">
                      <span class="text-white text-2xl">📋</span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-xl font-bold text-text-primary">サービス記録</h3>
                    <p class="text-highlight-blue">→ クリックして詳細へ</p>
                  </div>
                </div>
              </div>
            </router-link>
  
            <router-link to="/admin/csv-export" class="function-card-link">
              <div class="p-8">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="icon-container bg-gradient-to-r from-gray-500 to-gray-600 pulse-on-hover">
                      <span class="text-white text-2xl">📊</span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-xl font-bold text-text-primary">CSV出力</h3>
                    <p class="text-highlight-blue">→ クリックして詳細へ</p>
                  </div>
                </div>
              </div>
            </router-link>
          </div>
  
          <!-- 勤怠修正機能 -->
          <div v-if="freeeAuthenticated" class="function-card rounded-2xl mb-8">
            <div class="px-8 py-6 border-b border-gray-200">
              <h2 class="text-xl font-bold text-text-primary flex items-center">
                <span class="mr-3 text-2xl">⏰</span>
                勤怠修正
              </h2>
            </div>
            <div class="p-8">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 従業員選択 -->
                <div>
                  <label class="block text-sm font-medium text-text-primary mb-2">
                    従業員選択
                  </label>
                  <select
                    v-model="selectedEmployeeId"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option value="">従業員を選択してください</option>
                    <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                      {{ emp.display_name }} (ID: {{ emp.id }})
                    </option>
                  </select>
                </div>

                <!-- 日付選択 -->
                <div>
                  <label class="block text-sm font-medium text-text-primary mb-2">
                    対象日
                  </label>
                  <input
                    v-model="selectedDate"
                    type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>

                <!-- 出勤時刻 -->
                <div>
                  <label class="block text-sm font-medium text-text-primary mb-2">
                    出勤時刻
                  </label>
                  <input
                    v-model="clockInTime"
                    type="time"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>

                <!-- 退勤時刻 -->
                <div>
                  <label class="block text-sm font-medium text-text-primary mb-2">
                    退勤時刻
                  </label>
                  <input
                    v-model="clockOutTime"
                    type="time"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>

                <!-- 休憩開始時刻 -->
                <div>
                  <label class="block text-sm font-medium text-text-primary mb-2">
                    休憩開始時刻
                  </label>
                  <input
                    v-model="breakStartTime"
                    type="time"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>

                <!-- 休憩終了時刻 -->
                <div>
                  <label class="block text-sm font-medium text-text-primary mb-2">
                    休憩終了時刻
                  </label>
                  <input
                    v-model="breakEndTime"
                    type="time"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
              </div>

              <!-- 更新ボタン -->
              <div class="mt-6 text-center">
                <button
                  @click="handleUpdateWorkRecord"
                  :disabled="!canUpdateWorkRecord || isUpdatingWorkRecord"
                  class="px-8 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl hover:from-purple-600 hover:to-indigo-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ isUpdatingWorkRecord ? '更新中...' : '勤怠記録を更新' }}
                </button>
              </div>

              <!-- 更新結果表示 -->
              <div v-if="workRecordUpdateResult" class="mt-4 p-4 rounded-lg" :class="workRecordUpdateResult.type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                <h4 class="font-semibold" :class="workRecordUpdateResult.type === 'success' ? 'text-green-800' : 'text-red-800'">
                  {{ workRecordUpdateResult.title }}
                </h4>
                <p class="text-sm mt-1" :class="workRecordUpdateResult.type === 'success' ? 'text-green-700' : 'text-red-700'">
                  {{ workRecordUpdateResult.message }}
                </p>
              </div>
            </div>
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
                    <h3 class="text-lg font-semibold text-text-primary">従業員管理</h3>
                    <p class="text-sm font-medium text-text-secondary">
                      freee APIから従業員情報を取得・管理
                    </p>
                  </div>
                  <div class="flex gap-3">
                    <button
                      @click="handleFetchEmployees"
                      :disabled="isFetchingEmployees"
                      class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-blue-600 text-white rounded-xl hover:from-indigo-600 hover:to-blue-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {{ isFetchingEmployees ? '取得中...' : '従業員一覧' }}
                    </button>
                    <button
                      @click="handleImportEmployees"
                      :disabled="isImportingEmployees"
                      class="px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-xl hover:from-purple-600 hover:to-pink-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {{ isImportingEmployees ? 'インポート中...' : '従業員インポート' }}
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
                            <th class="px-2 py-1 text-left text-gray-600">名前</th>
                            <th class="px-2 py-1 text-left text-gray-600">メールアドレス</th>
                            <th class="px-2 py-1 text-left text-gray-600">入社日</th>
                          </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                          <tr v-for="emp in employeeUpdateResult.employees" :key="emp.id" class="hover:bg-gray-50">
                            <td class="px-2 py-1 text-gray-900">{{ emp.id }}</td>
                            <td class="px-2 py-1 text-gray-900">{{ emp.display_name }}</td>
                            <td class="px-2 py-1 text-gray-900">{{ emp.email || '未設定' }}</td>
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
import { ref, onMounted, computed } from 'vue'
import { useFreeeStore } from '../stores/freee'
import { usePaidLeaveStore } from '../stores/paidLeave'
import Navigation from '../components/Navigation.vue'

const freeeStore = useFreeeStore()
const paidLeaveStore = usePaidLeaveStore()

const freeeAuthenticated = ref(false)
const freeeAuthMessage = ref(null)

// 従業員管理関連
const isFetchingEmployees = ref(false)
const isImportingEmployees = ref(false)
const employeeUpdateResult = ref(null)

// 勤怠修正関連
const selectedEmployeeId = ref('')
const selectedDate = ref('')
const clockInTime = ref('')
const clockOutTime = ref('')
const breakStartTime = ref('')
const breakEndTime = ref('')
const isUpdatingWorkRecord = ref(false)
const workRecordUpdateResult = ref(null)
const employees = ref([])

// 勤怠修正で更新可能かチェック
const canUpdateWorkRecord = computed(() => {
  return selectedEmployeeId.value &&
         selectedDate.value &&
         clockInTime.value &&
         clockOutTime.value
})

// 認証状態の確認とURLパラメータから認証結果を確認
onMounted(async () => {
  // まず現在の認証状態を確認
  await checkFreeeAuthStatus()

  // 認証済みの場合、従業員一覧を取得
  if (freeeAuthenticated.value) {
    await loadEmployees()
    // 今日の日付をデフォルトに設定
    selectedDate.value = new Date().toISOString().split('T')[0]
  }

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

// 従業員インポート
const handleImportEmployees = async () => {
  isImportingEmployees.value = true
  employeeUpdateResult.value = null
  
  try {
    const result = await freeeStore.importEmployeesFromFreee()
    
    employeeUpdateResult.value = {
      type: 'success',
      title: '従業員インポート完了',
      message: `インポート: ${result.imported_count}名、スキップ: ${result.skipped_count}名（全${result.total_employees}名中）`,
      importedEmployees: result.imported_employees || [],
      skippedEmployees: result.skipped_employees || []
    }
  } catch (error) {
    console.error('Failed to import employees:', error)
    employeeUpdateResult.value = {
      type: 'error',
      title: '従業員インポートエラー',
      message: error.response?.data?.error || '従業員のインポートに失敗しました。'
    }
  } finally {
    isImportingEmployees.value = false
  }
}

// 従業員一覧を取得
const loadEmployees = async () => {
  try {
    const result = await freeeStore.fetchEmployees()
    employees.value = result.data.employees || []
  } catch (error) {
    console.error('Failed to load employees:', error)
  }
}

// 勤怠記録を更新
const handleUpdateWorkRecord = async () => {
  isUpdatingWorkRecord.value = true
  workRecordUpdateResult.value = null

  try {
    const workRecordSegments = []
    const breakRecords = []

    // 出退勤時刻の設定
    if (clockInTime.value && clockOutTime.value) {
      workRecordSegments.push({
        clock_in_at: `${selectedDate.value} ${clockInTime.value}:00`,
        clock_out_at: `${selectedDate.value} ${clockOutTime.value}:00`
      })
    }

    // 休憩時間の設定
    if (breakStartTime.value && breakEndTime.value) {
      breakRecords.push({
        clock_in_at: `${selectedDate.value} ${breakStartTime.value}:00`,
        clock_out_at: `${selectedDate.value} ${breakEndTime.value}:00`
      })
    }

    // freeeStoreのメソッドを使用
    const result = await freeeStore.updateWorkRecord(
      parseInt(selectedEmployeeId.value),
      selectedDate.value,
      workRecordSegments,
      breakRecords
    )

    workRecordUpdateResult.value = {
      type: 'success',
      title: '勤怠記録更新完了',
      message: result.message
    }

    // フォームをリセット
    selectedEmployeeId.value = ''
    clockInTime.value = ''
    clockOutTime.value = ''
    breakStartTime.value = ''
    breakEndTime.value = ''

  } catch (error) {
    console.error('Failed to update work record:', error)
    workRecordUpdateResult.value = {
      type: 'error',
      title: '勤怠記録更新エラー',
      message: error.response?.data?.error || error.message || '勤怠記録の更新に失敗しました。'
    }
  } finally {
    isUpdatingWorkRecord.value = false
  }
}


</script>