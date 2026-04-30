# Tenant Panel Architecture

**Created:** 2026-04-27  
**Status:** Complete  
**Version:** 1.0

## Overview

Единая панель тенанта с интеграцией всех бизнес-процессов:
- CRM (Заказы B2B/B2C)
- Сотрудники (через шеринг)
- Инвентаризация
- Склад (единый B2B/B2C)
- Зарплаты
- HR
- Маркетинг
- Настройки
- Документы

## Architecture

```
frontend/src/views/tenant/
├── TenantPanel.vue (Main Component)
├── components/
│   ├── StaffView.vue
│   ├── InventoryView.vue
│   ├── WarehouseView.vue (с переключением B2B/B2C)
│   ├── SalariesView.vue
│   ├── HRView.vue
│   ├── MarketingView.vue
│   ├── SettingsView.vue
│   ├── DocumentsView.vue
│   └── WalletModal.vue (клик по балансу)
└── constants/
    └── colors.ts (B2B/B2C цветовая дифференциация)
```

## Color Differentiation

B2C на 17 тонов ярче B2B:

```typescript
B2B_COLOR = '#3B82F6'  // Blue-500
B2C_COLOR = '#60A5FA'  // Blue-400 (на 17 тонов ярче)
```

Используется для:
- Заказов в списке
- Статистики
- Кнопок
- Индикаторов

## Wallet Integration

**Доступ:** Клик по балансу в шапке панели

**Функционал:**
- Просмотр баланса
- История операций
- Формирование документов:
  - Счет на оплату
  - Акт выполненных работ
  - Накладная (ТОРГ-12)
  - УПД
  - Счет-фактура
  - Договор
- Сверки и акты
- Закрывающие документы:
  - Баланс
  - Отчет о прибылях
  - Отчет о движении средств
  - Налоговая декларация

## Vertical Operations

### Hotels - Room Cleaning

**Сущность:** `RoomCleaning`

**Статусы:** pending, in_progress, completed, needs_rework

**Типы уборки:**
- checkout (приоритет 5)
- express (приоритет 4)
- standard (приоритет 3)
- deep (приоритет 2)

**Функционал:**
- Назначение уборки
- Авто-назначение персонала
- Чек-лист уборки
- Фото-фиксация
- Контроль качества
- Оценка супервизора

### Restaurant - Order Fulfillment

**Сущность:** `OrderFulfillment`

**Статусы:** pending, in_progress, ready, served

**Функционал:**
- Создание fulfillment для заказа
- Назначение на кухонную станцию
- Назначение повара
- Назначение официанта
- KDS (Kitchen Display System) данные
- Контроль времени приготовления
- Уведомление официанта
- Оценка качества

## Unified Warehouse

**Сущности:**
- `UnifiedWarehouse` — единый склад
- `UnifiedStock` — единый stock
- `InventoryReservation` — резервирование

**Переключение B2B/B2C:**
- Раздельная емкость (capacity)
- Раздельное использование (capacity_used)
- Метод `switchCapacity()` для переключения
- Резервирование по типу заказа
- Авто-распределение при необходимости

## Analytics Integration

**Сервис:** `CRMAnalyticsService`

**Метрики:**
- B2C статистика (клиенты, сделки, LTV)
- B2B статистика (лиды, конверсия, pipeline)
- Конверсионные воронки
- Выручка по типам
- Ежемесячные тренды
- Экспорт в ClickHouse

## API Endpoints

```php
// Tenant Panel
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // CRM
    Route::get('/tenant/stats', [TenantController::class, 'getStats']);
    Route::get('/tenant/orders', [TenantController::class, 'getOrders']);
    
    // Wallet
    Route::get('/wallet/balance', [WalletController::class, 'getBalance']);
    Route::get('/wallet/transactions', [WalletController::class, 'getTransactions']);
    Route::post('/wallet/documents/generate', [WalletController::class, 'generateDocument']);
    
    // Analytics
    Route::get('/analytics/crm', [CRMAnalyticsController::class, 'getStats']);
    Route::get('/analytics/funnel', [CRMAnalyticsController::class, 'getFunnel']);
    
    // Vertical Operations
    Route::apiResource('hotels/cleanings', RoomCleaningController::class);
    Route::post('hotels/cleanings/{id}/assign', [RoomCleaningController::class, 'assign']);
    Route::post('hotels/cleanings/{id}/complete', [RoomCleaningController::class, 'complete']);
    
    Route::apiResource('restaurants/fulfillments', OrderFulfillmentController::class);
    Route::post('restaurants/fulfillments/{id}/start', [OrderFulfillmentController::class, 'start']);
    Route::post('restaurants/fulfillments/{id}/ready', [OrderFulfillmentController::class, 'markReady']);
    Route::post('restaurants/fulfillments/{id}/serve', [OrderFulfillmentController::class, 'markServed']);
    
    // Unified Warehouse
    Route::get('/warehouse/stats', [UnifiedWarehouseController::class, 'getStats']);
    Route::post('/warehouse/switch-capacity', [UnifiedWarehouseController::class, 'switchCapacity']);
    Route::post('/warehouse/reserve', [UnifiedWarehouseController::class, 'reserve']);
});
```

## Database Migrations

1. `2026_04_27_000001_add_b2b_entities_to_crm.php` — B2B сущности CRM
2. `2026_04_27_000002_create_unified_warehouse_system.php` — Единый склад
3. `2026_04_27_000003_create_vertical_operations_tables.php` — Операции вертикалей

## Performance

- Кэширование статистики (Redis)
- Оптимизированные запросы с индексами
- Пагинация для больших списков
- Асинхронная генерация документов
- WebSockets для real-time обновлений

## Security

- Tenant isolation
- Role-based access control
- Audit logging для финансовых операций
- PII защита контактов
- Rate limiting для API
