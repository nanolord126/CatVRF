# KDS (Kitchen Display System) Integration — CatVRF Restaurant Module

**Версия:** 1.0  
**Статус:** Production Ready  
**Последнее обновление:** Апрель 2026

---

## Обзор

KDS (Kitchen Display System) — это полноценная система интеграции кухонного оборудования для CatVRF Restaurant Module. Система позволяет автоматически передавать заказы на кухонные дисплеи, принтеры, сигнальные системы и другие устройства в реальном времени.

**Ключевые возможности:**
- Автоматическая маршрутизация заказов по кухонным станциям
- Реал-тайм обновления статусов через WebSocket
- Поддержка множества типов оборудования (KDS, принтеры, сигнализация, MQTT, HTTP)
- Оффлайн-режим с последующей синхронизацией
- Интеграция с заказами из маркетплейса CatVRF
- Полная настройка через Filament Admin Panel

---

## Архитектура

### Компоненты системы

```
┌─────────────────────────────────────────────────────────────┐
│                     CatVRF Order System                      │
│              (Заказы из зала, маркетплейса, доставки)        │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│              KitchenIntegrationService                        │
│         (Оркестрация драйверов и маршрутизация)               │
└──────┬──────────────┬──────────────┬──────────────┬─────────┘
       │              │              │              │
       ▼              ▼              ▼              ▼
┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│ KDS Display │ │   Printer   │ │  Signal Sys │ │    MQTT     │
│  (WebSocket)│ │  (ESC/POS)  │ │  (Sound/Light)│ │  (Tablets)  │
└─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘
```

### Драйверы интеграции

| Драйвер | Тип | Описание | Уровень |
|---------|-----|-----------|--------|
| **KDS Display** | Внутренний | WebSocket дисплей для поваров | 1 |
| **Kitchen Printer** | Аппаратный | ESC/POS принтеры (Star, Epson, Citizen) | 1 |
| **Signal System** | Аппаратный | Звуковая/световая сигнализация | 1 |
| **MQTT** | Протокол | Для планшетов поваров | 2 |
| **Browser Print** | Лёгкий | Печать через браузер | 3 |
| **Generic HTTP** | Универсальный | Кастомные HTTP интеграции | 3 |

---

## Установка и настройка

### 1. Базовая настройка

```bash
# Опубликовать конфигурацию
php artisan vendor:publish --tag=kitchen-config

# Запустить миграции
php artisan migrate
```

### 2. Конфигурация `.env`

```bash
# KDS Display (включён по умолчанию)
KDS_DISPLAY_ENABLED=true
KDS_REFRESH_INTERVAL=5
KDS_AUTO_REFRESH=true

# Kitchen Printer
KITCHEN_PRINTER_ENABLED=false

# Signal System
SIGNAL_SYSTEM_ENABLED=false
SIGNAL_SYSTEM_API_URL=https://your-signal-system.local
SIGNAL_SYSTEM_API_KEY=your-api-key

# MQTT
KITCHEN_MQTT_ENABLED=false
KITCHEN_MQTT_HOST=localhost
KITCHEN_MQTT_PORT=1883
KITCHEN_MQTT_USERNAME=
KITCHEN_MQTT_PASSWORD=

# Browser Print
BROWSER_PRINT_ENABLED=false

# Generic HTTP
GENERIC_HTTP_ENABLED=false
GENERIC_HTTP_HEALTH_CHECK_URL=https://api.example.com/health
GENERIC_HTTP_AUTH_TYPE=bearer
GENERIC_HTTP_AUTH_TOKEN=your-token

# Offline Mode
KITCHEN_OFFLINE_MODE=true
```

### 3. Настройка кухонных станций

Зайдите в Filament Admin Panel → Restaurant → Kitchen Stations и создайте станции:

1. **Холодный цех** (cold) — для салатов, закусок
2. **Горячий цех** (hot) — для горячих блюд
3. **Бар** (bar) — для напитков
4. **Десерты** (dessert) — для десертов
5. **Экспедиция** (expedition) — для сбора готовых заказов

---

## Подключение оборудования

### Кухонные принтеры (ESC/POS)

#### Поддерживаемые модели

**Star Micronics:**
- TSP143 (WiFi/Ethernet)
- MC-Print3 (Bluetooth/WiFi)
- mC-Print3
- SM-S210i
- SM-T300i

**Epson:**
- TM-T88V
- TM-T88VI
- TM-m30
- TM-L90

**Citizen:**
- CT-S310II
- CT-S651II

#### Настройка сетевого принтера

1. Подключите принтер к сети (Ethernet/WiFi)
2. Получите IP адрес принтера
3. Добавьте принтер в `config/kitchen.php`:

```php
'kitchen_printer' => [
    'enabled' => true,
    'printers' => [
        '1' => [ // ID кухонной станции
            'name' => 'Холодный цех принтер',
            'type' => 'network',
            'host' => '192.168.1.100',
            'port' => 9100,
            'model' => 'star_tsp143',
        ],
    ],
],
```

#### Настройка USB принтера (Linux)

```php
'2' => [
    'name' => 'Бар принтер',
    'type' => 'usb',
    'device' => '/dev/usb/lp0',
    'model' => 'star_tsp143',
],
```

**Требования:**
- Права на устройство: `sudo chmod 666 /dev/usb/lp0`
- Добавить пользователя в группу `lp`: `sudo usermod -a -G lp www-data`

#### Тестирование принтера

```bash
# Тестовое подключение
telnet 192.168.1.100 9100

# Или через PHP
php artisan kitchen:test-printer 1
```

### KDS Дисплеи (WebSocket)

#### Требования

- Планшет или моноблок с Android/iOS/Windows
- Браузер с поддержкой WebSocket (Chrome, Safari, Edge)
- Стабильное интернет-подключение

#### Настройка

1. Откройте URL: `https://your-domain.com/kitchen/{station_id}`
2. Система автоматически подключится к WebSocket
3. Заказы будут отображаться в реальном времени

#### Примеры URL

- Холодный цех: `/kitchen/1`
- Горячий цех: `/kitchen/2`
- Бар: `/kitchen/3`

### Сигнальная система

#### Требования к API

Сигнальная система должна предоставлять REST API:

**Health Check:**
```
GET /ping
Response: { "pong": true }
```

**Alert Endpoint:**
```
POST /alert
Content-Type: application/json
X-API-Key: your-api-key

Body: {
  "type": "order_ready",
  "order_id": 123,
  "kitchen_station_id": 1,
  "priority": "high",
  "sound": { "type": "chime", "volume": 80 },
  "light": { "color": "green", "pattern": "blink" }
}
```

#### Настройка

```php
'signal_system' => [
    'enabled' => true,
    'api_url' => 'https://your-signal-system.local',
    'api_key' => 'your-api-key',
    'sound_enabled' => true,
    'light_enabled' => true,
],
```

### MQTT для планшетов

#### Требования

- MQTT Broker (Mosquitto, EMQX, HiveMQ)
- Поддержка QoS 1
- TLS опционально

#### Настройка брокера (Mosquitto)

```bash
# Установка Mosquitto
sudo apt install mosquitto mosquitto-clients

# Конфигурация /etc/mosquitto/mosquitto.conf
listener 1883
allow_anonymous false
password_file /etc/mosquitto/passwd

# Создание пароля
sudo mosquitto_passwd -c /etc/mosquitto/passwd catvrf-kds
```

#### Настройка в CatVRF

```php
'mqtt' => [
    'enabled' => true,
    'host' => 'localhost',
    'port' => 1883,
    'username' => 'catvrf-kds',
    'password' => 'your-password',
    'use_tls' => false,
],
```

#### Топики MQTT

```
kitchen/station/{station_id}/orders          # Новые заказы
kitchen/station/{station_id}/orders/{order_id}/status  # Обновление статуса
kitchen/station/{station_id}/orders/{order_id}/cancel   # Отмена
```

### Generic HTTP Driver

Для кастомных интеграций с внутренними системами.

#### Пример конфигурации

```php
'generic_http' => [
    'enabled' => true,
    'auth_type' => 'bearer',
    'auth_token' => 'your-jwt-token',
    'endpoints' => [
        '1' => [
            'name' => 'Холодный цех API',
            'url' => 'https://internal-api.local/kitchen/orders',
            'method' => 'POST',
            'timeout' => 10,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Station-ID' => '1',
            ],
            'status_update_url' => 'https://internal-api.local/kitchen/orders/{order_id}/status',
            'cancel_url' => 'https://internal-api.local/kitchen/orders/{order_id}/cancel',
            'payload_template' => [
                'order_id' => '{{order_id}}',
                'station_id' => '{{kitchen_station_id}}',
                'priority' => '{{priority}}',
                'items' => '{{items}}',
            ],
        ],
    ],
],
```

---

## Чек-лист совместимости оборудования

### Принтеры

| Модель | Тип подключения | ESC/POS | Статус | Примечания |
|--------|----------------|---------|--------|------------|
| Star TSP143 | Ethernet/WiFi | ✅ | Поддерживается | Рекомендуется |
| Star MC-Print3 | Bluetooth | ✅ | Поддерживается | Требует bluez |
| Epson TM-T88V | Ethernet | ✅ | Поддерживается | Рекомендуется |
| Epson TM-m30 | WiFi | ✅ | Поддерживается | |
| Citizen CT-S310II | Ethernet | ✅ | Поддерживается | |
| Любой ESC/POS | Network | ✅ | Поддерживается | Через Generic HTTP |

### KDS Дисплеи

| Устройство | ОС | Браузер | WebSocket | Статус |
|------------|-----|---------|------------|--------|
| iPad Pro | iOS 14+ | Safari | ✅ | Поддерживается |
| Android Tablet | Android 10+ | Chrome | ✅ | Поддерживается |
| Windows Tablet | Windows 10+ | Edge/Chrome | ✅ | Поддерживается |
| Любой браузер | Any | Chrome 90+ | ✅ | Поддерживается |

### MQTT Брокеры

| Брокер | Версия | QoS 1 | TLS | Статус |
|--------|--------|-------|-----|--------|
| Mosquitto | 2.0+ | ✅ | ✅ | Поддерживается |
| EMQX | 4.0+ | ✅ | ✅ | Поддерживается |
| HiveMQ | 4.0+ | ✅ | ✅ | Поддерживается |

---

## Использование

### Отправка заказа на кухню

```php
use Modules\Restaurant\Application\Services\KitchenIntegrationService;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;
use Modules\Restaurant\Domain\Enums\OrderPriority;
use Modules\Restaurant\Domain\ValueObjects\PreparationTime;

// Создание статуса заказа для кухни
$orderStatus = OrderKitchenStatus::create(
    orderId: $order->id,
    kitchenStationId: 1, // Холодный цех
    estimatedPreparationTime: new PreparationTime(15),
    priority: OrderPriority::NORMAL,
    isFromMarketplace: false,
    isVip: false,
);

// Сохранение в БД
$repository = app(OrderKitchenStatusRepositoryInterface::class);
$repository->save($orderStatus);

// Отправка на все активные драйверы
$kitchenService = new KitchenIntegrationService(tenant()->id);
$enabledDrivers = ['kds_display', 'kitchen_printer'];
$results = $kitchenService->sendOrderToIntegrations($orderStatus, $enabledDrivers);
```

### Обновление статуса заказа

```php
// Повар нажал "В РАБОТУ"
$updatedStatus = $orderStatus->withStatus(OrderKitchenStatusEnum::IN_PROGRESS);
$repository->save($updatedStatus);

$kitchenService->updateOrderStatusOnIntegrations($updatedStatus, $enabledDrivers);
```

### KDS Display для повара

Откройте в браузере:
```
https://your-domain.com/kitchen/1
```

**Функции:**
- Отображение активных заказов
- Таймер обратного отсчёта
- Кнопки "В РАБОТУ" и "ГОТОВО"
- Фильтрация по статусу
- Автообновление каждые 5 секунд

---

## Оффлайн-режим

При потере соединения:

1. Заказы сохраняются локально в Redis
2. При восстановлении связи — автоматическая синхронизация
3. TTL локального хранилища: 1 час

**Настройка:**
```php
'offline_mode' => [
    'enabled' => true,
    'sync_on_reconnect' => true,
    'local_storage_ttl' => 3600,
],
```

---

## Мониторинг и логи

### Проверка статуса драйверов

```php
$kitchenService = new KitchenIntegrationService(tenant()->id);
$status = $kitchenService->checkAllConnections(['kds_display', 'kitchen_printer']);

// Результат:
// [
//     'kds_display' => ['connected' => true, 'available' => true],
//     'kitchen_printer' => ['connected' => false, 'error' => 'Connection timeout'],
// ]
```

### Логи

Логи драйверов пишутся в `storage/logs/laravel.log`:

```bash
# Просмотр логов KDS
tail -f storage/logs/laravel.log | grep kitchen

# Фильтрация по драйверу
tail -f storage/logs/laravel.log | grep "kitchen_printer"
```

---

## Безопасность

### Рекомендации

1. **Сеть:** Используйте отдельную VLAN для кухонного оборудования
2. **VPN:** Для удалённого доступа к принтерам используйте VPN
3. **API Keys:** Храните в `.env`, никогда не коммитите в Git
4. **TLS:** Включите TLS для MQTT если используется публичный брокер
5. **Firewall:** Ограничьте доступ к портам принтеров (9100)

### Аутентификация

**Generic HTTP Driver поддерживает:**
- Bearer Token (JWT)
- Basic Auth
- API Key (custom header)
- Digest Auth

---

## Troubleshooting

### Принтер не печатает

**Проблема:** Принтер не отвечает на ping

**Решение:**
```bash
# Проверьте соединение
telnet 192.168.1.100 9100

# Проверьте firewall
sudo ufw allow 9100/tcp

# Проверьте права на USB (для USB принтеров)
ls -l /dev/usb/lp0
sudo chmod 666 /dev/usb/lp0
```

### WebSocket не подключается

**Проблема:** KDS дисплей показывает "Disconnected"

**Решение:**
```bash
# Проверьте конфигурацию broadcasting
php artisan config:clear

# Проверьте Redis
redis-cli ping

# Проверьте очередь
php artisan queue:work
```

### MQTT не публикует сообщения

**Проблема:** Сообщения не доходят до планшетов

**Решение:**
```bash
# Проверьте брокер
mosquitto_sub -h localhost -t "kitchen/station/+/orders" -v

# Проверьте логи брокера
sudo journalctl -u mosquitto -f
```

---

## Тестирование

### Unit тесты

```bash
# Запустить все тесты KDS
php artisan test --filter=Kitchen

# Запустить тесты конкретного драйвера
php artisan test --filter=KitchenPrinterDriver
```

### Ручное тестирование

```bash
# Тест принтера
php artisan kitchen:test-printer 1

# Тест MQTT подключения
php artisan kitchen:test-mqtt

# Тест всех драйверов
php artisan kitchen:test-all
```

---

## Roadmap

### Планируется (Q2 2026)

- [ ] IoT датчики (температура холодильников)
- [ ] Компьютерное зрение для распознавания готовности
- [ ] Умные весы для ингредиентов
- [ ] Интеграция с 1С:Общепит (через HTTP API)

### В разработке

- [ ] Мобильное приложение для поваров (React Native)
- [ ] Аналитика производительности кухни
- [ ] AI-предсказание времени приготовления

---

## Поддержка

**Документация:** `modules/Restaurant/KDS_INTEGRATION_README.md`  
**Конфигурация:** `config/kitchen.php`  
**Issues:** GitHub Issues (nanolord126/CatVRF)

---

## Лицензия

CatVRF Restaurant Module — часть проекта CatVRF.  
© 2026 CatVRF Team. Все права защищены.
