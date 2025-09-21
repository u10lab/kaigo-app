# Bolt.new Prompt - Home Care Station Business Improvement App (Complete Version)

## Project Overview

Create a frontend for a **Home Care Station Back Office Business Improvement App** using Vue 3 + Composition API.

## Technical Requirements

- **Framework**: Vue 3.4.x + Composition API
- **State Management**: Pinia 2.x
- **HTTP Communication**: Axios 1.7.x
- **Styling**: Tailwind CSS
- **Build Tool**: Vite 5.x
- **Language**: JavaScript ES2023 (NO TypeScript - Do NOT create .ts files)

## Backend API Specifications (Complete Version)

### Authentication API
- `POST /api/login` - Login (email + password)
- `POST /api/logout` - Logout
- `GET /api/user` - Get user information

### freee Integration API (Implemented)
- `GET /api/freee/auth-url` - Get freee authentication URL
- `GET /api/freee/callback` - freee authentication callback
- `POST /api/freee/clock-in` - Clock in
- `POST /api/freee/clock-out` - Clock out
- `GET /api/freee/work-records` - Get work records
- `GET /api/freee/payroll` - Get payroll data

### Service Records API (To be implemented)
- `POST /api/service/start` - Start service
- `POST /api/service/{id}/end` - End service
- `GET /api/service/records` - Get service records

### Payroll Calculation API (To be implemented)
- `GET /api/payroll/calculate` - Get payroll calculation results

### Paid Leave Management API (To be implemented)
- `GET /api/paid-leave/{userId}` - Get individual paid leave status
- `GET /api/paid-leave/all` - Get all employees' paid leave list
- `POST /api/paid-leave/use` - Record paid leave usage

### CSV Export API (To be implemented)
- `GET /api/export/care-insurance` - Export care insurance billing CSV

## Payroll Calculation System

### Payroll Structure
- **Base Salary**: Retrieved from freee API (hourly wage system)
- **Service-specific Hourly Rate Variations**: Managed by the app
- **Payment Unit**: 30-minute increments

### Payroll Calculation Logic
```
Final Payroll = freee Base Salary + Service-specific Hourly Rate Differences

Example:
- freee Base Salary: 1,000 yen/hour × 8 hours = 8,000 yen
- Service-specific Hourly Rate Variations:
  - Bathing Care: (1,500 yen - 1,000 yen) × 2 hours = +1,000 yen
  - Cleaning: (1,200 yen - 1,000 yen) × 1 hour = +200 yen
- Final Payroll: 8,000 yen + 1,000 yen + 200 yen = 9,200 yen
```

## Screen Configuration

### 1. Login Screen
**File Name**: `LoginView.vue`

**Features**:
- Login with email address + password
- After successful login, redirect based on role:
  - Admin → Admin Dashboard
  - Helper → Helper Main Screen

**Test Accounts**:
- Admin: `admin@test.com` / `password`
- Helper: `helper1@test.com` / `password`

### 2. Helper Main Screen
**File Name**: `HelperMainView.vue`

**Features**:
- **Time Clock Section**:
  - Work Start Button (green, large button)
  - Service Start Button (blue)
  - Service End Button (blue)
  - Work End Button (red, large button)
- **Current Status Display**:
  - Working/Break/End status
  - Current service information (client name, service type)
- **Paid Leave Confirmation**:
  - Remaining days display
  - Annual 5-day requirement progress

### 3. Service Start Screen
**File Name**: `ServiceStartView.vue`

**Features**:
- Client selection (dropdown)
  - A001: Yamada Ichiro (Care Level 2)
  - A002: Tanaka Hanako (Care Level 1)
  - A003: Sasaki Kenichi (Care Level 3)
- Service type selection (dropdown)
  - Bathing Care (1,500 yen/hour)
  - Cleaning (1,200 yen/hour)
  - Daily Living Assistance (1,300 yen/hour)
- Service start button

### 4. Admin Dashboard
**File Name**: `AdminDashboardView.vue`

**Features**:
- **Time Management**:
  - All employees' attendance status list
  - Overtime alert history
- **Payroll Management**:
  - Monthly payroll calculation results
  - Service-specific hourly rate adjustments
- **Care Insurance Billing**:
  - Service records list
  - CSV export button
- **Paid Leave Management**:
  - All employees' paid leave status list
  - Annual 5-day requirement progress
- **freee Integration Settings**:
  - OAuth authentication button
  - Authentication status display

### 5. Payroll Management Screen (Admin)
**File Name**: `PayrollManagementView.vue`

**Features**:
- Monthly payroll calculation results list
- Service-specific hourly rate adjustment display
- Payroll details display
- Manual payroll recalculation button

**API Integration Example**:
```javascript
// Get payroll calculation results
const fetchPayrollData = async (month, userId) => {
  const response = await api.get(`/payroll/calculate?month=${month}&user_id=${userId}`)
  return response.data
}
```

### 6. Paid Leave Management Screen (Admin)
**File Name**: `PaidLeaveManagementView.vue`

**Features**:
- All employees' paid leave status list
- Annual 5-day requirement progress display
- Paid leave usage record registration
- Paid leave grant processing

### 7. Paid Leave Status Screen (Helper)
**File Name**: `PaidLeaveStatusView.vue`

**Features**:
- Personal paid leave remaining days display
- Annual 5-day requirement progress
- Usage history display

### 8. CSV Export Screen (Admin)
**File Name**: `CsvExportView.vue`

**Features**:
- Export period selection
- CSV file download
- Export history display

### 9. Service Records List Screen
**File Name**: `ServiceRecordsView.vue`

**Features**:
- Monthly service records list display
- Filter functionality (by client, by service type)
- CSV export button

## State Management (Pinia)

### 1. AuthStore
```javascript
// stores/auth.js
export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(null)
  const isAuthenticated = computed(() => !!token.value)
  
  const login = async (email, password) => {
    // POST /api/login
  }
  
  const logout = async () => {
    // POST /api/logout
  }
  
  return { user, token, isAuthenticated, login, logout }
})
```

### 2. FreeeStore
```javascript
// stores/freee.js
export const useFreeeStore = defineStore('freee', () => {
  const isAuthenticated = ref(false)
  const workStatus = ref('idle') // 'idle', 'working', 'on_service', 'finished'
  const currentService = ref(null)
  
  const clockIn = async () => {
    // POST /api/freee/clock-in
  }
  
  const clockOut = async () => {
    // POST /api/freee/clock-out
  }
  
  const startService = async (clientId, serviceTypeId) => {
    // Service start logic
  }
  
  const endService = async () => {
    // Service end logic
  }
  
  return { isAuthenticated, workStatus, currentService, clockIn, clockOut, startService, endService }
})
```

### 3. ServiceStore
```javascript
// stores/service.js
export const useServiceStore = defineStore('service', () => {
  const clients = ref([])
  const serviceTypes = ref([])
  const serviceRecords = ref([])
  
  const fetchClients = async () => {
    // Get clients list
  }
  
  const fetchServiceTypes = async () => {
    // Get service types list
  }
  
  const fetchServiceRecords = async (month) => {
    // Get service records list
  }
  
  return { clients, serviceTypes, serviceRecords, fetchClients, fetchServiceTypes, fetchServiceRecords }
})
```

### 4. PayrollStore
```javascript
// stores/payroll.js
export const usePayrollStore = defineStore('payroll', () => {
  const payrollData = ref({})
  const serviceAdjustments = ref([])
  
  const fetchPayrollData = async (month, userId) => {
    // GET /api/payroll/calculate
  }
  
  const recalculatePayroll = async (month, userId) => {
    // Payroll recalculation
  }
  
  return { payrollData, serviceAdjustments, fetchPayrollData, recalculatePayroll }
})
```

### 5. PaidLeaveStore
```javascript
// stores/paidLeave.js
export const usePaidLeaveStore = defineStore('paidLeave', () => {
  const paidLeaveRecords = ref([])
  const userPaidLeaveStatus = ref({})
  
  const fetchPaidLeaveStatus = async (userId) => {
    // GET /api/paid-leave/{userId}
  }
  
  const usePaidLeave = async (userId, date, reason) => {
    // POST /api/paid-leave/use
  }
  
  return { paidLeaveRecords, userPaidLeaveStatus, fetchPaidLeaveStatus, usePaidLeave }
})
```

## Styling Requirements

### Design Policy
- **Mobile First**: Prioritize smartphone usage
- **Large Buttons**: Minimum 44px tap area
- **Clear Color Coding**:
  - Work Start: `bg-green-500`
  - Service Related: `bg-blue-500`
  - Work End: `bg-red-500`
  - Warning: `bg-yellow-500`
- **Simple Screen Layout**: One screen, one function principle

### Color Palette
```css
/* Tailwind CSS Custom Colors */
:root {
  --primary-green: #10B981;
  --primary-blue: #3B82F6;
  --primary-red: #EF4444;
  --primary-yellow: #F59E0B;
  --text-primary: #1F2937;
  --text-secondary: #6B7280;
  --bg-primary: #FFFFFF;
  --bg-secondary: #F9FAFB;
}
```

## Implementation Notes

### 1. Authentication Flow
- Save token to `localStorage` after login
- Add `Authorization: Bearer {token}` header to API requests
- Automatic logout on token expiration

### 2. Error Handling
- Appropriate API error display
- Retry functionality on network errors
- User-friendly error messages

### 3. Responsive Design
- Design based on smartphones (375px+)
- Consider tablet and desktop display
- Support both landscape and portrait orientations

### 4. Accessibility
- Keyboard navigation support
- Screen reader support
- Sufficient contrast ratio

## File Structure

```
src/
├── views/
│   ├── LoginView.vue
│   ├── HelperMainView.vue
│   ├── ServiceStartView.vue
│   ├── AdminDashboardView.vue
│   ├── PayrollManagementView.vue
│   ├── PaidLeaveManagementView.vue
│   ├── PaidLeaveStatusView.vue
│   ├── CsvExportView.vue
│   └── ServiceRecordsView.vue
├── components/
│   ├── ClockButton.vue
│   ├── ServiceSelector.vue
│   ├── StatusDisplay.vue
│   └── Navigation.vue
├── stores/
│   ├── auth.js
│   ├── freee.js
│   ├── service.js
│   ├── payroll.js
│   └── paidLeave.js
├── services/
│   └── api.js
└── router/
    └── index.js
```

## API Communication Implementation Example

```javascript
// services/api.js
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
  },
})

// Request interceptor
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default api
```

## Implementation Priority

### Phase 1: Basic Functions (Highest Priority)
1. Login screen
2. Helper main screen
3. Service start screen
4. Admin dashboard (basic version)

### Phase 2: Payroll Management Functions
1. Payroll management screen
2. Paid leave management screen
3. CSV export screen

### Phase 3: Advanced Functions
1. Overtime alert functionality
2. Detailed report functionality
3. Responsive adjustments

## Important Notes

1. **Unimplemented APIs**: Service records API, payroll calculation API, paid leave management API, and CSV export API are not yet implemented. Backend APIs need to be implemented in parallel when implementing the frontend.

2. **Data Integration**: Consistency between freee API and app data must be maintained.

3. **Error Handling**: For unimplemented API parts, implement appropriate mock data or error handling.

4. **TypeScript Restriction**: Do NOT create .ts files. Use only JavaScript ES2023 (.js files).

## Role-Based Screen Access Control (Updated)

### Helper (Helper) - No Navigation Menu
- **HelperMainView.vue** - Main work screen (time clock & service management)
  - Include link button to paid leave status
- **ServiceStartView.vue** - Service start screen
- **PaidLeaveStatusView.vue** - Personal paid leave status screen

### Admin (Admin) - With Navigation Menu
- **AdminDashboardView.vue** - Dashboard (entry point to all functions)
- **PayrollManagementView.vue** - Payroll management list
- **PayrollDetailView.vue** - Individual employee payroll details (with back button)
- **PaidLeaveManagementView.vue** - Paid leave management list
- **PaidLeaveDetailView.vue** - Individual employee paid leave details (with back button)
- **CsvExportView.vue** - CSV export screen
- **ServiceRecordsView.vue** - Service records list screen

### Additional Decisions Made
- Admin field work is not considered in this implementation
- Service records list screen is admin-only
- Helper screens do not include payroll confirmation
- Detail screens include back buttons
- List screens use navigation for transitions

### Updated File Structure
```
src/views/
├── LoginView.vue
├── HelperMainView.vue
├── ServiceStartView.vue
├── PaidLeaveStatusView.vue
├── AdminDashboardView.vue
├── PayrollManagementView.vue
├── PayrollDetailView.vue          # Newly added
├── PaidLeaveManagementView.vue
├── PaidLeaveDetailView.vue        # Newly added
├── CsvExportView.vue
└── ServiceRecordsView.vue
```

### Backend Integration Status
- **Implemented APIs**: Authentication API, freee API (partial)
- **Unimplemented APIs**: Service Records API, Payroll Calculation API, Paid Leave Management API, CSV Export API
- **Development Approach**: Follow requirement.md development flow for staged implementation

Use this prompt to implement the frontend with Bolt.new. You can achieve the functionality as specified in the requirements document while maintaining consistency with the backend.
