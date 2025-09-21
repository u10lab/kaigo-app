<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-200">
      <Navigation />
      <div class="absolute inset-0 bg-slate-900 bg-opacity-5"></div>
      
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
              <div class="flex items-center justify-between">
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
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue'
  import { useFreeeStore } from '../stores/freee'
  import Navigation from '../components/Navigation.vue'
  
  const freeeStore = useFreeeStore()
  
  const freeeAuthenticated = ref(false)
  
  const handleFreeeAuth = async () => {
    try {
      const authUrl = await freeeStore.getAuthUrl()
      window.location.href = authUrl
    } catch (error) {
      console.error('freee auth failed:', error)
      // デモ用
      freeeAuthenticated.value = true
    }
  }
  </script>