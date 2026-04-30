# IoT Integration for Restaurant Vertical

## Overview

This module provides full IoT integration for the CatVRF Restaurant vertical, enabling real-time communication with kitchen equipment including temperature sensors, smart scales, KDS displays, and more.

## Features

- **Multi-protocol support**: MQTT, WebSocket, Modbus TCP, HTTP
- **Real-time telemetry**: Temperature, weight, humidity monitoring
- **Alert system**: Configurable thresholds with automatic notifications
- **Device management**: Filament admin interface for device registration
- **Live monitoring**: Livewire component for real-time dashboards
- **KDS integration**: Automatic order-to-kitchen workflows

## Installation

### 1. Install Dependencies

```bash
composer require php-mqtt/client
```

### 2. Configure Environment

Copy IoT environment variables:
```bash
# Add to your .env file
MQTT_HOST=127.0.0.1
MQTT_PORT=1883
MQTT_USERNAME=
MQTT_PASSWORD=
IOT_TEMP_WARNING=8.0
IOT_TEMP_CRITICAL=15.0
```

### 3. Run Migrations

```bash
php artisan migrate --path=modules/Restaurant/Database/Migrations
```

## Quick Start

### Register a Device

```php
use Modules\Restaurant\Application\Services\IoTHubService;
use Modules\Restaurant\Domain\Enums\IoTDeviceType;
use Modules\Restaurant\Domain\Enums\IoTProtocol;

$iotHub = app(IoTHubService::class);

$device = $iotHub->registerDevice(
    tenantId: 1,
    deviceIdentifier: 'FRIDGE_TEMP_001',
    name: 'Main Refrigerator Sensor',
    type: IoTDeviceType::TEMPERATURE_SENSOR,
    protocol: IoTProtocol::MQTT,
    kitchenStationId: 1,
    brokerUrl: 'mqtt://localhost:1883',
    topicPrefix: 'iot/restaurant1/fridge1'
);
```

### Send Command to Device

```php
$iotHub->sendCommand($deviceId, 'start_timer', [
    'duration_minutes' => 15,
    'order_id' => 12345,
]);
```

### Handle Incoming Telemetry

Telemetry is automatically processed via MQTT/WebSocket. Payload format:
```json
{
  "temperature": 4.5,
  "humidity": 65,
  "timestamp": "2026-04-23T10:30:00Z"
}
```

## File Structure

```
modules/Restaurant/
├── Application/Services/
│   ├── IoTHubService.php           # Main IoT hub service
│   ├── MqttClientService.php       # MQTT client implementation
│   ├── ModbusClientService.php     # Modbus TCP client
│   └── KitchenIoTIntegrationService.php
├── Domain/
│   ├── Entities/
│   │   ├── IoTDevice.php
│   │   └── IoTTelemetry.php
│   ├── Enums/
│   │   ├── IoTDeviceType.php
│   │   └── IoTProtocol.php
│   ├── Events/
│   │   └── IoTAlertTriggered.php
│   └── Repositories/
│       ├── IoTDeviceRepositoryInterface.php
│       └── IoTTelemetryRepositoryInterface.php
├── Infrastructure/
│   ├── Models/
│   │   ├── IoTDeviceModel.php
│   │   └── IoTTelemetryModel.php
│   └── Repositories/
│       ├── EloquentIoTDeviceRepository.php
│       └── EloquentIoTTelemetryRepository.php
├── Presentation/
│   ├── Resources/
│   │   └── IoTDeviceResource.php   # Filament admin interface
│   └── Http/Livewire/
│       └── IoTRealTimeMonitor.php  # Real-time monitoring component
└── Database/Migrations/
    ├── 2026_04_23_000010_create_iot_devices_table.php
    ├── 2026_04_23_000011_create_iot_telemetry_table.php
    └── 2026_04_23_000012_create_iot_alert_rules_table.php
```

## Supported Device Types

- `TEMPERATURE_SENSOR` - Temperature monitoring (refrigerators, freezers)
- `WEIGHT_SCALE` - Smart scales for ingredient verification
- `SMART_TIMER` - Kitchen timers with automatic start
- `DOOR_SENSOR` - Door open/close monitoring
- `SMART_PRINTER` - IoT printers for order tickets
- `KDS_DISPLAY` - Kitchen Display System screens
- `SMART_STOVE` - Smart cooking surfaces
- `CAMERA_VISION` - Computer vision cameras
- `SMART_LOCK` - Smart locks for storage
- `OIL_LEVEL_SENSOR` - Fryer oil level monitoring
- `HUMIDITY_SENSOR` - Humidity monitoring

## Alert Configuration

Default thresholds:
- Temperature warning: > 8°C
- Temperature critical: > 15°C
- Weight minimum: < 0.01kg

Configure in `.env`:
```env
IOT_TEMP_WARNING=8.0
IOT_TEMP_CRITICAL=15.0
IOT_WEIGHT_MIN=0.01
```

## Integration with KDS

Automatic order-to-kitchen flow:
1. Order created → Event dispatched
2. KitchenIoTIntegrationService listens
3. Smart timer starts automatically
4. Order prints on IoT printer
5. KDS display updates via WebSocket

## Testing

Run IoT tests:
```bash
php artisan test --filter=IoT
```

## Documentation

Full setup guide: `docs/RESTAURANT_IOT_SETUP.md`

## Known Issues

- **IoTHubService**: Requires manual fix due to edit conflicts (see TODO item #11)
- **MQTT Client**: Requires `php-mqtt/client` package installation

## Requirements

- PHP 8.3+
- Laravel 11+
- MQTT Broker (Mosquitto/EMQX/Cloud)
- Redis (for WebSocket and caching)
- ClickHouse (optional, for high-volume telemetry)

## License

Proprietary - CatVRF Project
