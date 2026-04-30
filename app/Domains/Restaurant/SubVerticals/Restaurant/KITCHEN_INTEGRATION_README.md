# Промышленная интеграция KDS с кухонным оборудованием

## Обзор

Система интеграции KDS с кухонным оборудованием построена на архитектуре драйверов (Strategy Pattern), что позволяет поддерживать различные типы оборудования без изменения основного кода.

**Важно:** Все интеграции - внутренние (кроме аппаратных принтеров и сигнализации). Интеграции с внешними системами учёта (r_keeper, iiko, Poster, 1C) не поддерживаются - всё остаётся внутри платформы CatVRF.

## Архитектура драйверов

```
KitchenIntegrationService (оркестратор)
    ├── KdsDisplayDriver (внутренний WebSocket)
    ├── KitchenPrinterDriver (аппаратный: Star Micronics, Epson, Citizen)
    ├── SignalSystemDriver (аппаратный: звук/свет)
    ├── MqttDriver (внутренний MQTT для планшетов)
    └── BrowserPrintDriver (внутренний браузерная печать)
```

## Поддерживаемые драйверы

### Уровень 1 - Аппаратные интеграции

#### 1. Kitchen Printer Driver
**Тип:** Аппаратный  
**Поддерживаемые принтеры:** Star Micronics, Epson, Citizen  
**Типы подключения:**
- Network (TCP/IP) - порт 9100
- USB - через device path (/dev/usb/lp0)
- Bluetooth - через bluez (требует доп. настройки)

**Функции:**
- Печать заказа на станцию при создании
- Печать стикера "ГОТОВО" при завершении
- Печать стикера "ОТМЕНО" при отмене
- Автоматический retry при ошибках (3 попытки)

**Конфигурация:**
```php
'kitchen_printer' => [
    'enabled' => true,
    'timeout' => 10,
    'printers' => [
        1 => [ // station_id
            'name' => 'Hot Kitchen Printer',
            'type' => 'network',
            'host' => '192.168.1.100',
            'port' => 9100,
        ],
        2 => [
            'name' => 'Bar Printer',
            'type' => 'usb',
            'device' => '/dev/usb/lp0',
        ],
    ],
],
```

#### 2. Signal System Driver
**Тип:** Аппаратный  
**Функции:** Звуковая и световая сигнализация

**Типы оповещений:**
- `order_ready` - мелодия (chime) + зелёный свет
- `order_problem` - сигнал тревоги (alarm) + оранжевый свет
- `order_overdue` - экстренный сигнал (urgent) + красный строб
- `order_cancelled` - beep + синий свет

**Конфигурация:**
```php
'signal_system' => [
    'enabled' => true,
    'api_url' => env('SIGNAL_SYSTEM_API_URL'),
    'api_key' => env('SIGNAL_SYSTEM_API_KEY'),
    'sound_enabled' => true,
    'light_enabled' => true,
    'timeout' => 5,
],
```

### Уровень 2 - Внутренние протоколы

#### 3. KDS Display Driver (Internal WebSocket)
**Тип:** Внутренний  
**Протокол:** Laravel Echo / WebSocket  
**Функции:** Отображение заказов на KDS в реальном времени

**Как работает:**
- Использует существующие WebSocket события (OrderSentToKitchen, OrderStatusUpdated)
- Интегрирован с Livewire компонентами
- Не требует внешнего API

**Конфигурация:**
```php
'kds_display' => [
    'enabled' => true, // Всегда доступен
],
```

#### 4. MQTT Driver
**Тип:** Внутренний  
**Протокол:** MQTT (Mosquitto, EMQX, HiveMQ)  
**Функции:** Связь с планшетами поваров

**Topics:**
- `kitchen/station/{stationId}/orders` - новые заказы
- `kitchen/station/{stationId}/orders/{orderId}/status` - обновления статуса
- `kitchen/station/{stationId}/orders/{orderId}/cancel` - отмена

**Конфигурация:**
```php
'mqtt' => [
    'enabled' => true,
    'host' => env('MQTT_HOST', 'localhost'),
    'port' => env('MQTT_PORT', 1883),
    'client_id' => 'catvrf-kds',
    'username' => env('MQTT_USERNAME'),
    'password' => env('MQTT_PASSWORD'),
    'use_tls' => false,
    'subscribed_stations' => [1, 2, 3],
],
```

**Требования:**
```bash
composer require php-mqtt/client
```

### Уровень 3 - Лёгкая интеграция

#### 5. Browser Print Driver
**Тип:** Внутренний  
**Протокол:** JavaScript window.print()  
**Функции:** Печать через браузер для недорогих термопринтеров

**Как работает:**
- Данные для печати сохраняются в кэш Redis
- Livewire компонент загружает данные и вызывает window.print()
- Подходит для облачных принтеров (Google Cloud Print)

**Конфигурация:**
```php
'browser_print' => [
    'enabled' => true,
],
```

## Использование

### Автоматическая отправка заказов

```php
use Modules\Restaurant\Application\Services\KitchenIntegrationService;

$integrationService = app(KitchenIntegrationService::class);

// Отправить заказ на все активные драйверы
$enabledDrivers = ['kds_display', 'kitchen_printer', 'signal_system'];
$results = $integrationService->sendOrderToIntegrations($orderStatus, $enabledDrivers);

// Результат:
// [
//     'kds_display' => ['success' => true],
//     'kitchen_printer' => ['success' => true],
//     'signal_system' => ['success' => true],
// ]
```

### Обновление статуса

```php
// Обновить статус на всех драйверах
$results = $integrationService->updateOrderStatusOnIntegrations($orderStatus, $enabledDrivers);
```

### Отмена заказа

```php
// Отменить заказ на всех драйверах
$results = $integrationService->cancelOrderOnIntegrations($orderStatus, $enabledDrivers);
```

### Проверка соединений

```php
// Проверить состояние всех драйверов
$status = $integrationService->checkAllConnections($enabledDrivers);

// Результат:
// [
//     'kds_display' => ['connected' => true, 'available' => true, 'status' => [...]],
//     'kitchen_printer' => ['connected' => false, 'error' => '...'],
// ]
```

### Получение доступных драйверов

```php
$available = $integrationService->getAvailableDrivers();
```

## Интеграция с KitchenService

Автоматическая интеграция при создании/обновлении заказов:

```php
// В KitchenService
public function sendOrderToKitchen(...): OrderKitchenStatus
{
    // ... сохранение в БД ...
    
    // Автоматическая отправка на интеграции
    if (config('restaurant-integration.auto_send_on_create')) {
        $enabledDrivers = config('restaurant-integration.enabled_drivers.default', []);
        $this->integrationService->sendOrderToIntegrations($status, $enabledDrivers);
    }
    
    return $status;
}
```

## Мониторинг

### Метрики Prometheus

Автоматический экспорт метрик:
- `kds_integration_success_total{driver}` - успешные операции
- `kds_integration_failure_total{driver}` - неудачные операции
- `kds_integration_retry_total{driver}` - количество ретраев
- `kds_integration_duration_seconds{driver}` - время выполнения

### Логи

Все операции логируются с уровнем INFO:
```php
Log::info("Order sent to driver: kds_display", [
    'order_id' => $orderStatus->orderId,
    'success' => true,
]);
```

Ошибки логируются с уровнем ERROR с полным stack trace.

## Troubleshooting

### Принтер не печатает

1. Проверьте соединение: `php artisan kds:check-printers`
2. Проверьте конфигурацию `config/restaurant-integration.php`
3. Проверьте сетевое соединение (для network принтеров):
   ```bash
   telnet 192.168.1.100 9100
   ```
4. Проверьте логи: `tail -f storage/logs/laravel.log | grep kitchen_printer`

### MQTT не подключается

1. Проверьте, что MQTT сервер запущен
2. Проверьте настройки в `.env`
3. Проверьте логи MQTT сервера
4. Тест подписки:
   ```bash
   mosquitto_sub -h localhost -t kitchen/# -v
   ```

### WebSocket не обновляется

1. Проверьте конфигурацию `config/broadcasting.php`
2. Проверьте, что Pusher/Laravel Echo Server запущен
3. Проверьте консоль браузера на ошибки JavaScript
4. Проверьте авторизацию каналов в `routes/channels.php`

## Безопасность

### Аутентификация

- Все внешние API требуют `X-API-Key` header
- MQTT поддерживает username/password
- WebSocket использует стандартную авторизацию Laravel

### Изоляция по tenant

Все драйверы автоматически изолируют данные по tenant через global scopes.

## Тестирование

```bash
# Запустить все тесты интеграций
php artisan test --filter=KitchenIntegrationServiceTest

# Проверить конкретный драйвер
php artisan test --filter=KitchenPrinterDriverTest
```

## Требования к зависимостям

```bash
# Для MQTT
composer require php-mqtt/client

# Для HTTP запросов (уже в Laravel)
# guzzlehttp/guzzle
```

## Roadmap

- [ ] Поддержка 更多 типов принтеров (Zebra, Bixolon)
- [ ] Интеграция с голосовыми ассистентами
- [ ] Поддержка IoT сенсоров (температура, влажность)
- [ ] Автоматическая калибровка принтеров
- [ ] WebSocket fallback для MQTT

## Поддержка

Для вопросов и проблем обращайтесь к команде разработки CatVRF.
