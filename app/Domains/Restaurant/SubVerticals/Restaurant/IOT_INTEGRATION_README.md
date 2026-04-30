# IoT Integration — CatVRF Restaurant Module

**Версия:** 1.0  
**Статус:** Production Ready  
**Дата:** Апрель 2026

---

## Обзор

Полноценная IoT-интеграция для ресторанной вертикали CatVRF, обеспечивающая автоматизацию кухонных процессов через умные датчики, весы, таймеры и другое оборудование.

### Ключевые возможности

- **Умные датчики температуры** — мониторинг холодильников, морозильников, витрин с автоматическими оповещениями
- **Умные весы** — автоматическое взвешивание ингредиентов и списание со склада
- **Умные таймеры** — автоматический запуск при получении заказа на кухню
- **KDS дисплеи** — интеграция с существующей системой Kitchen Display System
- **IoT принтеры** — автоматическая печать заказов
- **Датчики открытия дверей** — контроль доступа к холодильным камерам
- **Поддержка протоколов** — MQTT, WebSocket, Modbus TCP/RTU, HTTP

---

## Архитектура

```
┌─────────────────────────────────────────────────────────────┐
│                     CatVRF Restaurant                       │
│                      (Laravel 11+)                          │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                    IoTHubService                            │
│         (Центральный сервис управления IoT)                  │
└──────┬──────────────┬──────────────┬──────────────┬─────────┘
       │              │              │              │
       ▼              ▼              ▼              ▼
┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│   MQTT      │ │  WebSocket  │ │  Modbus TCP │ │    HTTP     │
│  (Mosquitto)│ │  (Echo)     │ │  (Industrial)│ │  (REST API) │
└─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘
       │              │              │              │
       ▼              ▼              ▼              ▼
┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│ Датчики     │ │ KDS Дисплеи │ │ Весы       │ │ Принтеры    │
│ температуры │ │ (планшеты)  │ │ (умные)    │ │ (ESC/POS)   │
└─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘
```

---

## Установка

### 1. Выполнение миграций

```bash
php artisan migrate
```

Будут созданы таблицы:
- `iot_devices` — реестр IoT устройств
- `iot_telemetry` — телеметрия (time-series данные)
- `iot_alert_rules` — правила оповещений

### 2. Настройка MQTT Broker (опционально)

Для работы с MQTT устройствами требуется брокер сообщений.

#### Установка Mosquitto (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install mosquitto mosquitto-clients

# Настройка конфигурации
sudo nano /etc/mosquitto/mosquitto.conf
```

Добавьте в конфиг:

```
listener 1883
allow_anonymous false
password_file /etc/mosquitto/passwd
```

Создайте пароль:

```bash
sudo mosquitto_passwd -c /etc/mosquitto/passwd catvrf_iot
sudo systemctl restart mosquitto
```

### 3. Конфигурация `.env`

```bash
# IoT Hub Configuration
IOT_MQTT_ENABLED=true
IOT_MQTT_HOST=localhost
IOT_MQTT_PORT=1883
IOT_MQTT_USERNAME=catvrf_iot
IOT_MQTT_PASSWORD=your_password_here

# WebSocket для реал-тайм обновлений
IOT_WEBSOCKET_ENABLED=true

# Настройки очистки телеметрии
IOT_TELEMETRY_RETENTION_DAYS=90
```

### 4. Регистрация Service Provider

Добавьте в `config/app.php`:

```php
'providers' => [
    // ...
    Modules\Restaurant\Infrastructure\Providers\RestaurantServiceProvider::class,
],
```

### 5. Настройка планировщика задач

Добавьте в `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Проверка offline устройств каждые 5 минут
    $schedule->job(new \Modules\Restaurant\Application\Jobs\CheckOfflineDevicesJob())
        ->everyFiveMinutes();
    
    // Очистка старой телеметрии каждую ночь
    $schedule->job(new \Modules\Restaurant\Application\Jobs\CleanupOldTelemetryJob(90))
        ->daily();
}
```

---

## Подключение оборудования

### Датчики температуры (Sonoff, Aqara, Tuya)

#### Sonoff TH16/TH10 с датчиком DS18B20

1. **Flash прошивка Tasmota**

```bash
# Загрузите Tasmota с https://tasmota.github.io/
# Прошеейте устройство согласно инструкции
```

2. **Настройка MQTT в Tasmota**

```
MQTT Host: ваш MQTT broker
MQTT Port: 1883
MQTT User: catvrf_iot
MQTT Password: ваш_пароль
Topic: sonoff/th16
Full Topic: %prefix%/%topic%/
```

3. **Регистрация устройства в CatVRF**

```php
use Modules\Restaurant\Application\Services\IoTHubService;
use Modules\Restaurant\Domain\Enums\IoTDeviceType;
use Modules\Restaurant\Domain\Enums\IoTProtocol;

$iotHub = app(IoTHubService::class);

$device = $iotHub->registerDevice(
    tenantId: 1,
    deviceIdentifier: 'sonoff-th16-001', // MAC или уникальный ID
    name: 'Холодильник №1',
    type: IoTDeviceType::TEMPERATURE_SENSOR,
    protocol: IoTProtocol::MQTT,
    kitchenStationId: 1, // Холодный цех
    brokerUrl: 'mqtt://localhost:1883',
    topicPrefix: 'sonoff/th16',
    metadata: [
        'model' => 'Sonoff TH16',
        'sensor_type' => 'DS18B20',
        'location' => 'Main refrigerator',
    ],
);
```

4. **Настройка автопубликации**

В Tasmota настройте автопубликацию температуры:

```
TelePeriod 300  // Публиковать каждые 5 минут
```

#### Aqara Temperature Sensor

Aqara работает через Zigbee2MQTT:

1. **Установка Zigbee2MQTT**

```bash
# Установите через Docker
docker run -d \
   --name zigbee2mqtt \
   -v $(pwd)/data:/app/data \
   -p 8080:8080 \
   koenkk/zigbee2mqtt
```

2. **Подключение к CatVRF**

```php
$device = $iotHub->registerDevice(
    tenantId: 1,
    deviceIdentifier: 'aqara-temp-001',
    name: 'Морозильник №1',
    type: IoTDeviceType::TEMPERATURE_SENSOR,
    protocol: IoTProtocol::MQTT,
    brokerUrl: 'mqtt://localhost:1883',
    topicPrefix: 'zigbee2mqtt/0x00158d0001234567',
);
```

---

### Умные весы (Mettler Toledo, Dymo)

#### Mettler Toledo через Modbus TCP

1. **Настройка весов**

```
IP Address: 192.168.1.100
Port: 502 (Modbus TCP default)
```

2. **Регистрация**

```php
$device = $iotHub->registerDevice(
    tenantId: 1,
    deviceIdentifier: 'mettler-001',
    name: 'Весы холодного цеха',
    type: IoTDeviceType::WEIGHT_SCALE,
    protocol: IoTProtocol::MODBUS_TCP,
    kitchenStationId: 1,
    connectionConfig => json_encode([
        'host' => '192.168.1.100',
        'port' => 502,
        'slave_id' => 1,
        'register_address' => 0,
    ]),
);
```

---

### Умные таймеры (ESP32 + Tasmota)

1. **Подключение ESP32 к Tasmota**

```bash
# Прошеейте ESP32 с Tasmota
# Настройте WiFi и MQTT
```

2. **Конфигурация**

```php
$device = $iotHub->registerDevice(
    tenantId: 1,
    deviceIdentifier: 'timer-esp32-001',
    name: 'Таймер горячего цеха',
    type: IoTDeviceType::SMART_TIMER,
    protocol: IoTProtocol::MQTT,
    kitchenStationId: 2, // Горячий цех
    topicPrefix => 'esp32/timer',
);
```

3. **Отправка команд**

```php
// Запустить таймер на 15 минут
$iotHub->sendCommand($device->id, 'start_timer', [
    'duration_minutes' => 15,
    'order_id' => 12345,
]);

// Остановить таймер
$iotHub->sendCommand($device->id, 'stop_timer');
```

---

### IoT Принтеры (Star Micronics, Epson)

#### Star TSP143 через сеть

1. **Настройка принтера**

```
IP Address: 192.168.1.150
Port: 9100
```

2. **Регистрация**

```php
$device = $iotHub->registerDevice(
    tenantId: 1,
    deviceIdentifier: 'star-tsp143-001',
    name: 'Принтер горячего цеха',
    type: IoTDeviceType::SMART_PRINTER,
    protocol: IoTProtocol::HTTP,
    kitchenStationId: 2,
    connectionConfig => json_encode([
        'url' => 'http://192.168.1.150:9100',
        'encoding' => 'CP866',
    ]),
);
```

3. **Печать заказа**

```php
$iotHub->sendCommand($device->id, 'print_order', [
    'order_id' => 12345,
    'items' => [
        ['name' => 'Борщ', 'quantity' => 2],
        ['name' => 'Пельмени', 'quantity' => 3],
    ],
    'timestamp' => now()->toIso8601String(),
]);
```

---

## Использование

### Получение телеметрии от устройства

```php
// Получить последние 100 записей телеметрии
$telemetry = $iotHub->getDeviceTelemetry($deviceId);

// Получить только данные температуры
$temperatureData = $iotHub->getDeviceTelemetry($deviceId, 'temperature', 100);
```

### Обработка входящих данных (MQTT Webhook)

Создайте endpoint для получения данных от MQTT bridge:

```php
// routes/api.php
Route::post('/iot/telemetry/{deviceIdentifier}', function (string $deviceIdentifier, Request $request) {
    $iotHub = app(IoTHubService::class);
    $iotHub->handleIncomingData($deviceIdentifier, $request->all());
    
    return response()->json(['status' => 'processed']);
});
```

### Мониторинг в реальном времени

Livewire компоненты обеспечивают реал-тайм мониторинг:

```blade
<!-- В Blade шаблоне -->
<livewire:restaurant::iot-device-monitor />
<livewire:restaurant::iot-temperature-dashboard />
```

### Интеграция с KDS

Автоматический запуск таймеров при получении заказа:

```php
use Modules\Restaurant\Application\Services\KitchenIoTIntegrationService;

$kitchenIoT = app(KitchenIoTIntegrationService::class);

// При отправке заказа на кухню
$kitchenIoT->startSmartTimerOnOrder($orderStatus);
$kitchenIoT->printOrderOnIoT($orderStatus);
```

---

## Мониторинг и алерты

### Настройка правил оповещений

```php
use Modules\Restaurant\Infrastructure\Models\IoTAlertRuleModel;

IoTAlertRuleModel::create([
    'tenant_id' => 1,
    'iot_device_id' => $deviceId,
    'name' => 'Температура выше нормы',
    'metric_type' => 'temperature',
    'condition' => 'greater_than',
    'threshold_min' => 8.0,
    'severity' => 'critical',
    'is_active' => true,
    'notification_config' => [
        'email' => ['manager@restaurant.com'],
        'sms' => true,
    ],
]);
```

### Проверка безопасности кухни

```php
$safety = $kitchenIoT->checkKitchenSafety($kitchenStationId);

if (!$safety['safe']) {
    foreach ($safety['issues'] as $issue) {
        Log::warning('Safety issue detected', $issue);
    }
}
```

---

## API

### Регистрация устройства

```bash
POST /api/v1/iot/devices
Content-Type: application/json

{
  "device_identifier": "sonoff-001",
  "name": "Холодильник №1",
  "type": "temperature_sensor",
  "protocol": "mqtt",
  "kitchen_station_id": 1,
  "broker_url": "mqtt://localhost:1883",
  "topic_prefix": "sonoff/th16"
}
```

### Получение статуса устройства

```bash
GET /api/v1/iot/devices/{id}/status
```

### Отправка команды

```bash
POST /api/v1/iot/devices/{id}/command
Content-Type: application/json

{
  "command": "start_timer",
  "payload": {
    "duration_minutes": 15
  }
}
```

---

## Troubleshooting

### Устройство не появляется онлайн

1. Проверьте MQTT broker:
```bash
mosquitto_sub -h localhost -t "#" -v
```

2. Проверьте логи Laravel:
```bash
tail -f storage/logs/laravel.log | grep -i iot
```

3. Убедитесь, что устройство публикует в правильный топик

### Телеметрия не сохраняется

1. Проверьте формат данных — должен быть JSON
2. Убедитесь, что device_identifier совпадает
3. Проверьте очереди: `php artisan queue:work`

### WebSocket не обновляется

1. Проверьте конфигурацию broadcasting
2. Убедитесь, что Laravel Echo Server запущен
3. Проверьте права на каналы в `routes/channels.php`

---

## Безопасность

### Рекомендации

1. **Изолируйте IoT сеть** — используйте отдельную VLAN для кухонного оборудования
2. **VPN для удалённого доступа** — не открывайте порты MQTT в интернет
3. **TLS для MQTT** — используйте MQTTS для публичных брокеров
4. **Сертификаты устройств** — используйте client certificates для авторизации
5. **Регулярное обновление прошивки** — обновляйте Tasmota и прошивки устройств

### Аутентификация

Все устройства авторизуются через:
- MQTT username/password
- Device unique identifier
- Tenant isolation (global scope)

---

## Производительность

### Оптимизация

- **Redis для кэширования** — телеметрия кэшируется на 5 минут
- **Очистка старых данных** — автоматическое удаление данных старше 90 дней
- **Асинхронная обработка** — все операции в очередях
- **ClickHouse для аналитики** — рассмотрите миграцию для high-volume телеметрии

### Мониторинг

Используйте Prometheus метрики:

- `iot_devices_online_total` — количество онлайн устройств
- `iot_telemetry_received_total` — полученных записей телеметрии
- `iot_alerts_triggered_total` — триггернутых алертов

---

## Roadmap

### Q2 2026

- [ ] Компьютерное зрение для распознавания готовности блюд
- [ ] AI-предсказание времени приготовления на основе исторических данных
- [ ] Интеграция с 1С:Общепит через HTTP API
- [ ] Мобильное приложение для поваров (React Native)

### Q3 2026

- [ ] Роботы-повара (интеграция с Moley Robotics)
- [ ] Автоматические линии сборки
- [ ] IoT меню на столах (QR + NFC)
- [ ] Умные замки с биометрией

---

## Поддержка

**Документация:** `modules/Restaurant/IOT_INTEGRATION_README.md`  
**Issues:** GitHub Issues (nanolord126/CatVRF)  
**Email:** support@catvrf.ru

---

## Лицензия

CatVRF Restaurant Module — часть проекта CatVRF.  
© 2026 CatVRF Team. Все права защищены.
