<template>
  <div class="space-y-6">
    <!-- Marketing Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">Активные кампании</p>
        <p class="text-2xl font-bold text-blue-600">{{ stats.activeCampaigns }}</p>
        <p class="mt-2 text-xs text-gray-500">{{ stats.totalCampaigns }} всего</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">Бюджет за месяц</p>
        <p class="text-2xl font-bold text-green-600">{{ formatCurrency(stats.budget) }}</p>
        <p class="mt-2 text-xs text-gray-500">{{ stats.budgetSpent }} потрачено</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">Конверсия</p>
        <p class="text-2xl font-bold text-yellow-600">{{ stats.conversion }}%</p>
        <p class="mt-2 text-xs text-green-600">+{{ stats.conversionChange }}% vs прошлый</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-600">ROI</p>
        <p class="text-2xl font-bold" :class="stats.roi >= 100 ? 'text-green-600' : 'text-red-600'">{{ stats.roi }}%</p>
        <p class="mt-2 text-xs text-gray-500">Возврат инвестиций</p>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <button class="p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow text-left">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M3.413 9.352a1.76 1.76 0 012.514 2.22l2.147 6.15M6.827 19.24a1.76 1.76 0 002.514-2.22l-2.147-6.15" />
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-900">Создать кампанию</p>
            <p class="text-sm text-gray-500">Новая реклама</p>
          </div>
        </div>
      </button>
      <button class="p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow text-left">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-green-100 rounded-lg">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-900">Промокоды</p>
            <p class="text-sm text-gray-500">Управление</p>
          </div>
        </div>
      </button>
      <button class="p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow text-left">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-purple-100 rounded-lg">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-900">Рассылки</p>
            <p class="text-sm text-gray-500">Email/SMS/Push</p>
          </div>
        </div>
      </button>
      <button class="p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow text-left">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-yellow-100 rounded-lg">
            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-900">Аналитика</p>
            <p class="text-sm text-gray-500">Отчеты</p>
          </div>
        </div>
      </button>
    </div>

    <!-- Active Campaigns -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-gray-900">Активные кампании</h3>
          <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
            Все кампании
          </button>
        </div>
      </div>
      <div class="p-4">
        <div v-for="campaign in campaigns" :key="campaign.id" class="p-4 border rounded-lg mb-4 last:mb-0 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-3">
            <div>
              <p class="font-medium text-gray-900">{{ campaign.name }}</p>
              <p class="text-sm text-gray-500">{{ campaign.channel }} • {{ campaign.period }}</p>
            </div>
            <span :class="getCampaignStatusClass(campaign.status)" class="px-3 py-1 rounded-full text-xs font-medium">
              {{ getCampaignStatusLabel(campaign.status) }}
            </span>
          </div>
          <div class="grid grid-cols-4 gap-4 mt-3">
            <div>
              <p class="text-sm text-gray-500">Бюджет</p>
              <p class="font-semibold text-gray-900">{{ formatCurrency(campaign.budget) }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Потрачено</p>
              <p class="font-semibold text-gray-900">{{ formatCurrency(campaign.spent) }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Клики</p>
              <p class="font-semibold text-blue-600">{{ campaign.clicks }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Конверсия</p>
              <p class="font-semibold text-green-600">{{ campaign.conversion }}%</p>
            </div>
          </div>
          <div class="mt-3">
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="h-2 rounded-full bg-blue-600" :style="{ width: campaign.progress + '%' }"></div>
            </div>
            <p class="text-xs text-gray-500 mt-1">{{ campaign.progress }}% бюджета использовано</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Promo Codes -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-gray-900">Промокоды</h3>
          <button class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
            Создать промокод
          </button>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Код</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Скидка</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Использований</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действителен до</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="promo in promos" :key="promo.id">
              <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                {{ promo.code }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                {{ promo.discount }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ promo.usage }} / {{ promo.maxUsage }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ promo.expiresAt }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getPromoStatusClass(promo.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                  {{ getPromoStatusLabel(promo.status) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const stats = ref({
  activeCampaigns: 4,
  totalCampaigns: 12,
  budget: 150000,
  budgetSpent: 87500,
  conversion: 3.2,
  conversionChange: 0.5,
  roi: 245,
});

const campaigns = ref([
  {
    id: 1,
    name: 'Весеннее меню',
    channel: 'Яндекс.Директ',
    period: '01.04 - 30.04.2026',
    status: 'active',
    budget: 50000,
    spent: 32500,
    clicks: 1245,
    conversion: 2.8,
    progress: 65,
  },
  {
    id: 2,
    name: 'День рождения',
    channel: 'Email рассылка',
    period: '25.04 - 30.04.2026',
    status: 'active',
    budget: 15000,
    spent: 5000,
    clicks: 450,
    conversion: 5.2,
    progress: 33,
  },
  {
    id: 3,
    name: 'Бизнес-ланч',
    channel: 'VK Реклама',
    period: '20.04 - 10.05.2026',
    status: 'active',
    budget: 60000,
    spent: 45000,
    clicks: 2100,
    conversion: 3.5,
    progress: 75,
  },
]);

const promos = ref([
  { id: 1, code: 'SPRING2026', discount: '15%', usage: 234, maxUsage: 500, expiresAt: '30.04.2026', status: 'active' },
  { id: 2, code: 'WELCOME10', discount: '10%', usage: 89, maxUsage: 1000, expiresAt: '31.12.2026', status: 'active' },
  { id: 3, code: 'B2B20', discount: '20%', usage: 45, maxUsage: 100, expiresAt: '15.05.2026', status: 'active' },
]);

function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(amount);
}

function getCampaignStatusClass(status: string): string {
  const classes = {
    active: 'bg-green-100 text-green-800',
    paused: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-gray-100 text-gray-800',
  };
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800';
}

function getCampaignStatusLabel(status: string): string {
  const labels = {
    active: 'Активна',
    paused: 'Пауза',
    completed: 'Завершена',
  };
  return labels[status as keyof typeof labels] || status;
}

function getPromoStatusClass(status: string): string {
  const classes = {
    active: 'bg-green-100 text-green-800',
    expired: 'bg-red-100 text-red-800',
    disabled: 'bg-gray-100 text-gray-800',
  };
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800';
}

function getPromoStatusLabel(status: string): string {
  const labels = {
    active: 'Активен',
    expired: 'Истек',
    disabled: 'Отключен',
  };
  return labels[status as keyof typeof labels] || status;
}
</script>
