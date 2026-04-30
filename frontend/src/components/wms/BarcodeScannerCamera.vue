<template>
  <div class="barcode-scanner-camera">
    <!-- Scanner Header -->
    <div class="scanner-header">
      <h3 class="scanner-title">Сканер штрихкодов</h3>
      <button 
        @click="closeScanner" 
        class="close-btn"
        aria-label="Закрыть сканер"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Camera Viewfinder -->
    <div class="camera-container">
      <video 
        ref="videoRef" 
        class="camera-feed"
        autoplay 
        playsinline 
        muted
      ></video>
      <canvas ref="canvasRef" class="hidden-canvas"></canvas>
      
      <!-- Scanning Overlay -->
      <div class="scan-overlay">
        <div class="scan-frame">
          <div class="scan-line" :class="{ 'scanning': isScanning }"></div>
          <div class="corner-tl"></div>
          <div class="corner-tr"></div>
          <div class="corner-bl"></div>
          <div class="corner-br"></div>
        </div>
      </div>

      <!-- Camera Error -->
      <div v-if="error" class="camera-error">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <p class="error-text">{{ error }}</p>
        <button @click="initializeCamera" class="retry-btn">Повторить</button>
      </div>

      <!-- Manual Input Fallback -->
      <div v-if="!cameraActive" class="manual-input">
        <input 
          v-model="manualBarcode"
          type="text" 
          placeholder="Введите штрихкод вручную"
          class="barcode-input"
          @keyup.enter="handleManualScan"
        />
        <button @click="handleManualScan" class="scan-btn">Сканировать</button>
      </div>
    </div>

    <!-- Scan Result -->
    <div v-if="scanResult" class="scan-result">
      <div class="result-card">
        <div v-if="scanResult.found" class="result-found">
          <h4>Товар найден</h4>
          <div class="item-details">
            <div class="detail-row">
              <span class="label">SKU:</span>
              <span class="value">{{ scanResult.sku }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Название:</span>
              <span class="value">{{ scanResult.name }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Текущий остаток:</span>
              <span class="value">{{ scanResult.current_stock }}</span>
            </div>
          </div>

          <!-- Adjustment Controls -->
          <div class="adjustment-controls">
            <div class="control-group">
              <label>Тип операции:</label>
              <select v-model="adjustmentType" class="select-input">
                <option value="in">Приход</option>
                <option value="out">Расход</option>
                <option value="adjust">Корректировка</option>
              </select>
            </div>
            <div class="control-group">
              <label>Количество:</label>
              <input 
                v-model.number="quantity" 
                type="number" 
                min="1" 
                class="number-input"
              />
            </div>
            <div class="control-group">
              <label>Причина:</label>
              <input 
                v-model="reason" 
                type="text" 
                placeholder="Причина корректировки"
                class="text-input"
              />
            </div>
            <button 
              @click="processAdjustment" 
              :disabled="isProcessing"
              class="adjust-btn"
            >
              {{ isProcessing ? 'Обработка...' : 'Подтвердить' }}
            </button>
          </div>
        </div>
        <div v-else class="result-not-found">
          <h4>Товар не найден</h4>
          <p>{{ scanResult.message }}</p>
          <button @click="resetScan" class="scan-again-btn">Сканировать снова</button>
        </div>
      </div>
    </div>

    <!-- Adjustment Result -->
    <div v-if="adjustmentResult" class="adjustment-result">
      <div class="result-card" :class="{ 'success': adjustmentResult.success, 'error': !adjustmentResult.success }">
        <div v-if="adjustmentResult.success" class="success-message">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <h4>Корректировка выполнена</h4>
          <div class="result-details">
            <p>Было: {{ adjustmentResult.previous_stock }}</p>
            <p>Стало: {{ adjustmentResult.new_stock }}</p>
          </div>
          <button @click="resetScan" class="scan-again-btn">Сканировать следующий</button>
        </div>
        <div v-else class="error-message">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          <h4>Ошибка</h4>
          <p>{{ adjustmentResult.message }}</p>
          <button @click="resetScan" class="scan-again-btn">Попробовать снова</button>
        </div>
      </div>
    </div>

    <!-- Scan History -->
    <div v-if="showHistory && history" class="scan-history">
      <h4>История сканирований</h4>
      <div class="history-list">
        <div v-for="item in history.data" :key="item.id" class="history-item">
          <div class="history-info">
            <span class="history-type" :class="item.type">{{ item.type }}</span>
            <span class="history-quantity">{{ Math.abs(item.quantity) }}</span>
          </div>
          <span class="history-date">{{ formatDate(item.created_at) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useBarcodeScanner } from '@/composables/useBarcodeScanner'

interface Props {
  warehouseId?: number
  onClose?: () => void
}

const props = withDefaults(defineProps<Props>(), {
  warehouseId: 1
})

const emit = defineEmits<{
  close: []
  scanComplete: [result: any]
}>()

const {
  isScanning,
  scanResult,
  adjustmentResult,
  error,
  videoStream,
  initializeCamera,
  stopCamera,
  lookupBarcode,
  validateBarcode,
  preFlightScan,
  scanAndAdjust,
  getScannerHistory
} = useBarcodeScanner()

const videoRef = ref<HTMLVideoElement | null>(null)
const canvasRef = ref<HTMLCanvasElement | null>(null)
const cameraActive = ref(false)
const isProcessing = ref(false)
const manualBarcode = ref('')
const showHistory = ref(false)
const history = ref<any>(null)

const adjustmentType = ref<'in' | 'out' | 'adjust'>('out')
const quantity = ref(1)
const reason = ref('')

onMounted(async () => {
  await startCamera()
  await loadHistory()
})

onUnmounted(() => {
  stopCamera()
})

const startCamera = async () => {
  if (videoRef.value) {
    cameraActive.value = await initializeCamera(videoRef.value)
    if (cameraActive.value) {
      startBarcodeDetection()
    }
  }
}

const startBarcodeDetection = () => {
  // In production, integrate with @zxing/library or html5-qrcode
  // This is a placeholder for the actual detection logic
  console.log('Barcode detection started')
}

const handleManualScan = async () => {
  if (!manualBarcode.value.trim()) return
  
  const result = await lookupBarcode(manualBarcode.value, props.warehouseId)
  if (result.found) {
    emit('scanComplete', result)
  }
}

const processAdjustment = async () => {
  if (!scanResult.value || !scanResult.value.barcode) return
  
  isProcessing.value = true
  
  // Pre-flight validation
  const validation = await preFlightScan(
    scanResult.value.barcode,
    props.warehouseId,
    adjustmentType.value,
    quantity.value
  )
  
  if (!validation.valid || !validation.can_proceed) {
    isProcessing.value = false
    return
  }
  
  const result = await scanAndAdjust(
    scanResult.value.barcode,
    props.warehouseId,
    quantity.value,
    adjustmentType.value,
    reason.value || 'Корректировка через сканер'
  )
  
  if (result.success) {
    emit('scanComplete', result)
    await loadHistory()
  }
  
  isProcessing.value = false
}

const resetScan = () => {
  scanResult.value = null
  adjustmentResult.value = null
  manualBarcode.value = ''
  quantity.value = 1
  reason.value = ''
}

const closeScanner = () => {
  stopCamera()
  emit('close')
}

const loadHistory = async () => {
  history.value = await getScannerHistory(props.warehouseId)
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleString('ru-RU')
}
</script>

<style scoped>
.barcode-scanner-camera {
  @apply relative bg-gray-900 rounded-lg overflow-hidden;
  min-height: 500px;
}

.scanner-header {
  @apply flex items-center justify-between p-4 bg-gray-800 text-white;
}

.scanner-title {
  @apply text-lg font-semibold;
}

.close-btn {
  @apply p-2 hover:bg-gray-700 rounded-lg transition-colors;
}

.camera-container {
  @apply relative bg-black;
  min-height: 400px;
}

.camera-feed {
  @apply w-full h-full object-cover;
}

.hidden-canvas {
  @apply hidden;
}

.scan-overlay {
  @apply absolute inset-0 flex items-center justify-center;
}

.scan-frame {
  @apply relative w-64 h-64 border-2 border-white border-opacity-50 rounded-lg;
}

.scan-line {
  @apply absolute top-1/2 left-0 right-0 h-0.5 bg-green-500;
  transform: translateY(-50%);
}

.scan-line.scanning {
  animation: scan 2s ease-in-out infinite;
}

@keyframes scan {
  0%, 100% { top: 10%; }
  50% { top: 90%; }
}

.corner-tl,
.corner-tr,
.corner-bl,
.corner-br {
  @apply absolute w-8 h-8 border-4 border-green-500;
}

.corner-tl {
  @apply top-0 left-0 border-r-0 border-b-0 rounded-tl-lg;
}

.corner-tr {
  @apply top-0 right-0 border-l-0 border-b-0 rounded-tr-lg;
}

.corner-bl {
  @apply bottom-0 left-0 border-r-0 border-t-0 rounded-bl-lg;
}

.corner-br {
  @apply bottom-0 right-0 border-l-0 border-t-0 rounded-br-lg;
}

.camera-error {
  @apply absolute inset-0 flex flex-col items-center justify-center bg-gray-900 bg-opacity-95 text-white p-6;
}

.error-text {
  @apply mt-4 text-center text-gray-300;
}

.retry-btn {
  @apply mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors;
}

.manual-input {
  @apply absolute inset-0 flex items-center justify-center bg-gray-900 bg-opacity-95 p-6;
}

.barcode-input {
  @apply flex-1 px-4 py-2 bg-gray-800 text-white border border-gray-700 rounded-l-lg focus:outline-none focus:border-blue-500;
}

.scan-btn {
  @apply px-4 py-2 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700 transition-colors;
}

.scan-result,
.adjustment-result {
  @apply absolute inset-x-0 bottom-0 bg-white rounded-t-lg shadow-lg p-6;
}

.result-card {
  @apply bg-white rounded-lg p-6;
}

.result-found h4,
.result-not-found h4 {
  @apply text-lg font-semibold mb-4;
}

.item-details {
  @apply space-y-2 mb-6;
}

.detail-row {
  @apply flex justify-between;
}

.label {
  @apply text-gray-600;
}

.value {
  @apply font-medium;
}

.adjustment-controls {
  @apply space-y-4;
}

.control-group {
  @apply flex flex-col;
}

.control-group label {
  @apply text-sm text-gray-600 mb-1;
}

.select-input,
.number-input,
.text-input {
  @apply px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500;
}

.adjust-btn {
  @apply w-full py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed;
}

.result-not-found {
  @apply text-center;
}

.scan-again-btn {
  @apply mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors;
}

.success-message,
.error-message {
  @apply text-center;
}

.success-message h4 {
  @apply text-lg font-semibold text-green-600 mb-2;
}

.error-message h4 {
  @apply text-lg font-semibold text-red-600 mb-2;
}

.result-details {
  @apply my-4 text-gray-600;
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

.history-quantity {
  @apply font-medium;
}

.history-date {
  @apply text-sm text-gray-500;
}

.history-item {
  @apply flex justify-between items-center py-2 border-b border-gray-200;
}

.scan-history h4 {
  @apply text-lg font-semibold mb-4;
}
</style>
