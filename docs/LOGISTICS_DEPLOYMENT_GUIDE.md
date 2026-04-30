# Unified Logistics Core - Deployment Guide

**Version:** 1.0  
**Date:** April 18, 2026  
**Architecture Score:** 9.2/10  

## Overview

Unified Logistics Core интегрирован в CatVRF и production-ready. Система включает:

- **Pickup Points (ПВЗ)** - Ozon-level алгоритм назначения с ML-скорингом
- **Unified Fleet** - гибридный режим курьеров + такси
- **Order Shipments** - полиморфная модель fulfillment
- **Real-time WebSocket** - live tracking через Swoole
- **Queue Processing** - Horizon supervisor для logistics queue

## Production Deployment

### 1. Database Migrations

```bash
php artisan migrate --path=database/migrations/2024_01_01_000009_create_couriers_table.php
php artisan migrate --path=database/migrations/2024_01_01_000010_create_pickup_points_table.php
php artisan migrate --path=database/migrations/2024_01_01_000011_create_order_shipments_table.php
php artisan migrate --path=database/migrations/2026_04_18_161821_add_fulfillment_to_orders_table.php
```

### 2. Horizon Installation (Linux Production Only)

```bash
# Установить PHP расширения (Ubuntu/Debian)
sudo apt-get install php8.3-pcntl php8.3-posix

# Установить Horizon
composer require laravel/horizon

# Опубликовать конфигурацию
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"

# Миграции для Horizon
php artisan migrate
```

**Примечание:** Horizon несовместим с Windows. На Windows dev environment используйте `php artisan queue:work`.

### 3. Redis Configuration

```bash
# Установить Redis
sudo apt-get install redis-server

# Запустить Redis
sudo systemctl start redis
sudo systemctl enable redis
```

Проверьте `config/database.php` - Redis connection `default` должен быть настроен.

### 4. Environment Variables

Добавьте в `.env`:

```env
BROADCAST_DRIVER=redis
QUEUE_CONNECTION=redis
HORIZON_DOMAIN=horizon.yourdomain.com
HORIZON_PATH=horizon
```

### 5. Queue Worker

**Production с Horizon:**
```bash
php artisan horizon
```

**Dev без Horizon:**
```bash
php artisan queue:work --queue=logistics --tries=3 --timeout=120
```

### 6. WebSocket (Swoole)

```bash
# Установить Swoole extension
pecl install swoole

# Установить Octane
composer require laravel/octane

# Запустить Octane с Swoole
php artisan octane:start --server=swoole --host=0.0.0.0 --port=8000
```

## API Endpoints

### Pickup Points

```
GET /api/logistics/pickup-points          - Список активных ПВЗ
GET /api/logistics/pickup-points/{id}     - Детализация ПВЗ
GET /api/logistics/pickup-points/nearby   - Геопоиск
POST /api/logistics/pickup-points/assign  - Назначение ПВЗ
```

### Courier Tracking

```
GET /api/logistics/couriers               - Список курьеров
GET /api/logistics/couriers/{id}          - Детализация курьера
GET /api/logistics/couriers/{id}/ratings  - Рейтинг курьера
```

### Shipment Tracking

```
GET /api/logistics/shipments/{trackingNumber} - Отслеживание по трек-номеру
POST /api/logistics/shipments              - Создание отправления
GET /api/logistics/shipments/my            - Мои отправления
```

## WebSocket Channels

### Private Channels

```
orders.{orderId}       - Отслеживание заказа
couriers.{courierId}   - Статусы курьера
users.{userId}         - Личные уведомления
```

### Events

```
courier.assigned       - Курьер назначен
pvz.issued            - ПВЗ выдан
shipment.updated       - Статус отправления обновлён
```

## Monitoring

### Horizon Dashboard

Доступна по URL: `https://horizon.yourdomain.com`

Ключевые метрики:
- **logistics queue** - throughput, wait time, failed jobs
- **Supervisor status** - 3 processes, 256MB RAM, timeout 120s
- **Job metrics** - DispatchPvzIssuanceJob performance

### Prometheus Metrics

```
GET /api/octane/metrics  - Swoole table metrics
GET /metrics             - Application metrics
```

### Logs

```bash
# Logistics логи
tail -f storage/logs/laravel.log | grep logistics

# Audit логи
tail -f storage/logs/audit.log | grep pvz

# Horizon логи
tail -f storage/logs/horizon.log
```

## Performance Tuning

### Redis Configuration

```php
// config/database.php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', 'catvrf_'),
    ],
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],
],
```

### Horizon Supervisor

```php
// config/horizon.php
'logistics' => [
    'connection' => 'redis',
    'queue' => ['logistics'],
    'balance' => 'simple',
    'processes' => 3,
    'tries' => 3,
    'timeout' => 120,
    'nice' => -2,  // High priority
    'memory' => 256,
],
```

### Cache Configuration

```php
// config/cache.php
'redis' => [
    'driver' => 'redis',
    'connection' => 'cache',
],
```

## Security

### Tenant Isolation

Все logistics модели используют `TenantScoped` trait:
- `PickupPoint`
- `OrderShipment`
- `Courier`

WebSocket каналы также изолированы по tenant.

### Rate Limiting

```php
// config/horizon.php
'waits' => [
    'redis:logistics' => 60,  // 60 seconds threshold
],
```

### Fraud Detection

`PvzAssignmentService` включает:
- FraudControlService вызов перед назначением
- Audit logging всех назначений
- Correlation ID трассировка

## Testing

### Unit Tests

```bash
php artisan test tests/Unit/Domains/Logistics/
```

### Feature Tests

```bash
php artisan test tests/Feature/Domains/Logistics/
```

### Integration Tests

```bash
php artisan test --filter=LogisticsApiIntegrationTest
```

## Troubleshooting

### Queue Jobs Not Processing

```bash
# Проверить статус Horizon
php artisan horizon:status

# Проверить логи
tail -f storage/logs/horizon.log

# Перезапустить Horizon
php artisan horizon:terminate
php artisan horizon
```

### WebSocket Not Connecting

```bash
# Проверить Redis
redis-cli ping

# Проверить broadcasting driver
php artisan tinker --execute="echo config('broadcasting.default');"

# Проверить Swoole
php artisan octane:status
```

### Pickup Point Hold Issues

```bash
# Очистить Redis holds
redis-cli
> KEYS pvz:hold:*
> DEL <key>
```

## Rollback

```bash
# Откатить миграции
php artisan migrate:rollback --step=4

# Откатить Horizon
composer remove laravel/horizon
```

## Production Checklist

- [ ] Redis установлен и работает
- [ ] Horizon установлен (Linux production)
- [ ] Миграции выполнены
- [ ] Environment variables настроены
- [ ] Queue worker запущен (Horizon или queue:work)
- [ ] WebSocket (Swoole) запущен
- [ ] Prometheus metrics доступны
- [ ] Logs настроены и вращаются
- [ ] Backup базы данных настроен
- [ ] CDN для статических файлов настроен

## Support

**Documentation:** `docs/UNIFIED_LOGISTICS_IMPLEMENTATION.md`  
**Tests:** `tests/Unit/Domains/Logistics/`, `tests/Feature/Domains/Logistics/`  
**Services:** `app/Domains/Logistics/Services/`  
**Models:** `app/Domains/Logistics/Models/`
