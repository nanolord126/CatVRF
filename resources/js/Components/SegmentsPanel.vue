<template>
  <div class="segments-panel">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">Сегментация клиентов</h3>

      <!-- Фильтры -->
      <div class="mb-6 flex gap-3">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" v-model="criteria.by_value" class="rounded" />
          <span class="text-gray-700 dark:text-gray-300">По стоимости</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" v-model="criteria.by_behavior" class="rounded" />
          <span class="text-gray-700 dark:text-gray-300">По поведению</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" v-model="criteria.by_location" class="rounded" />
          <span class="text-gray-700 dark:text-gray-300">По геолокации</span>
        </label>
      </div>

      <!-- Таблица сегментов -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700">
              <th class="text-left py-3 px-4 text-gray-900 dark:text-white font-bold">Сегмент</th>
              <th class="text-left py-3 px-4 text-gray-900 dark:text-white font-bold">Кол-во клиентов</th>
              <th class="text-left py-3 px-4 text-gray-900 dark:text-white font-bold">Средний LTV</th>
              <th class="text-left py-3 px-4 text-gray-900 dark:text-white font-bold">Чёрн</th>
              <th class="text-left py-3 px-4 text-gray-900 dark:text-white font-bold">Действие</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="segment in segments" :key="segment.id" class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="py-3 px-4 text-gray-900 dark:text-white">{{ segment.name }}</td>
              <td class="py-3 px-4 text-gray-700 dark:text-gray-300">{{ segment.count }}</td>
              <td class="py-3 px-4 text-gray-700 dark:text-gray-300">{{ formatCurrency(segment.avg_ltv) }}</td>
              <td class="py-3 px-4">
                <span v-if="segment.churn_risk" :class="getChurnClass(segment.churn_risk)">
                  {{ (segment.churn_risk * 100).toFixed(1) }}%
                </span>
              </td>
              <td class="py-3 px-4">
                <button @click="compareWithSegment(segment)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                  Сравнить
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Сравнение -->
      <div v-if="comparisonMode" class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
        <h4 class="text-md font-bold text-gray-900 dark:text-white mb-4">Сравнение: {{ selectedSegment.name }}</h4>
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-blue-50 dark:bg-blue-900 rounded p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Выручка</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">+15.2%</div>
          </div>
          <div class="bg-green-50 dark:bg-green-900 rounded p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Заказов</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">+8.9%</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const criteria = ref({
  by_value: true,
  by_behavior: true,
  by_location: false,
})

const segments = ref([])
const comparisonMode = ref(false)
const selectedSegment = ref(null)

onMounted(async () => {
  try {
    const params = new URLSearchParams()
    Object.entries(criteria.value).forEach(([key, value]) => {
      if (value) params.append(key, 'true')
    })
    
    const res = await fetch(`/api/v2/segments?${params}`)
    const data = await res.json()
    segments.value = data.data || []
  } catch (e) {
    console.error('Failed to load segments', e)
  }
})

const formatCurrency = (value) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
  }).format(value)
}

const getChurnClass = (churnRate) => {
  if (churnRate > 0.7) return 'text-red-600 font-bold'
  if (churnRate > 0.5) return 'text-orange-600 font-bold'
  return 'text-green-600'
}

const compareWithSegment = (segment) => {
  selectedSegment.value = segment
  comparisonMode.value = true
}
</script>
