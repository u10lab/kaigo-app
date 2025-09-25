import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/LoginView.vue')
  },
  {
    path: '/admin-demo',
    name: 'AdminDemo',
    component: () => import('../views/AdminDashboardView.vue')
  },
  {
    path: '/helper-demo',
    name: 'HelperDemo',
    component: () => import('../views/HelperMainView.vue')
  },
  {
    path: '/helper',
    name: 'HelperMain',
    component: () => import('../views/HelperMainView.vue'),
    meta: { requiresAuth: true, role: 'helper' }
  },
  {
    path: '/service/start',
    name: 'ServiceStart',
    component: () => import('../views/ServiceStartView.vue'),
    meta: { requiresAuth: true, role: 'helper' }
  },
  {
    path: '/paid-leave/status',
    name: 'PaidLeaveStatus',
    component: () => import('../views/PaidLeaveStatusView.vue'),
    meta: { requiresAuth: true, role: 'helper' }
  },
  {
    path: '/paid-leave/application',
    name: 'PaidLeaveApplication',
    component: () => import('../views/PaidLeaveApplicationView.vue'),
    meta: { requiresAuth: true, role: 'helper' }
  },
  {
    path: '/admin',
    name: 'AdminDashboard',
    component: () => import('../views/AdminDashboardView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/payroll',
    name: 'PayrollManagement',
    component: () => import('../views/PayrollManagementView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/payroll/:userId/:month',
    name: 'PayrollDetail',
    component: () => import('../views/PayrollDetailView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/paid-leave',
    name: 'PaidLeaveManagement',
    component: () => import('../views/PaidLeaveManagementView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/paid-leave/:userId',
    name: 'PaidLeaveDetail',
    component: () => import('../views/PaidLeaveDetailView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/paid-leave-approval',
    name: 'PaidLeaveApproval',
    component: () => import('../views/PaidLeaveApprovalView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/csv-export',
    name: 'CsvExport',
    component: () => import('../views/CsvExportView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/service-records',
    name: 'ServiceRecords',
    component: () => import('../views/ServiceRecordsView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// ナビゲーションガード
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  console.log('Router guard - navigating to:', to.path, 'from:', from.path)
  
  // ログインページとデモページへのアクセスを許可
  if (to.path === '/login' || to.path === '/admin-demo' || to.path === '/helper-demo') {
    next()
    return
  }
  
  // 認証初期化完了を待つ
  if (!authStore.isInitialized) {
    console.log('Auth not initialized, waiting...')
    // 初期化完了まで待機
    await new Promise(resolve => {
      const checkInitialized = () => {
        if (authStore.isInitialized) {
          resolve()
        } else {
          setTimeout(checkInitialized, 10)
        }
      }
      checkInitialized()
    })
  }
  
  // 保護されたルートの認証チェック
  if (to.meta.requiresAuth) {
    if (!authStore.isAuthenticated) {
      console.log('Not authenticated, redirecting to login', {
        token: authStore.token,
        user: authStore.user,
        isAuthenticated: authStore.isAuthenticated
      })
      next('/login')
      return
    }
    
    // ロールベースのアクセスチェック
    if (to.meta.role && authStore.user?.role !== to.meta.role) {
      console.log('Insufficient permissions, redirecting based on role')
      if (authStore.user?.role === 'admin') {
        next('/admin')
      } else if (authStore.user?.role === 'helper') {
        next('/helper')
      } else {
        next('/login')
      }
      return
    }
  }
  
  next()
})

export default router