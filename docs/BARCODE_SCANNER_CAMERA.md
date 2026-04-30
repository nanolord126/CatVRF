# Barcode Scanner Camera - Документация

**Версия:** 1.0  
**Дата:** 2026-04-28  
**Модуль:** WMS (Warehouse Management System) - Frontend

## Обзор

Компонент сканера штрихкодов с поддержкой камеры для считывания штрихкодов и QR-кодов в реальном времени. Интегрирован с существующим API WMS для автоматической корректировки запасов.

## Возможности

- **Сканирование камерой:** Использование камеры устройства для считывания штрихкодов
- **Поддержка QR-кодов:** Распознавание и обработка QR-кодов
- **Поддерживаемые форматы:** EAN-13, EAN-8, UPC-A, Code-128, QR-коды
- **Ручной ввод:** Резервный вариант ввода штрихкода вручную
- **Автоматическая корректировка:** Интеграция с API для автоматического обновления запасов
- **История сканирований:** Просмотр истории операций сканирования
- **WebSocket обновления:** Реальное время обновлений через WebSocket

## Установка зависимостей

Для работы сканера требуется библиотека для распознавания штрихкодов. Рекомендуется использовать `@zxing/library`:

```bash
npm install @zxing/library
# или
yarn add @zxing/library
```

Альтернативные библиотеки:
- `html5-qrcode`
- `vue-qrcode-reader`

## Использование компонента

### Базовое использование

```vue
<template>
  <BarcodeScannerCamera
    :warehouse-id="1"
    @close="handleClose"
    @scan-complete="handleScanComplete"
  />
</template>

<script setup lang="ts">
import BarcodeScannerCamera from '@/components/wms/BarcodeScannerCamera.vue'

const handleClose = () => {
  console.log('Scanner closed')
}

const handleScanComplete = (result) => {
  console.log('Scan completed:', result)
}
</script>
```

### Использование composable

```typescript
import { useBarcodeScanner } from '@/composables/useBarcodeScanner'

const {
  isScanning,
  scanResult,
  adjustmentResult,
  error,
  initializeCamera,
  stopCamera,
  lookupBarcode,
  scanAndAdjust,
  detectBarcodeFromFrame,
  validateQRCode,
  parseQRCodeData
} = useBarcodeScanner()

// Инициализация камеры
const videoElement = ref<HTMLVideoElement>()
const canvasElement = ref<HTMLCanvasElement>()

onMounted(async () => {
  await initializeCamera(videoElement.value)
})

// Сканирование
const handleScan = async (barcode: string) => {
  const result = await lookupBarcode(barcode, warehouseId)
  if (result.found) {
    await scanAndAdjust(barcode, warehouseId, quantity, 'out', 'Reason')
  }
}

// Обнаружение штрихкода из видео
const detectCode = async () => {
  const { code, type } = await detectBarcodeFromFrame(
    videoElement.value,
    canvasElement.value
  )
  if (code) {
    if (type === 'qr') {
      const parsed = parseQRCodeData(code)
      console.log('QR Code:', parsed)
    } else {
      console.log('Barcode:', code)
    }
  }
}
```

## API Composable

### useBarcodeScanner()

Composable для работы со сканером штрихкодов.

#### Возвращаемые значения

| Свойство | Тип | Описание |
|----------|-----|----------|
| `isScanning` | `Ref<boolean>` | Статус сканирования |
| `scanResult` | `Ref<ScanResult \| null>` | Результат поиска штрихкода |
| `adjustmentResult` | `Ref<ScanAdjustmentResult \| null>` | Результат корректировки |
| `error` | `Ref<string \| null>` | Сообщение об ошибке |
| `videoStream` | `Ref<MediaStream \| null>` | Поток видео с камеры |

#### Методы

##### `initializeCamera(videoElement: HTMLVideoElement): Promise<boolean>`

Инициализация камеры устройства.

```typescript
const success = await initializeCamera(videoElement.value)
```

##### `stopCamera(): void`

Остановка камеры.

```typescript
stopCamera()
```

##### `lookupBarcode(barcode: string, warehouseId: number): Promise<ScanResult>`

Поиск товара по штрихкоду.

```typescript
const result = await lookupBarcode('1234567890123', 1)
```

##### `validateBarcode(barcode: string): Promise<boolean>`

Валидация формата штрихкода.

```typescript
const isValid = await validateBarcode('1234567890123')
```

##### `preFlightScan(barcode, warehouseId, operation, quantity): Promise<{valid: boolean}>`

Предварительная проверка перед сканированием.

```typescript
const validation = await preFlightScan('1234567890123', 1, 'out', 5)
```

##### `scanAndAdjust(barcode, warehouseId, quantity, adjustmentType, reason): Promise<ScanAdjustmentResult>`

Сканирование и автоматическая корректировка запасов.

```typescript
const result = await scanAndAdjust(
  '1234567890123',
  1,
  5,
  'out',
  'Корректировка через сканер'
)
```

##### `detectBarcodeFromFrame(videoElement, canvasElement): Promise<{code: string \| null, type: 'barcode' \| 'qr' \| null}>`

Обнаружение штрихкода или QR-кода из видеокадра.

```typescript
const { code, type } = await detectBarcodeFromFrame(
  videoElement.value,
  canvasElement.value
)
```

##### `validateQRCode(qrCode: string): boolean`

Валидация формата QR-кода.

```typescript
const isValid = validateQRCode('https://example.com')
```

##### `parseQRCodeData(qrCode: string): {type: string, data: any}`

Парсинг данных QR-кода.

```typescript
const parsed = parseQRCodeData('https://example.com')
// { type: 'url', data: { url: 'https://example.com' } }
```

## Компонент BarcodeScannerCamera

### Props

| Prop | Тип | Обязательный | По умолчанию | Описание |
|------|-----|-------------|--------------|----------|
| `warehouseId` | `number` | Нет | `1` | ID склада |
| `onClose` | `() => void` | Нет | - | Callback при закрытии |

### Events

| Event | Payload | Описание |
|-------|---------|----------|
| `close` | - | Сканер закрыт |
| `scanComplete` | `result: any` | Сканирование завершено |

### Функции компонента

1. **Автоматическое сканирование:** Использует камеру для распознавания штрихкодов
2. **Ручной ввод:** Возможность ввести штрихкод вручную
3. **Корректировка запасов:** Интерфейс для выбора типа операции и количества
4. **История:** Просмотр истории сканирований
5. **Обработка ошибок:** Отображение ошибок и возможность повторной попытки

## Поддерживаемые форматы

### Штрихкоды

- **EAN-13:** 13-значный европейский артикул
- **EAN-8:** 8-значный европейский артикул
- **UPC-A:** 12-значный универсальный код продукта
- **Code-128:** Алфавитно-цифровой формат

### QR-коды

- **URL:** Ссылки на веб-ресурсы
- **Текст:** Произвольный текст
- **JSON:** Структурированные данные
- **Штрихкоды:** Штрихкоды в формате QR

## Интеграция с @zxing/library

Для полноценной работы сканера рекомендуется интегрировать библиотеку `@zxing/library`:

### Установка

```bash
npm install @zxing/library
```

### Пример интеграции

```typescript
import { BrowserMultiFormatReader, BarcodeFormat } from '@zxing/library'

const reader = new BrowserMultiFormatReader()

// Настройка форматов для сканирования
const hints = new Map()
hints.set(BarcodeFormat.EAN_13, true)
hints.set(BarcodeFormat.EAN_8, true)
hints.set(BarcodeFormat.UPC_A, true)
hints.set(BarcodeFormat.CODE_128, true)
hints.set(BarcodeFormat.QR_CODE, true)

// Сканирование из видео
const result = await reader.decodeFromCanvas(canvasElement, hints)
console.log('Detected:', result.text)
console.log('Format:', result.barcodeFormat)
```

### Обновление composable

Замените метод `detectBarcodeFromFrame` в `useBarcodeScanner.ts`:

```typescript
import { BrowserMultiFormatReader, BarcodeFormat } from '@zxing/library'

let reader: BrowserMultiFormatReader | null = null

const detectBarcodeFromFrame = async (
  videoElement: HTMLVideoElement,
  canvasElement: HTMLCanvasElement
): Promise<{ code: string | null; type: 'barcode' | 'qr' | null }> => {
  if (!reader) {
    reader = new BrowserMultiFormatReader()
  }

  try {
    const result = await reader.decodeFromCanvas(canvasElement)
    const isQR = result.barcodeFormat === BarcodeFormat.QR_CODE
    
    return {
      code: result.text,
      type: isQR ? 'qr' : 'barcode'
    }
  } catch (err) {
    return { code: null, type: null }
  }
}
```

## Разрешения камеры

### HTTPS требуется

Для работы камеры в браузере требуется HTTPS соединение. При разработке на localhost это ограничение не применяется.

### Запрос разрешений

При первом использовании камеры браузер запросит разрешение у пользователя. Убедитесь, что:

1. Сайт обслуживается по HTTPS
2. Пользователь предоставил разрешение на использование камеры
3. Камера доступна на устройстве

### Обработка ошибок доступа

```typescript
const initializeCamera = async (videoElement: HTMLVideoElement): Promise<boolean> => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: 'environment',
        width: { ideal: 1280 },
        height: { ideal: 720 }
      }
    })
    
    videoStream.value = stream
    videoElement.srcObject = stream
    await videoElement.play()
    
    return true
  } catch (err: any) {
    if (err.name === 'NotAllowedError') {
      error.value = 'Разрешение на использование камеры отклонено'
    } else if (err.name === 'NotFoundError') {
      error.value = 'Камера не найдена'
    } else {
      error.value = 'Ошибка доступа к камере: ' + err.message
    }
    return false
  }
}
```

## WebSocket интеграция

Для получения обновлений в реальном времени используйте WebSocket канал:

```typescript
import Echo from 'laravel-echo'

const echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
})

// Подключение к каналу сканера
const scannerChannel = echo.private(`scanner.${tenantId}.${warehouseId}`)

scannerChannel.listen('barcode.scan.updated', (event) => {
  console.log('Stock updated:', event)
  // Обновление UI
})
```

## Пример полного использования

```vue
<template>
  <div class="scanner-page">
    <BarcodeScannerCamera
      :warehouse-id="selectedWarehouse"
      @close="showScanner = false"
      @scan-complete="handleScanComplete"
    />
    
    <div v-if="scanHistory.length" class="history-panel">
      <h3>История сканирований</h3>
      <div v-for="item in scanHistory" :key="item.id">
        {{ item.barcode }} - {{ item.quantity }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import BarcodeScannerCamera from '@/components/wms/BarcodeScannerCamera.vue'
import { useBarcodeScanner } from '@/composables/useBarcodeScanner'

const selectedWarehouse = ref(1)
const showScanner = ref(true)
const scanHistory = ref([])

const { getScannerHistory } = useBarcodeScanner()

const loadHistory = async () => {
  const history = await getScannerHistory(selectedWarehouse.value)
  if (history) {
    scanHistory.value = history.data
  }
}

const handleScanComplete = (result) => {
  console.log('Scan completed:', result)
  loadHistory()
}

onMounted(() => {
  loadHistory()
})
</script>
```

## Оптимизация производительности

### Кэширование результатов

```typescript
const cache = new Map<string, ScanResult>()

const lookupBarcode = async (barcode: string, warehouseId: number) => {
  const cacheKey = `${barcode}:${warehouseId}`
  
  if (cache.has(cacheKey)) {
    return cache.get(cacheKey)
  }
  
  const result = await fetchFromAPI(barcode, warehouseId)
  cache.set(cacheKey, result)
  
  return result
}
```

### Оптимизация частоты сканирования

```typescript
let lastScanTime = 0
const SCAN_INTERVAL = 500 // 500ms между сканами

const handleDetectedCode = (code: string) => {
  const now = Date.now()
  if (now - lastScanTime < SCAN_INTERVAL) {
    return // Игнорировать слишком частые сканы
  }
  
  lastScanTime = now
  processCode(code)
}
```

## Безопасность

### Валидация данных

Все сканированные данные должны быть валидированы на сервере, даже если валидация прошла на клиенте.

### Санитизация ввода

```typescript
const sanitizeBarcode = (barcode: string): string => {
  return barcode.replace(/[^A-Za-z0-9]/g, '')
}
```

### Защита от инъекций

Никогда не доверяйте данным из QR-кодов без валидации, особенно если они используются в URL или API запросах.

## Поддержка браузеров

| Браузер | Минимальная версия | Камера | QR-коды |
|---------|-------------------|--------|---------|
| Chrome | 53+ | ✅ | ✅ |
| Firefox | 36+ | ✅ | ✅ |
| Safari | 11+ | ✅ | ✅ |
| Edge | 79+ | ✅ | ✅ |
| Opera | 40+ | ✅ | ✅ |

Мобильные браузеры:
- iOS Safari 11+
- Chrome Android 53+
- Firefox Android 36+

## Troubleshooting

### Камера не работает

1. Проверьте HTTPS соединение
2. Убедитесь, что разрешение на камеру предоставлено
3. Проверьте, что камера не используется другим приложением
4. Попробуйте перезагрузить страницу

### Штрихкоды не распознаются

1. Убедитесь, что освещение достаточное
2. Держите камеру на правильном расстоянии (15-30 см)
3. Убедитесь, что штрихкод не поврежден
4. Попробуйте ручной ввод

### QR-коды не распознаются

1. Убедитесь, что QR-код четкий
2. Проверьте контрастность
3. Убедитесь, что весь QR-код в кадре
4. Попробуйте другой угол обзора

## Поддержка

Для вопросов и проблем со сканером штрихкодов обратитесь к команде разработки WMS или обратитесь к основной документации CatVRF.
