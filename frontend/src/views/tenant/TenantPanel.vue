<template>
  <div class="tenant-panel min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <h1 class="text-2xl font-bold text-gray-900">{{ tenantName }}</h1>
            <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
              Активный
            </span>
          </div>
          
          <div class="flex items-center space-x-6">
            <!-- Wallet Balance - Clickable -->
            <button 
              @click="openWalletModal"
              class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all cursor-pointer shadow-md"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
              <span class="font-semibold">{{ formatBalance(walletBalance) }} ₽</span>
            </button>
            
            <!-- Notifications -->
            <button class="relative p-2 text-gray-600 hover:text-gray-900">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <span v-if="unreadNotifications > 0" class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>
            
            <!-- User Menu -->
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                {{ userInitials }}
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
                ? 'border-blue-500 text-blue-600' 
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
      <!-- CRM / Orders Tab -->
      <div v-if="activeTab === 'orders'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div v-for="stat in orderStats" :key="stat.label" class="bg-white rounded-lg shadow p-6">
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- B2B Orders -->
          <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b border-gray-200">
              <h3 class="font-semibold text-gray-900">B2B Заказы</h3>
            </div>
            <div class="p-4">
              <div v-for="order in b2bOrders" :key="order.id" 
                   class="py-3 border-b border-gray-100 last:border-0"
                   :style="{ borderLeft: `4px solid ${B2B_COLOR}` }">
                <div class="flex justify-between items-start">
                  <div>
                    <p class="font-medium text-gray-900">{{ order.company }}</p>
                    <p class="text-sm text-gray-500">{{ order.items }} позиций</p>
                  </div>
                  <span class="px-2 py-1 rounded text-xs font-medium"
                        :style="{ backgroundColor: B2B_COLORS.light, color: B2B_COLORS.text }">
                    {{ formatCurrency(order.amount) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- B2C Orders -->
          <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b border-gray-200">
              <h3 class="font-semibold text-gray-900">B2C Заказы</h3>
            </div>
            <div class="p-4">
              <div v-for="order in b2cOrders" :key="order.id"
                   class="py-3 border-b border-gray-100 last:border-0"
                   :style="{ borderLeft: `4px solid ${B2C_COLOR}` }">
                <div class="flex justify-between items-start">
                  <div>
                    <p class="font-medium text-gray-900">{{ order.customer }}</p>
                    <p class="text-sm text-gray-500">{{ order.items }} позиций</p>
                  </div>
                  <span class="px-2 py-1 rounded text-xs font-medium"
                        :style="{ backgroundColor: B2C_COLORS.light, color: B2C_COLORS.text }">
                    {{ formatCurrency(order.amount) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Staff Tab -->
      <div v-if="activeTab === 'staff'" class="space-y-6">
        <StaffView />
      </div>

      <!-- Inventory Tab -->
      <div v-if="activeTab === 'inventory'" class="space-y-6">
        <InventoryView />
      </div>

      <!-- Warehouse Tab -->
      <div v-if="activeTab === 'warehouse'" class="space-y-6">
        <WarehouseView :b2b-color="B2B_COLOR" :b2c-color="B2C_COLOR" />
      </div>

      <!-- Salaries Tab -->
      <div v-if="activeTab === 'salaries'" class="space-y-6">
        <SalariesView />
      </div>

      <!-- HR Tab -->
      <div v-if="activeTab === 'hr'" class="space-y-6">
        <HRView />
      </div>

      <!-- Marketing Tab -->
      <div v-if="activeTab === 'marketing'" class="space-y-6">
        <MarketingView />
      </div>

      <!-- Settings Tab -->
      <div v-if="activeTab === 'settings'" class="space-y-6">
        <SettingsView />
      </div>

      <!-- Documents Tab -->
      <div v-if="activeTab === 'documents'" class="space-y-6">
        <DocumentsView />
      </div>

      <!-- Compliance Tab -->
      <div v-if="activeTab === 'compliance'" class="space-y-6">
        <ComplianceView />
      </div>

      <!-- CatFloat Rewards Tab -->
      <div v-if="activeTab === 'catfloat'" class="space-y-6">
        <CatFloatView />
      </div>
    </main>

    <!-- Wallet Modal -->
    <WalletModal v-if="showWalletModal" @close="showWalletModal = false" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { B2B_COLOR, B2C_COLOR, B2B_COLORS, B2C_COLORS } from '@/constants/colors';
import StaffView from './components/StaffView.vue';
import InventoryView from './components/InventoryView.vue';
import WarehouseView from './components/WarehouseView.vue';
import SalariesView from './components/SalariesView.vue';
import HRView from './components/HRView.vue';
import MarketingView from './components/MarketingView.vue';
import SettingsView from './components/SettingsView.vue';
import DocumentsView from './components/DocumentsView.vue';
import ComplianceView from './components/ComplianceView.vue';
import CatFloatView from '@/components/bonuses/CatFloatDashboard.vue';

const activeTab = ref('orders');
const showWalletModal = ref(false);
const walletBalance = ref(1250000);
const unreadNotifications = ref(5);
const tenantName = ref('ООО Пример Компания');

const tabs = [
  { id: 'orders', label: 'Заказы (CRM)', icon: 'ShoppingCartIcon' },
  { id: 'staff', label: 'Сотрудники', icon: 'UsersIcon' },
  { id: 'inventory', label: 'Инвентаризация', icon: 'ClipboardListIcon' },
  { id: 'warehouse', label: 'Склад', icon: 'WarehouseIcon' },
  { id: 'salaries', label: 'Зарплаты', icon: 'CurrencyDollarIcon' },
  { id: 'hr', label: 'HR', icon: 'UserGroupIcon' },
  { id: 'marketing', label: 'Маркетинг', icon: 'MegaphoneIcon' },
  { id: 'compliance', label: 'Compliance', icon: 'ShieldCheckIcon' },
  { id: 'analytics', label: 'Аналитика', icon: 'ChartBarIcon' },
  { id: 'catfloat', label: 'CatFloat', icon: 'StarIcon' },
  { id: 'settings', label: 'Настройки', icon: 'CogIcon' },
  { id: 'documents', label: 'Документы', icon: 'DocumentIcon' },
];

const orderStats = ref([
  { label: 'B2B Заказы', value: '234', change: '+12% за месяц', color: B2B_COLOR, icon: 'BriefcaseIcon', iconBg: 'bg-blue-100', iconColor: 'text-blue-600' },
  { label: 'B2C Заказы', value: '1,847', change: '+8% за месяц', color: B2C_COLOR, icon: 'ShoppingBagIcon', iconBg: 'bg-blue-100', iconColor: 'text-blue-500' },
  { label: 'Выручка', value: '4.2M ₽', change: '+15% за месяц', color: '#10B981', icon: 'TrendingUpIcon', iconBg: 'bg-green-100', iconColor: 'text-green-600' },
  { label: 'Конверсия', value: '12.5%', change: '+2% за месяц', color: '#F59E0B', icon: 'ChartBarIcon', iconBg: 'bg-yellow-100', iconColor: 'text-yellow-600' },
]);

const b2bOrders = ref([
  { id: 1, company: 'ООО ТехноПром', items: 45, amount: 125000 },
  { id: 2, company: 'ИП Иванов', items: 12, amount: 45000 },
  { id: 3, company: 'АО СтройМастер', items: 78, amount: 320000 },
]);

const b2cOrders = ref([
  { id: 1, customer: 'Петров П.П.', items: 3, amount: 2500 },
  { id: 2, customer: 'Сидорова А.А.', items: 5, amount: 4200 },
  { id: 3, customer: 'Козлов И.И.', items: 2, amount: 1800 },
]);

const userInitials = computed(() => 'АА');

function formatBalance(balance: number): string {
  return new Intl.NumberFormat('ru-RU').format(balance);
}

function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(amount);
}

function openWalletModal() {
  showWalletModal.value = true;
}

onMounted(() => {
  // Load initial data
});
</script>

onMounted(() => {
  // Load initial data
});
</script>
</script>
