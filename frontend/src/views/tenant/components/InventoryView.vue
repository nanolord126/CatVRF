<template>
  <div class="space-y-6">
    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <button class="p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow text-left">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-900">Инвентаризация</p>
            <p class="text-sm text-gray-500">Создать</p>
          </div>
        </div>
      </button>
      <button class="p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow text-left">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-green-100 rounded-lg">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-900">Отчеты</p>
            <p class="text-sm text-gray-500">Просмотреть</p>
          </div>
        </div>
      </button>
      <button class="p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow text-left">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-yellow-100 rounded-lg">
            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-900">Расхождения</p>
            <p class="text-sm text-gray-500">Проверить</p>
          </div>
        </div>
      </button>
      <button class="p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow text-left">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-purple-100 rounded-lg">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-900">Категории</p>
            <p class="text-sm text-gray-500">Управление</p>
          </div>
        </div>
      </button>
    </div>

    <!-- Active Inventories -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-gray-900">Активные инвентаризации</h3>
          <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
            Новая инвентаризация
          </button>
        </div>
      </div>
      <div class="p-4">
        <div v-for="inventory in activeInventories" :key="inventory.id" class="p-4 border rounded-lg mb-4 last:mb-0">
          <div class="flex items-center justify-between mb-3">
            <div>
              <p class="font-medium text-gray-900">{{ inventory.name }}</p>
              <p class="text-sm text-gray-500">{{ inventory.location }} • {{ inventory.date }}</p>
            </div>
            <span :class="getInventoryStatusClass(inventory.status)" class="px-3 py-1 rounded-full text-xs font-medium">
              {{ getInventoryStatusLabel(inventory.status) }}
            </span>
          </div>
          <div class="grid grid-cols-3 gap-4 mt-3">
            <div class="text-center">
              <p class="text-2xl font-bold text-gray-900">{{ inventory.itemsCount }}</p>
              <p class="text-sm text-gray-500">Позиций</p>
            </div>
            <div class="text-center">
              <p class="text-2xl font-bold text-blue-600">{{ inventory.progress }}%</p>
              <p class="text-sm text-gray-500">Прогресс</p>
            </div>
            <div class="text-center">
              <p class="text-2xl font-bold" :class="inventory.discrepancies > 0 ? 'text-red-600' : 'text-green-600'">
                {{ inventory.discrepancies }}
              </p>
              <p class="text-sm text-gray-500">Расхождений</p>
            </div>
          </div>
          <div class="mt-3">
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="h-2 rounded-full bg-blue-600" :style="{ width: inventory.progress + '%' }"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Discrepancies -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-900">Недавние расхождения</h3>
      </div>
      <div class="p-4">
        <div v-for="discrepancy in discrepancies" :key="discrepancy.id" class="py-3 border-b border-gray-100 last:border-0">
          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-gray-900">{{ discrepancy.item }}</p>
              <p class="text-sm text-gray-500">SKU: {{ discrepancy.sku }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm text-gray-500">Ожидалось: {{ discrepancy.expected }}</p>
              <p class="text-sm font-medium" :class="discrepancy.actual < discrepancy.expected ? 'text-red-600' : 'text-green-600'">
                Фактически: {{ discrepancy.actual }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const activeInventories = ref([
  {
    id: 1,
    name: 'Ежемесячная инвентаризация Апрель',
    location: 'Основной склад',
    date: '27.04.2026',
    status: 'in_progress',
    itemsCount: 234,
    progress: 75,
    discrepancies: 3,
  },
  {
    id: 2,
    name: 'Инвентаризация холодильного оборудования',
    location: 'Кухня',
    date: '26.04.2026',
    status: 'completed',
    itemsCount: 45,
    progress: 100,
    discrepancies: 0,
  },
  {
    id: 3,
    name: 'Инвентаризация бара',
    location: 'Бар',
    date: '25.04.2026',
    status: 'completed',
    itemsCount: 78,
    progress: 100,
    discrepancies: 5,
  },
]);

const discrepancies = ref([
  { id: 1, item: 'Молоко 3.2%', sku: 'SKU-MILK-001', expected: 50, actual: 45 },
  { id: 2, item: 'Сыр Российский', sku: 'SKU-CHEESE-001', expected: 20, actual: 22 },
  { id: 3, item: 'Яйца category 1', sku: 'SKU-EGG-001', expected: 100, actual: 98 },
]);

function getInventoryStatusClass(status: string): string {
  const classes = {
    pending: 'bg-gray-100 text-gray-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  };
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800';
}

function getInventoryStatusLabel(status: string): string {
  const labels = {
    pending: 'Ожидает',
    in_progress: 'В процессе',
    completed: 'Завершено',
    cancelled: 'Отменено',
  };
  return labels[status as keyof typeof labels] || status;
}
</script>
