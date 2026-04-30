<template>
  <div class="admin-dashboard min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
            <span class="px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
              Administrator
            </span>
          </div>
          
          <div class="flex items-center space-x-6">
            <!-- System Status -->
            <div class="flex items-center space-x-2">
              <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
              <span class="text-sm text-gray-600">System Online</span>
            </div>
            
            <!-- Quick Actions -->
            <button class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
              Quick Actions
            </button>
            
            <!-- User Menu -->
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                AD
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex space-x-8 overflow-x-auto">
          <button 
            v-for="tab in tabs" 
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === tab.id 
                ? 'border-purple-500 text-purple-600' 
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <div class="flex items-center space-x-2">
              <component :is="tab.icon" class="w-4 h-4" />
              <span>{{ tab.label }}</span>
            </div>
          </button>
        </div>
      </div>
    </nav>

    <!-- Content Area -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Overview Tab -->
      <div v-if="activeTab === 'overview'" class="space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div v-for="stat in stats" :key="stat.label" class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-600">{{ stat.label }}</p>
                <p class="text-2xl font-bold" :style="{ color: stat.color }">{{ stat.value }}</p>
              </div>
              <div :class="stat.iconBg" class="p-3 rounded-lg">
                <component :is="stat.icon" :class="stat.iconColor" class="w-6 h-6" />
              </div>
            </div>
            <p class="mt-2 text-xs text-gray-500">{{ stat.change }}</p>
          </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Traffic Overview</h3>
            <div class="h-64 bg-gray-50 rounded-lg flex items-center justify-center">
              <span class="text-gray-400">Chart placeholder</span>
            </div>
          </div>
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Revenue Trends</h3>
            <div class="h-64 bg-gray-50 rounded-lg flex items-center justify-center">
              <span class="text-gray-400">Chart placeholder</span>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="font-semibold text-gray-900 mb-4">Recent Activity</h3>
          <div class="space-y-4">
            <div v-for="activity in recentActivity" :key="activity.id" class="flex items-center space-x-4 p-3 border-b border-gray-100 last:border-0">
              <div :class="activity.iconBg" class="p-2 rounded-full">
                <component :is="activity.icon" :class="activity.iconColor" class="w-4 h-4" />
              </div>
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">{{ activity.title }}</p>
                <p class="text-xs text-gray-500">{{ activity.description }}</p>
              </div>
              <span class="text-xs text-gray-400">{{ activity.time }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Tenants Tab -->
      <div v-if="activeTab === 'tenants'" class="space-y-6">
        <div class="bg-white rounded-lg shadow">
          <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-semibold text-gray-900">Tenants Management</h3>
            <button class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm">
              Add Tenant
            </button>
          </div>
          <div class="p-4">
            <table class="w-full">
              <thead>
                <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                  <th class="px-4 py-3">Name</th>
                  <th class="px-4 py-3">Status</th>
                  <th class="px-4 py-3">Users</th>
                  <th class="px-4 py-3">Revenue</th>
                  <th class="px-4 py-3">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="tenant in tenants" :key="tenant.id">
                  <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ tenant.name }}</td>
                  <td class="px-4 py-3">
                    <span :class="tenant.statusClass" class="px-2 py-1 rounded-full text-xs font-medium">
                      {{ tenant.status }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-900">{{ tenant.users }}</td>
                  <td class="px-4 py-3 text-sm text-gray-900">{{ formatCurrency(tenant.revenue) }}</td>
                  <td class="px-4 py-3 text-sm">
                    <button class="text-purple-600 hover:text-purple-900">Manage</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Users Tab -->
      <div v-if="activeTab === 'users'" class="space-y-6">
        <div class="bg-white rounded-lg shadow">
          <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-semibold text-gray-900">Users Management</h3>
            <button class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm">
              Add User
            </button>
          </div>
          <div class="p-4">
            <table class="w-full">
              <thead>
                <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                  <th class="px-4 py-3">User</th>
                  <th class="px-4 py-3">Email</th>
                  <th class="px-4 py-3">Role</th>
                  <th class="px-4 py-3">Tenant</th>
                  <th class="px-4 py-3">Status</th>
                  <th class="px-4 py-3">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="user in users" :key="user.id">
                  <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ user.name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-900">{{ user.email }}</td>
                  <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800">
                      {{ user.role }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-900">{{ user.tenant }}</td>
                  <td class="px-4 py-3">
                    <span :class="user.statusClass" class="px-2 py-1 rounded-full text-xs font-medium">
                      {{ user.status }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm">
                    <button class="text-purple-600 hover:text-purple-900">Edit</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Analytics Tab -->
      <div v-if="activeTab === 'analytics'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Top Verticals by Revenue</h3>
            <div class="space-y-3">
              <div v-for="vertical in topVerticals" :key="vertical.name" class="flex items-center justify-between">
                <span class="text-sm text-gray-900">{{ vertical.name }}</span>
                <span class="text-sm font-medium text-gray-900">{{ formatCurrency(vertical.revenue) }}</span>
              </div>
            </div>
          </div>
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-900 mb-4">User Growth</h3>
            <div class="h-48 bg-gray-50 rounded-lg flex items-center justify-center">
              <span class="text-gray-400">Chart placeholder</span>
            </div>
          </div>
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-900 mb-4">System Performance</h3>
            <div class="space-y-3">
              <div v-for="metric in performanceMetrics" :key="metric.name" class="flex items-center justify-between">
                <span class="text-sm text-gray-900">{{ metric.name }}</span>
                <span class="text-sm font-medium" :style="{ color: metric.status === 'good' ? '#10B981' : '#EF4444' }">
                  {{ metric.value }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Settings Tab -->
      <div v-if="activeTab === 'settings'" class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="font-semibold text-gray-900 mb-4">System Settings</h3>
          <div class="space-y-4">
            <div class="flex items-center justify-between p-4 border rounded-lg">
              <div>
                <p class="font-medium text-gray-900">Maintenance Mode</p>
                <p class="text-sm text-gray-500">Enable maintenance mode for all tenants</p>
              </div>
              <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                Toggle
              </button>
            </div>
            <div class="flex items-center justify-between p-4 border rounded-lg">
              <div>
                <p class="font-medium text-gray-900">Email Notifications</p>
                <p class="text-sm text-gray-500">Send system notifications via email</p>
              </div>
              <button class="px-4 py-2 bg-green-100 text-green-700 rounded-lg text-sm">
                Enabled
              </button>
            </div>
            <div class="flex items-center justify-between p-4 border rounded-lg">
              <div>
                <p class="font-medium text-gray-900">API Rate Limiting</p>
                <p class="text-sm text-gray-500">Configure rate limiting for API endpoints</p>
              </div>
              <button class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm">
                Configure
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const activeTab = ref('overview');

const tabs = [
  { id: 'overview', label: 'Overview', icon: 'HomeIcon' },
  { id: 'tenants', label: 'Tenants', icon: 'BuildingOfficeIcon' },
  { id: 'users', label: 'Users', icon: 'UsersIcon' },
  { id: 'analytics', label: 'Analytics', icon: 'ChartBarIcon' },
  { id: 'settings', label: 'Settings', icon: 'CogIcon' },
];

const stats = ref([
  { label: 'Total Tenants', value: '156', change: '+12 this month', color: '#8B5CF6', icon: 'BuildingOfficeIcon', iconBg: 'bg-purple-100', iconColor: 'text-purple-600' },
  { label: 'Total Users', value: '12,847', change: '+523 this week', color: '#3B82F6', icon: 'UsersIcon', iconBg: 'bg-blue-100', iconColor: 'text-blue-600' },
  { label: 'Total Revenue', value: '24.5M ₽', change: '+18% this month', color: '#10B981', icon: 'CurrencyDollarIcon', iconBg: 'bg-green-100', iconColor: 'text-green-600' },
  { label: 'Active Sessions', value: '1,234', change: '+89 now', color: '#F59E0B', icon: 'GlobeIcon', iconBg: 'bg-yellow-100', iconColor: 'text-yellow-600' },
]);

const recentActivity = ref([
  { id: 1, title: 'New tenant registered', description: 'ООО Новая Компания joined the platform', time: '2 min ago', icon: 'UserPlusIcon', iconBg: 'bg-green-100', iconColor: 'text-green-600' },
  { id: 2, title: 'Payment processed', description: 'Payment of 125,000 ₽ processed successfully', time: '5 min ago', icon: 'CheckCircleIcon', iconBg: 'bg-blue-100', iconColor: 'text-blue-600' },
  { id: 3, title: 'System alert', description: 'High CPU usage detected on server-02', time: '15 min ago', icon: 'ExclamationTriangleIcon', iconBg: 'bg-yellow-100', iconColor: 'text-yellow-600' },
  { id: 4, title: 'User updated', description: 'Admin user updated tenant settings', time: '1 hour ago', icon: 'PencilIcon', iconBg: 'bg-purple-100', iconColor: 'text-purple-600' },
]);

const tenants = ref([
  { id: 1, name: 'ООО Пример Компания', status: 'Active', statusClass: 'bg-green-100 text-green-800', users: 45, revenue: 1250000 },
  { id: 2, name: 'ИП Иванов', status: 'Active', statusClass: 'bg-green-100 text-green-800', users: 12, revenue: 320000 },
  { id: 3, name: 'АО СтройМастер', status: 'Pending', statusClass: 'bg-yellow-100 text-yellow-800', users: 8, revenue: 0 },
  { id: 4, name: 'ООО ТехноПром', status: 'Suspended', statusClass: 'bg-red-100 text-red-800', users: 23, revenue: 450000 },
]);

const users = ref([
  { id: 1, name: 'Admin User', email: 'admin@example.com', role: 'Super Admin', tenant: 'System', status: 'Active', statusClass: 'bg-green-100 text-green-800' },
  { id: 2, name: 'Manager One', email: 'manager1@example.com', role: 'Manager', tenant: 'ООО Пример', status: 'Active', statusClass: 'bg-green-100 text-green-800' },
  { id: 3, name: 'Staff Two', email: 'staff2@example.com', role: 'Staff', tenant: 'ООО Пример', status: 'Inactive', statusClass: 'bg-gray-100 text-gray-800' },
]);

const topVerticals = ref([
  { name: 'Restaurant', revenue: 8500000 },
  { name: 'Beauty', revenue: 6200000 },
  { name: 'Hotels', revenue: 4800000 },
  { name: 'Fashion', revenue: 3200000 },
  { name: 'Dental', revenue: 1800000 },
]);

const performanceMetrics = ref([
  { name: 'Response Time', value: '45ms', status: 'good' },
  { name: 'Uptime', value: '99.9%', status: 'good' },
  { name: 'Error Rate', value: '0.02%', status: 'good' },
  { name: 'Memory Usage', value: '78%', status: 'warning' },
  { name: 'Disk Usage', value: '45%', status: 'good' },
]);

function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(amount);
}
</script>
