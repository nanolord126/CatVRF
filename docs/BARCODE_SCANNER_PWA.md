# PWA Support for Offline Barcode Scanning

**Версия:** 1.0  
**Дата:** 2026-04-28  
**Модуль:** WMS Barcode Scanner - PWA

## Обзор

PWA (Progressive Web App) поддержка для сканера штрихкодов позволяет работать оффлайн, кэшировать результаты сканирования и синхронизировать данные при восстановлении соединения.

## Возможности PWA для сканера

- **Оффлайн сканирование:** Сканирование штрихкодов без интернет-соединения
- **Кэширование результатов:** Локальное хранение результатов сканирования
- **Синхронизация:** Автоматическая синхронизация при восстановлении соединения
- **Уведомления:** Push-уведомления о статусе синхронизации
- **Установка на устройство:** Возможность установки как нативное приложение

## Сервис-воркер для оффлайн работы

### Регистрация сервис-воркера

```typescript
// frontend/src/service-worker.ts
import { precacheAndRoute } from 'workbox-precaching'
import { registerRoute, NavigationRoute, Route } from 'workbox-routing'
import { StaleWhileRevalidate, NetworkFirst, CacheFirst } from 'workbox-strategies'
import { ExpirationPlugin } from 'workbox-expiration'
import { BackgroundSyncPlugin } from 'workbox-background-sync'

// Cache name for barcode scans
const BARCODE_CACHE = 'barcode-scans-v1'
const API_CACHE = 'api-cache-v1'

// Background sync for offline scans
const bgSyncPlugin = new BackgroundSyncPlugin('barcode-queue', {
  maxRetentionTime: 24 * 60, // Retry for up to 24 hours
  onSync: async ({ queue }) => {
    let entry
    while ((entry = await queue.shiftRequest())) {
      try {
        await fetch(entry.request)
        console.log('Background sync success')
      } catch (error) {
        console.error('Background sync failed:', error)
        await queue.unshiftRequest(entry)
      }
    }
  }
})

// Cache API responses
registerRoute(
  ({ url }) => url.pathname.startsWith('/api/wms/barcode'),
  new NetworkFirst({
    cacheName: API_CACHE,
    plugins: [
      new ExpirationPlugin({
        maxEntries: 100,
        maxAgeSeconds: 24 * 60 * 60, // 24 hours
      }),
    ],
  })
)

// Cache static assets
precacheAndRoute([
  { url: '/assets/barcode-scanner.js', revision: '1' },
  { url: '/assets/barcode-scanner.css', revision: '1' },
])

// Offline fallback
const navigationHandler = new NavigationRoute(
  new NetworkFirst({
    cacheName: 'pages-cache',
    plugins: [
      new ExpirationPlugin({
        maxEntries: 50,
        maxAgeSeconds: 24 * 60 * 60,
      }),
    ],
  })
)

registerRoute(navigationHandler)

export { bgSyncPlugin, BARCODE_CACHE }
```

### Регистрация сервис-воркера в приложении

```typescript
// frontend/src/main.ts
import { registerSW } from 'virtual:pwa-register'

registerSW({
  onNeedRefresh() {
    // Show update notification
    if (confirm('Доступно обновление. Обновить?')) {
      location.reload()
    }
  },
  onOfflineReady() {
    console.log('PWA ready for offline use')
  },
  onRegistered(registration) {
    // Periodic sync for barcode scans
    setInterval(async () => {
      if (registration && registration.sync) {
        await registration.sync.register('barcode-sync')
      }
    }, 60000) // Every minute
  },
})
```

## Оффлайн хранилище сканирований

### IndexedDB для кэширования

```typescript
// frontend/src/services/offlineBarcodeStorage.ts
import { openDB } from 'idb'

const DB_NAME = 'BarcodeScannerDB'
const DB_VERSION = 1
const STORE_NAME = 'scans'

export interface OfflineScan {
  id: string
  barcode: string
  warehouseId: number
  quantity: number
  adjustmentType: 'in' | 'out' | 'adjust'
  reason: string
  timestamp: number
  synced: boolean
}

class OfflineBarcodeStorage {
  private db: any

  async init() {
    this.db = await openDB(DB_NAME, DB_VERSION, {
      upgrade(db) {
        if (!db.objectStoreNames.contains(STORE_NAME)) {
          const store = db.createObjectStore(STORE_NAME, { keyPath: 'id' })
          store.createIndex('timestamp', 'timestamp')
          store.createIndex('synced', 'synced')
        }
      },
    })
  }

  async addScan(scan: OfflineScan): Promise<void> {
    await this.db.add(STORE_NAME, scan)
  }

  async getUnsyncedScans(): Promise<OfflineScan[]> {
    return await this.db.getAllFromIndex(STORE_NAME, 'synced', false)
  }

  async markAsSynced(id: string): Promise<void> {
    const scan = await this.db.get(STORE_NAME, id)
    if (scan) {
      scan.synced = true
      await this.db.put(STORE_NAME, scan)
    }
  }

  async getAllScans(): Promise<OfflineScan[]> {
    return await this.db.getAll(STORE_NAME)
  }

  async clearSyncedScans(): Promise<void> {
    const synced = await this.db.getAllFromIndex(STORE_NAME, 'synced', true)
    for (const scan of synced) {
      await this.db.delete(STORE_NAME, scan.id)
    }
  }
}

export const offlineStorage = new OfflineBarcodeStorage()
```

### Обновление composable для оффлайн работы

```typescript
// frontend/src/composables/useBarcodeScanner.ts (дополнение)
import { offlineStorage } from '@/services/offlineBarcodeStorage'

const isOnline = ref(navigator.onLine)
const offlineQueue = ref<OfflineScan[]>([])

// Watch online/offline status
window.addEventListener('online', () => {
  isOnline.value = true
  syncOfflineScans()
})

window.addEventListener('offline', () => {
  isOnline.value = false
})

const scanAndAdjust = async (
  barcode: string,
  warehouseId: number,
  quantity: number,
  adjustmentType: 'in' | 'out' | 'adjust',
  reason: string
): Promise<ScanAdjustmentResult> => {
  isScanning.value = true
  error.value = null

  try {
    if (!isOnline.value) {
      // Store scan offline
      const offlineScan: OfflineScan = {
        id: crypto.randomUUID(),
        barcode,
        warehouseId,
        quantity,
        adjustmentType,
        reason,
        timestamp: Date.now(),
        synced: false
      }

      await offlineStorage.addScan(offlineScan)
      offlineQueue.value.push(offlineScan)

      return {
        success: true,
        message: 'Сохранено оффлайн. Синхронизация при подключении к сети.'
      }
    }

    const response = await axios.post('/api/wms/barcode/scan/adjust', {
      barcode,
      warehouse_id: warehouseId,
      quantity,
      adjustment_type: adjustmentType,
      reason
    })

    adjustmentResult.value = response.data
    return response.data
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to process scan'
    return {
      success: false,
      message: error.value
    }
  } finally {
    isScanning.value = false
  }
}

const syncOfflineScans = async () => {
  const unsyncedScans = await offlineStorage.getUnsyncedScans()
  
  for (const scan of unsyncedScans) {
    try {
      const response = await axios.post('/api/wms/barcode/scan/adjust', {
        barcode: scan.barcode,
        warehouse_id: scan.warehouseId,
        quantity: scan.quantity,
        adjustment_type: scan.adjustmentType,
        reason: scan.reason
      })

      if (response.data.success) {
        await offlineStorage.markAsSynced(scan.id)
      }
    } catch (err) {
      console.error('Failed to sync scan:', err)
    }
  }

  // Clear synced scans
  await offlineStorage.clearSyncedScans()
  offlineQueue.value = []
}
```

## Манифест PWA

```json
{
  "name": "CatVRF Сканер штрихкодов",
  "short_name": "Сканер",
  "description": "Сканер штрихкодов для WMS CatVRF",
  "start_url": "/scanner",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#2563eb",
  "icons": [
    {
      "src": "/icons/icon-192x192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/icons/icon-512x512.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ],
  "categories": ["business", "productivity"],
  "screenshots": [
    {
      "src": "/screenshots/scanner-1.png",
      "sizes": "540x720",
      "type": "image/png"
    }
  ]
}
```

## Конфигурация Vite PWA

```typescript
// vite.config.ts
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['icons/*.png', 'fonts/*.woff2'],
      manifest: {
        name: 'CatVRF Сканер штрихкодов',
        short_name: 'Сканер',
        description: 'Сканер штрихкодов для WMS CatVRF',
        theme_color: '#2563eb',
        icons: [
          {
            src: 'pwa-192x192.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: 'pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png'
          }
        ]
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg}'],
        runtimeCaching: [
          {
            urlPattern: /^https:\/\/api\.example\.com\/.*/i,
            handler: 'NetworkFirst',
            options: {
              cacheName: 'api-cache',
              expiration: {
                maxEntries: 100,
                maxAgeSeconds: 24 * 60 * 60
              },
              cacheableResponse: {
                statuses: [0, 200]
              }
            }
          }
        ]
      }
    })
  ]
})
```

## UI индикаторы статуса оффлайн

```vue
<template>
  <div class="offline-indicator" :class="{ offline: !isOnline }">
    <div class="status-dot"></div>
    <span class="status-text">
      {{ isOnline ? 'Онлайн' : 'Оффлайн' }}
    </span>
    <span v-if="offlineQueue.length" class="queue-info">
      {{ offlineQueue.length }} в очереди
    </span>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

const isOnline = ref(navigator.onLine)
const offlineQueue = ref(0)

const updateOnlineStatus = () => {
  isOnline.value = navigator.onLine
}

onMounted(() => {
  window.addEventListener('online', updateOnlineStatus)
  window.addEventListener('offline', updateOnlineStatus)
})

onUnmounted(() => {
  window.removeEventListener('online', updateOnlineStatus)
  window.removeEventListener('offline', updateOnlineStatus)
})
</script>

<style scoped>
.offline-indicator {
  @apply flex items-center gap-2 px-4 py-2 bg-green-100 text-green-800 rounded-lg;
}

.offline-indicator.offline {
  @apply bg-yellow-100 text-yellow-800;
}

.status-dot {
  @apply w-2 h-2 rounded-full bg-current;
}

.queue-info {
  @apply ml-2 text-sm opacity-75;
}
</style>
```

## API для оффлайн синхронизации

### Batch sync endpoint

```php
// routes/api/wms.php (дополнение)
Route::post('/barcode/sync-offline', [BarcodeController::class, 'syncOffline'])
    ->middleware('can:create stock_movement');
```

### Контроллер синхронизации

```php
// BarcodeController.php (дополнение)
public function syncOffline(Request $request): JsonResponse
{
    $validated = $request->validate([
        'scans' => 'required|array',
        'scans.*.barcode' => 'required|string',
        'scans.*.warehouse_id' => 'required|integer',
        'scans.*.quantity' => 'required|integer',
        'scans.*.adjustment_type' => 'required|in:in,out,adjust',
        'scans.*.reason' => 'required|string',
        'scans.*.timestamp' => 'required|integer',
    ]);

    $userId = Auth::id();
    $tenantId = Auth::user()->tenant_id;
    $results = [];

    foreach ($validated['scans'] as $scan) {
        try {
            $result = DB::transaction(function () use ($scan, $userId, $tenantId) {
                $lookup = $this->service->lookupByBarcode($scan['barcode'], $scan['warehouse_id']);

                if (!$lookup['found']) {
                    return [
                        'barcode' => $scan['barcode'],
                        'success' => false,
                        'message' => 'Item not found',
                    ];
                }

                $itemId = $lookup['item_id'];
                $currentStock = $lookup['current_stock'];

                if ($scan['adjustment_type'] === 'out' && $currentStock < $scan['quantity']) {
                    return [
                        'barcode' => $scan['barcode'],
                        'success' => false,
                        'message' => 'Insufficient stock',
                    ];
                }

                $newStock = match ($scan['adjustment_type']) {
                    'in' => $currentStock + $scan['quantity'],
                    'out' => $currentStock - $scan['quantity'],
                    'adjust' => $scan['quantity'],
                };

                DB::table('inventory_items')
                    ->where('id', $itemId)
                    ->update([
                        'current_stock' => $newStock,
                        'updated_at' => now(),
                    ]);

                $movementQuantity = match ($scan['adjustment_type']) {
                    'in' => $scan['quantity'],
                    'out' => -$scan['quantity'],
                    'adjust' => $scan['quantity'] - $currentStock,
                };

                $movementId = DB::table('stock_movements')->insertGetId([
                    'uuid' => Str::uuid(),
                    'correlation_id' => Str::uuid(),
                    'inventory_item_id' => $itemId,
                    'type' => $scan['adjustment_type'] === 'adjust' ? 'adjustment' : $scan['adjustment_type'],
                    'quantity' => $movementQuantity,
                    'reason' => $scan['reason'] . ' (offline sync)',
                    'source_type' => 'barcode_scanner_offline',
                    'source_id' => null,
                    'created_by' => $userId,
                    'performed_by' => (string) $userId,
                    'created_at' => Carbon::createFromTimestamp($scan['timestamp']),
                ]);

                return [
                    'barcode' => $scan['barcode'],
                    'success' => true,
                    'movement_id' => $movementId,
                ];
            });

            $results[] = $result;
        } catch (\Exception $e) {
            $results[] = [
                'barcode' => $scan['barcode'],
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    return response()->json([
        'results' => $results,
        'total' => count($results),
        'successful' => count(array_filter($results, fn($r) => $r['success'])),
        'failed' => count(array_filter($results, fn($r) => !$r['success'])),
    ]);
}
```

## Тестирование оффлайн режима

### Chrome DevTools

1. Откройте DevTools (F12)
2. Перейдите в Network tab
3. Выберите "Offline" из выпадающего списка throttling
4. Протестируйте сканирование штрихкодов
5. Выберите "Online" для проверки синхронизации

### Команды для тестирования

```bash
# Запуск PWA в режиме разработки
npm run dev

# Сборка PWA для продакшена
npm run build

# Тестирование PWA локально
npx serve dist
```

## Установка на устройство

### Установка на Android

1. Откройте сайт в Chrome
2. Нажмите на меню (три точки)
3. Выберите "Добавить на главный экран"
4. Нажмите "Установить"

### Установка на iOS

1. Откройте сайт в Safari
2. Нажмите кнопку "Поделиться"
3. Прокрутите вниз и выберите "На экран Домой"
4. Нажмите "Добавить"

## Мониторинг оффлайн активности

```typescript
// Аналитика оффлайн использования
const trackOfflineUsage = () => {
  if (!isOnline.value) {
    // Send to analytics
    analytics.track('offline_scan_attempt', {
      queue_size: offlineQueue.value.length
    })
  }
}

// Отслеживание успешной синхронизации
const trackSyncSuccess = (syncedCount: number) => {
  analytics.track('offline_sync_success', {
    synced_count: syncedCount
  })
}
```

## Безопасность в оффлайн режиме

1. **Шифрование данных:** Шифруйте чувствительные данные в IndexedDB
2. **Валидация на сервере:** Всегда валидируйте данные при синхронизации
3. **Rate limiting:** Ограничьте количество оффлайн сканирований
4. **Срок хранения:** Очищайте старые оффлайн данные

## Troubleshooting

### Синхронизация не работает

1. Проверьте сетевое соединение
2. Проверьте логи сервис-воркера
3. Убедитесь, что API доступен
4. Проверьте размер очереди синхронизации

### PWA не устанавливается

1. Убедитесь, что сайт обслуживается по HTTPS
2. Проверьте манифест на ошибки
3. Убедитесь, что иконки доступны
4. Проверьте service worker registration

### Оффлайн данные теряются

1. Проверьте quota IndexedDB
2. Убедитесь, что браузер не очистил данные
3. Добавьте обработку ошибок при записи в IndexedDB

## Поддержка

Для вопросов по PWA функциональности сканера штрихкодов обратитесь к команде разработки WMS.
