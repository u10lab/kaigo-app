<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-200">
      <Navigation />
      <div class="absolute inset-0 bg-slate-900 bg-opacity-5"></div>
      
      <div class="relative z-10 max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
          <h1 class="text-4xl font-bold text-slate-800 mb-8 text-center">
            <span class="bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent">
              有給休暇管理
            </span>
          </h1>
  
          <!-- Action Buttons -->
          <div class="flex justify-center space-x-4 mb-8">
            <button
              @click="goToApproval"
              class="px-6 py-3 bg-primary-blue text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-primary-blue transition-colors duration-200"
            >
              申請承認画面へ
            </button>
          </div>

          <!-- Summary Stats -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="card-enhanced overflow-hidden rounded-2xl hover:scale-105 transition-transform duration-300">
              <div class="p-5">
                <dt class="text-sm font-medium text-text-secondary truncate">
                  年5日未達成者
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-primary-red">
                  {{ unachievedCount }}名
                </dd>
              </div>
            </div>
            
            <div class="card-enhanced overflow-hidden rounded-2xl hover:scale-105 transition-transform duration-300">
              <div class="p-5">
                <dt class="text-sm font-medium text-text-secondary truncate">
                  平均取得日数
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-primary-blue">
                  {{ averageUsed.toFixed(1) }}日
                </dd>
              </div>
            </div>
            
            <div class="card-enhanced overflow-hidden rounded-2xl hover:scale-105 transition-transform duration-300">
              <div class="p-5">
                <dt class="text-sm font-medium text-text-secondary truncate">
                  対象従業員数
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-text-primary">
                  {{ employeeLeaveStatus.length }}名
                </dd>
              </div>
            </div>
          </div>
  
          <!-- Employee List -->
          <div class="card-enhanced shadow overflow-hidden rounded-2xl">
            <div class="px-4 py-5 sm:px-6">
              <h2 class="text-lg leading-6 font-medium text-text-primary">
                従業員別有給取得状況
              </h2>
            </div>
            
            <ul class="divide-y divide-gray-200">
              <li
                v-for="employee in employeeLeaveStatus"
                :key="employee.user_id"
                class="cursor-pointer hover:bg-gray-50"
                @click="viewEmployeeDetail(employee.user_id)"
              >
                <div class="px-4 py-4 sm:px-6">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center">
                      <div>
                        <p class="text-sm font-medium text-text-primary">
                          {{ employee.user_name || employee.name }}
                        </p>
                        <p class="text-sm text-text-secondary">
                          残り{{ employee.remaining_days }}日 / {{ employee.granted_days }}日
                        </p>
                      </div>
                    </div>
                    
                    <div class="flex items-center space-x-6">
                      <!-- Annual Requirement Progress -->
                      <div class="text-center">
                        <div class="text-sm text-text-secondary mb-1">年5日義務</div>
                        <div class="flex items-center">
                          <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                            <div 
                              :class="[
                                'h-2 rounded-full transition-all',
                                employee.annual_requirement_used >= 5 ? 'bg-primary-green' : 'bg-primary-yellow'
                              ]"
                              :style="{ width: `${Math.min((employee.annual_requirement_used / 5) * 100, 100)}%` }"
                            ></div>
                          </div>
                          <span :class="[
                            'text-sm font-medium',
                            employee.annual_requirement_used >= 5 ? 'text-primary-green' : 'text-primary-yellow'
                          ]">
                            {{ employee.annual_requirement_used }}/5
                          </span>
                        </div>
                      </div>
                      
                      <!-- Status Badge -->
                      <div>
                        <span :class="[
                          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                          employee.annual_requirement_used >= 5 
                            ? 'bg-green-100 text-green-800' 
                            : 'bg-yellow-100 text-yellow-800'
                        ]">
                          {{ employee.annual_requirement_used >= 5 ? '達成' : '未達成' }}
                        </span>
                      </div>
                      
                      <div class="text-primary-blue">
                        →
                      </div>
                    </div>
                  </div>
                </div>
              </li>
            </ul>
  
            <div v-if="employeeLeaveStatus.length === 0" class="text-center py-12">
              <p class="text-text-secondary">データがありません</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted } from 'vue'
  import { useRouter } from 'vue-router'
  import { usePaidLeaveStore } from '../stores/paidLeave'
  import Navigation from '../components/Navigation.vue'
  
  const router = useRouter()
  const paidLeaveStore = usePaidLeaveStore()
  
  const employeeLeaveStatus = ref([])
  
  const unachievedCount = computed(() => {
    return employeeLeaveStatus.value.filter(emp => emp.annual_requirement_used < 5).length
  })
  
  const averageUsed = computed(() => {
    if (employeeLeaveStatus.value.length === 0) return 0
    const total = employeeLeaveStatus.value.reduce((sum, emp) => sum + emp.annual_requirement_used, 0)
    return total / employeeLeaveStatus.value.length
  })
  
  const loadEmployeeLeaveStatus = async () => {
    try {
      const data = await paidLeaveStore.fetchAllEmployeesLeaveStatus()
      employeeLeaveStatus.value = data
    } catch (error) {
      console.error('Failed to load employee leave status:', error)
    }
  }
  
  const viewEmployeeDetail = (userId) => {
    router.push(`/admin/paid-leave/${userId}`)
  }

  const goToApproval = () => {
    router.push('/admin/paid-leave-approval')
  }

  
  onMounted(() => {
    loadEmployeeLeaveStatus()
  })
  </script>