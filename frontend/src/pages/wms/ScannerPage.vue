<template>
  <div class="scanner-page">
    <div class="page-header">
      <h1 class="page-title">Сканер штрихкодов</h1>
      <div class="header-actions">
        <button @click="toggleHistory" class="history-btn">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          История
        </button>
        <select v-model="selectedWarehouse" class="warehouse-select">
          <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
            {{ warehouse.name }}
          </option>
        </select>
      </div>
    </div>

    <div class="scanner-container">
      <BarcodeScannerCamera
        v-if="showScanner"
        :warehouse-id="selectedWarehouse"
        @close="showScanner = false"
        @scan-complete="handleScanComplete"
      />

      <div v-else class="scanner-placeholder">
        <button @click="showScanner = true" class="start-scan-btn">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Начать сканирование
        </button>
      </div>
    </div>

    <!-- History Panel -->
    <div v-if="showHistory" class="history-panel">
      <div class="history-header">
        <h2>История сканирований</h2>
        <button @click="toggleHistory" class="close-btn">×</button>
      </div>
      
      <div class="history-filters">
        <input 
          v-model="historyFilters.from_date" 
          type="date" 
          class="filter-input"
        />
        <input 
          v-model="historyFilters.to_date" 
          type="date" 
          class="filter-input"
        />
        <button @click="loadHistory" class="apply-filter-btn">Применить</button>
      </div>

      <div v-if="loading" class="loading">
        Загрузка...
      </div>

      <div v-else-if="history && history.data.length" class="history-list">
        <div v-for="item in history.data" :key="item.id" class="history-item">
          <div class="history-main">
            <span class="history-type" :class="item.type">
              {{ getMovementTypeLabel(item.type) }}
            </span>
            <span class="history-quantity">{{ Math.abs(item.quantity) }} шт.</span>
          </div>
          <div class="history-meta">
            <span class="history-reason">{{ item.reason }}</span>
            <span class="history-date">{{ formatDate(item.created_at) }}</span>
          </div>
        </div>
      </div>

      <div v-else class="no-history">
        История сканирований пуста
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-value">{{ stats.totalScans }}</div>
        <div class="stat-label">Всего сканирований</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.todayScans }}</div>
        <div class="stat-label">Сегодня</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.successRate }}%</div>
        <div class="stat-label">Успешность</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.lastScan }}</div>
        <div class="stat-label">Последнее сканирование</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import BarcodeScannerCamera from '@/components/wms/BarcodeScannerCamera.vue'
import { useBarcodeScanner } from '@/composables/useBarcodeScanner'

const showScanner = ref(false)
const showHistory = ref(false)
const selectedWarehouse = ref(1)
const loading = ref(false)
const history = ref<any>(null)
const warehouses = ref([
  { id: 1, name: 'Склад 1' },
  { id: 2, name: 'Склад 2' },
  { id: 3, name: 'Склад 3' },
])

const historyFilters = ref({
  from_date: '',
  to_date: ''
})

const stats = ref({
  totalScans: 0,
  todayScans: 0,
  successRate: 0,
  lastScan: '-'
})

const { getScannerHistory } = useBarcodeScanner()

onMounted(() => {
  loadStats()
})

const toggleHistory = () => {
  showHistory.value = !showHistory.value
  if (showHistory.value) {
    loadHistory()
  }
}

const loadHistory = async () => {
  loading.value = true
  try {
    history.value = await getScannerHistory(
      selectedWarehouse.value,
      historyFilters.value.from_date || undefined,
      historyFilters.value.to_date || undefined
    )
  } catch (err) {
    console.error('Failed to load history:', err)
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  // Load statistics from API
  // This would be a separate endpoint or calculated from history
  stats.value = {
    totalScans: 156,
    todayScans: 23,
    successRate: 98,
    lastScan: '2 мин назад'
  }
}

const handleScanComplete = (result) => {
  console.log('Scan completed:', result)
  loadStats()
  if (showHistory.value) {
    loadHistory()
  }
}

const getMovementTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    'in': 'Приход',
    'out': 'Расход',
    'adjustment': 'Корректировка',
    'transfer': 'Перемещение',
    'damage': 'Списание',
  }
  return labels[type] || type
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style scoped>
.scanner-page {
  @apply max-w-7xl mx-auto p-6;
}

.page-header {
  @apply flex items-center justify-between mb-6;
}

.page-title {
  @apply text-2xl font-bold text-gray-900;
}

.header-actions {
  @apply flex items-center gap-4;
}

.history-btn {
  @apply flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors;
}

.warehouse-select {
  @apply px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500;
}

.scanner-container {
  @apply bg-gray-100 rounded-lg overflow-hidden;
  min-height: 500px;
}

.scanner-placeholder {
  @apply flex items-center justify-center h-96 bg-gray-100;
}

.start-scan-btn {
  @apply flex flex-col items-center gap-2 px-8 py-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors;
}

.history-panel {
  @apply mt-6 bg-white rounded-lg shadow-lg p-6;
}

.history-header {
  @apply flex items-center justify-between mb-4;
}

.history-header h2 {
  @apply text-xl font-semibold;
}

.close-btn {
  @apply text-2xl text-gray-500 hover:text-gray-700;
}

.history-filters {
  @apply flex gap-4 mb-4;
}

.filter-input {
  @apply px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500;
}

.apply-filter-btn {
  @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors;
}

.loading {
  @apply text-center py-8 text-gray-500;
}

.history-list {
  @apply space-y-3 max-h-96 overflow-y-auto;
}

.history-item {
  @apply p-4 bg-gray-50 rounded-lg;
}

.history-main {
  @apply flex items-center justify-between mb-2;
}

.history-type {
  @apply px-2 py-1 rounded text-xs font-medium;
}

.history-type.in {
  @apply bg-green-100 text-green-800;
}

.history-type.out {
  @apply bg-red-100 text-red-800;
}

.history-type.adjustment {
  @apply bg-blue-100 text-blue-800;
}

.history-type.transfer {
  @apply bg-purple-100 text-purple-800;
}

.history-type.damage {
  @apply bg-orange-100 text-orange-800;
}

.history-quantity {
  @apply font-semibold text-gray-900;
}

.history-meta {
  @apply flex items-center justify-between text-sm;
}

.history-reason {
  @apply text-gray-600;
}

.history-date {
  @apply text-gray-500;
}

.no-history {
  @apply text-center py-8 text-gray-500;
}

.stats-grid {
  @apply grid grid-cols-1 md:grid-cols-4 gap-4 mt-6;
}

.stat-card {
  @apply bg-white rounded-lg shadow p-6;
}

.stat-value {
  @apply text-3xl font-bold text-gray-900;
}

.stat-label {
  @apply text-sm text-gray-600 mt-1;
}
</style>
