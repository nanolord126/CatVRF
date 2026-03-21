<template>
  <div class="analytics-dashboard grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- KPIs Row -->
    <div class="lg:col-span-2 grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="text-gray-600 dark:text-gray-400 text-sm mb-2">Выручка (30д)</div>
        <div class="text-3xl font-bold text-gray-900 dark:text-white">
          {{ formatCurrency(kpis.total_revenue_30d) }}
        </div>
        <div class="text-green-600 text-xs mt-2">+12.5% vs прошлый месяц</div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="text-gray-600 dark:text-gray-400 text-sm mb-2">Заказов (30д)</div>
        <div class="text-3xl font-bold text-gray-900 dark:text-white">
          {{ kpis.total_orders_30d }}
        </div>
        <div class="text-green-600 text-xs mt-2">+8.3% vs прошлый месяц</div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="text-gray-600 dark:text-gray-400 text-sm mb-2">Средний чек</div>
        <div class="text-3xl font-bold text-gray-900 dark:text-white">
          {{ formatCurrency(kpis.avg_order_value) }}
        </div>
        <div class="text-red-600 text-xs mt-2">-2.1% vs прошлый месяц</div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="text-gray-600 dark:text-gray-400 text-sm mb-2">Конверсия</div>
        <div class="text-3xl font-bold text-gray-900 dark:text-white">
          {{ (kpis.conversion_rate * 100).toFixed(2) }}%
        </div>
        <div class="text-green-600 text-xs mt-2">+0.8% vs прошлый месяц</div>
      </div>
    </div>

    <!-- Прогноз на 30 дней -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Прогноз выручки (30 дней)</h3>
      <div class="h-64 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center">
        <div class="text-gray-500 dark:text-gray-400">📊 График выручки (Confidence: {{ forecast.confidence }})</div>
      </div>
    </div>

    <!-- Сравнение периодов -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Сравнение периодов</h3>
      <div class="space-y-3">
        <div class="flex justify-between items-center">
          <span class="text-gray-600 dark:text-gray-400">Выручка</span>
          <span class="text-lg font-bold text-green-600">+15.2%</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-600 dark:text-gray-400">Заказы</span>
          <span class="text-lg font-bold text-green-600">+8.9%</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-600 dark:text-gray-400">Средний чек</span>
          <span class="text-lg font-bold text-red-600">-5.3%</span>
        </div>
      </div>
    </div>

    <!-- Сегменты клиентов -->
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Сегменты клиентов</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="border-l-4 border-blue-500 pl-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">High-Value</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white">125</div>
          <div class="text-xs text-gray-500">Средний LTV: 125,000₽</div>
        </div>
        <div class="border-l-4 border-yellow-500 pl-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">Medium-Value</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white">350</div>
          <div class="text-xs text-gray-500">Средний LTV: 25,000₽</div>
        </div>
        <div class="border-l-4 border-gray-400 pl-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">Low-Value</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white">1,500</div>
          <div class="text-xs text-gray-500">Средний LTV: 3,500₽</div>
        </div>
      </div>
    </div>

    <!-- Экспорт -->
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Экспорт отчёта</h3>
      <div class="flex gap-3">
        <button @click="exportReport('csv')" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
          📄 CSV
        </button>
        <button @click="exportReport('excel')" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
          📊 Excel
        </button>
        <button @click="exportReport('pdf')" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
          📃 PDF
        </button>
        <button @click="exportReport('json')" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
          {} JSON
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const kpis = ref({
  total_revenue_30d: 250000,
  total_orders_30d: 125,
  avg_order_value: 2000,
  conversion_rate: 0.045,
})

const forecast = ref({
  confidence: 0.85,
  predictions: [],
})

onMounted(async () => {
  try {
    // Получить KPI
    const kpiRes = await fetch('/api/v2/analytics/kpis')
    const kpiData = await kpiRes.json()
    kpis.value = kpiData.data.metrics || kpis.value

    // Получить прогноз
    const forecastRes = await fetch('/api/v2/analytics/forecast?metric_type=revenue&days_ahead=30')
    const forecastData = await forecastRes.json()
    forecast.value = forecastData.data || forecast.value
  } catch (e) {
    console.error('Failed to load analytics', e)
  }
})

const formatCurrency = (value) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
  }).format(value)
}

const exportReport = async (format) => {
  try {
    const res = await fetch(`/api/v2/reporting/generate?report_type=revenue_report&export_format=${format}`)
    const data = await res.json()
    console.log('Export ready', data)
    // Trigger download
    const link = document.createElement('a')
    link.href = data.export.url
    link.download = data.export.filename
    link.click()
  } catch (e) {
    console.error('Export failed', e)
  }
}
</script>
