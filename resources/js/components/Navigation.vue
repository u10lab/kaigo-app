<template>
    <nav class="nav-bar">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <h1 class="text-xl font-semibold text-white">
              ホームケアステーション
            </h1>
          </div>
          
          <div class="flex items-center space-x-4">
            <div v-if="isAdmin" class="hidden md:flex space-x-4">
              <button
                v-for="item in adminNavItems"
                :key="item.name"
                @click="navigateTo(item.to)"
                :class="[
                  'nav-button',
                  $route.path === item.to ? 'nav-button-active' : 'nav-button-inactive'
                ]"
              >
                {{ item.label }}
              </button>
            </div>
  
            <div class="flex items-center space-x-3">
              <span class="text-sm text-slate-200">
                {{ user?.name || user?.email }}
              </span>
              <button
                @click="handleLogout"
                class="text-sm text-slate-200 hover:text-white transition-colors duration-200 px-3 py-1 rounded hover:bg-slate-700"
              >
                ログアウト
              </button>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Mobile menu for admin -->
      <div v-if="isAdmin" class="md:hidden border-t border-slate-700">
        <div class="px-2 pt-2 pb-3 space-y-1">
          <button
            v-for="item in adminNavItems"
            :key="item.name"
            @click="navigateTo(item.to)"
            :class="[
              'nav-button-mobile',
              $route.path === item.to ? 'nav-button-active' : 'nav-button-inactive'
            ]"
          >
            {{ item.label }}
          </button>
        </div>
      </div>
    </nav>
  </template>
  
  <script setup>
  import { computed } from 'vue'
  import { useAuthStore } from '../stores/auth'
  import { useRouter, useRoute } from 'vue-router'
  
  const authStore = useAuthStore()
  const router = useRouter()
  const route = useRoute()
  
  const user = computed(() => authStore.user)
  const isAdmin = computed(() => authStore.isAdmin)
  
  const adminNavItems = [
    { name: 'AdminDashboard', to: '/admin', label: 'ダッシュボード' },
    { name: 'PayrollManagement', to: '/admin/payroll', label: '給与管理' },
    { name: 'PaidLeaveManagement', to: '/admin/paid-leave', label: '有給管理' },
    { name: 'ServiceRecords', to: '/admin/service-records', label: 'サービス記録' },
    { name: 'CsvExport', to: '/admin/csv-export', label: 'CSV出力' }
  ]
  
  const navigateTo = (path) => {
    console.log('Navigating to:', path)
    router.push(path).catch(err => {
      console.error('Navigation error:', err)
    })
  }
  
  const handleLogout = async () => {
    await authStore.logout()
    router.push('/login')
  }
  </script>