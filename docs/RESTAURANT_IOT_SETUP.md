# Restaurant IoT Integration Setup Guide

## Overview

This guide describes how to set up and configure IoT device integration for the CatVRF Restaurant vertical. The system supports MQTT, WebSocket, and Modbus TCP protocols for real-time communication with kitchen equipment.

## Supported IoT Devices

### High Priority (Must-Have)
- **Smart Kitchen Timers and Displays** (KDS + IoT timers)
- **Temperature Sensors** (refrigerators, freezers, display cases with alarms)
- **Smart Scales** (automatic ingredient weighing during assembly)
- **IoT Printers and KDS Displays** (MQTT/WebSocket support)
- **Door Sensors** (refrigerator and cabinet doors)

### Medium Priority
- **Smart Stoves and Induction Surfaces** (cooking temperature control)
- **Computer Vision Cameras** (automatic dish readiness detection)
- **Smart Locks** (storage rooms and refrigerated cabinets)
- **Waste Bin and Oil Level Sensors**

### Low Priority (Future)
- **Robot Cookers and Automated Lines**
- **IoT Menu Boards** (QR + NFC)

## Installation

### 1. Install Dependencies

```bash
composer require php-mqtt/client
```

### 2. Configure Environment Variables

Copy the IoT environment variables to your `.env` file:

```bash
cp .env.example.iot .env.iot
# Then manually copy the variables to your main .env file
```

Required variables:
```env
MQTT_HOST=127.0.0.1
MQTT_PORT=1883
```

### 3. Run Migrations

```bash
php artisan migrate --path=modules/Restaurant/Database/Migrations
```

This will create:
- `iot_devices` table
- `iot_telemetry` table
- `iot_alert_rules` table

### 4. Configure MQTT Broker

#### Option A: Local Mosquitto (Development)

```bash
# Install Mosquitto
# Ubuntu/Debian
sudo apt-get install mosquitto mosquitto-clients

# Start Mosquitto
sudo systemctl start mosquitto
sudo systemctl enable mosquitto
```

#### Option B: Cloud MQTT (Production)

Use services like:
- AWS IoT Core
- Azure IoT Hub
- EMQX Cloud
- HiveMQ Cloud

Update `.env` with your cloud broker credentials.

## Device Registration

### Via Filament Admin Panel

1. Navigate to **Restaurant → IoT Devices**
2. Click **Create IoT Device**
3. Fill in the required fields:
   - **Device Identifier**: MAC address, serial number, or unique ID
   - **Device Name**: Human-readable name
   - **Device Type**: Select from dropdown (temperature_sensor, weight_scale, etc.)
   - **Protocol**: MQTT, WebSocket, Modbus TCP, etc.
   - **Kitchen Station**: Assign to a kitchen station (optional)
   - **Connection Config**: JSON configuration (protocol-specific)
   - **Broker URL**: MQTT broker URL (for MQTT devices)
   - **Topic Prefix**: MQTT topic prefix (e.g., `iot/device`)

### Via API

```php
use Modules\Restaurant\Application\Services\IoTHubService;
use Modules\Restaurant\Domain\Enums\IoTDeviceType;
use Modules\Restaurant\Domain\Enums\IoTProtocol;

$iotHub = app(IoTHubService::class);

$device = $iotHub->registerDevice(
    tenantId: 1,
    deviceIdentifier: 'AA:BB:CC:DD:EE:FF',
    name: 'Refrigerator Temperature Sensor',
    type: IoTDeviceType::TEMPERATURE_SENSOR,
    protocol: IoTProtocol::MQTT,
    kitchenStationId: 1,
    connectionConfig: json_encode([
        'qos' => 1,
        'retain' => false,
    ]),
    brokerUrl: 'mqtt://localhost:1883',
    topicPrefix: 'iot/restaurant1/fridge1'
);
```

## Device Configuration Examples

### Sonoff/Tuya Smart Switch (HTTP)

```json
{
  "url": "http://192.168.1.100/cm?cmnd=Power",
  "method": "GET",
  "auth_token": "your_token_here"
}
```

### Aqara Temperature Sensor (MQTT)

```json
{
  "qos": 1,
  "retain": true,
  "payload_format": "zigbee2mqtt"
}
```

### Smart Scale (Modbus TCP)

```json
{
  "host": "192.168.1.50",
  "port": 502,
  "slave_id": 1,
  "register_address": 40001,
  "register_count": 2
}
```

### KDS Display (WebSocket)

```json
{
  "websocket_url": "ws://localhost:8080",
  "authentication": {
    "type": "token",
    "token": "your_jwt_token"
  }
}
```

## Sending Commands to Devices

### Start Smart Timer on Order

```php
$iotHub->sendCommand($deviceId, 'start_timer', [
    'duration_minutes' => 15,
    'order_id' => 12345,
]);
```

### Print Order on IoT Printer

```php
$iotHub->sendCommand($deviceId, 'print_order', [
    'order_id' => 12345,
    'items' => [
        ['name' => 'Burger', 'quantity' => 2],
        ['name' => 'Fries', 'quantity' => 1],
    ],
    'priority' => 'normal',
]);
```

### Read Modbus Register

```php
$iotHub->sendCommand($deviceId, 'read_register', [
    'address' => 40001,
    'quantity' => 2,
]);
```

## Receiving Telemetry Data

### MQTT Subscription

The system automatically subscribes to device topics based on the configured topic prefix.

Expected payload format:
```json
{
  "temperature": 4.5,
  "humidity": 65,
  "timestamp": "2026-04-23T10:30:00Z"
}
```

### HTTP Webhook

Configure your device to POST data to:
```
POST /api/restaurant/iot/telemetry/{device_identifier}
Content-Type: application/json

{
  "metric_type": "temperature",
  "value": 4.5,
  "unit": "celsius"
}
```

## Real-Time Monitoring

### Livewire Components

Access real-time monitoring dashboards:

1. **Temperature Dashboard**: `route('restaurant.iot.temperature-dashboard')`
2. **Device Monitor**: `route('restaurant.iot.device-monitor')`

### WebSocket Channels

Subscribe to device updates via WebSocket:

```javascript
const channel = `iot.device.${deviceIdentifier}`;
Echo.channel(channel).listen('IoTTelemetryReceived', (e) => {
    console.log('New telemetry:', e.telemetry);
});
```

## Alert Configuration

### Create Alert Rules via Filament

1. Navigate to **Restaurant → IoT Alert Rules**
2. Click **Create Alert Rule**
3. Configure:
   - **Device**: Assign to specific device or leave blank for all devices of type
   - **Metric Type**: temperature, weight, humidity, etc.
   - **Condition**: greater_than, less_than, equals, between
   - **Threshold Values**: Set threshold(s)
   - **Alert Level**: info, warning, critical
   - **Actions**: Notify manager, Block operations

### Default Temperature Alerts

- **Warning**: Temperature > 8°C
- **Critical**: Temperature > 15°C

These thresholds can be customized in `.env`:
```env
IOT_TEMP_WARNING=8.0
IOT_TEMP_CRITICAL=15.0
```

## Integration with Kitchen Display System (KDS)

### Automatic Order to Kitchen Flow

1. Order created → Event dispatched
2. KitchenIoTIntegrationService listens
3. Automatically starts smart timer on assigned station
4. Prints order on IoT printer
5. Updates KDS display via WebSocket

### Example Listener

```php
class SendOrderToKitchen
{
    public function handle(OrderCreated $event)
    {
        $iotService = app(KitchenIoTIntegrationService::class);
        
        // Start timer
        $iotService->startSmartTimerOnOrder($orderStatus);
        
        // Print order
        $iotService->printOrderOnIoT($orderStatus);
    }
}
```

## Troubleshooting

### Device Not Showing Online

1. Check MQTT broker is running: `systemctl status mosquitto`
2. Verify device can reach broker (network connectivity)
3. Check topic prefix matches device configuration
4. Review logs: `tail -f storage/logs/laravel.log | grep IoT`

### Telemetry Not Reaching Server

1. Verify device is publishing to correct topic
2. Check MQTT credentials in device configuration
3. Ensure firewall allows MQTT port (default 1883)
4. Check device logs for connection errors

### Modbus Connection Fails

1. Verify device IP and port are correct
2. Check Modbus slave ID matches device configuration
3. Ensure device supports Modbus TCP (not RTU only)
4. Test with external Modbus client tool

### High Memory Usage

If processing high-volume telemetry:

1. Enable ClickHouse for long-term storage:
   ```env
   IOT_CLICKHOUSE_ENABLED=true
   ```
2. Reduce telemetry retention period:
   ```env
   IOT_TELEMETRY_RETENTION_DAYS=30
   ```
3. Implement data aggregation in ClickHouse

## Security Best Practices

1. **Use TLS for MQTT in production**:
   ```env
   MQTT_USE_TLS=true
   MQTT_PORT=8883
   ```

2. **Implement device authentication**:
   - Use MQTT username/password
   - Implement client certificates for production
   - Rotate credentials regularly

3. **Network isolation**:
   - Keep IoT devices on separate VLAN
   - Use VPN for remote device access
   - Implement firewall rules

4. **Data anonymization**:
   - Never send PII in telemetry
   - Anonymize device identifiers in logs
   - Comply with 152-ФЗ and medical data regulations

## Performance Optimization

### Redis Caching

Telemetry data is cached with 5-minute TTL. Adjust in `IoTHubService`:
```php
private const CACHE_TTL = 300; // 5 minutes
```

### ClickHouse Integration

For high-volume telemetry (1000+ devices), enable ClickHouse:

1. Install ClickHouse
2. Configure connection in config
3. Run ClickHouse migrations
4. Enable in `.env`:
   ```env
   IOT_CLICKHOUSE_ENABLED=true
   ```

### Queue Processing

Telemetry processing is automatically queued. Configure queue worker:

```bash
php artisan queue:work --queue=iot,high,default
```

## Testing

Run IoT integration tests:

```bash
php artisan test --filter=IoT
```

Test MQTT connection:

```bash
# Subscribe to test topic
mosquitto_sub -h localhost -t "iot/test/#" -v

# Publish test message
mosquitto_pub -h localhost -t "iot/test/sensor" -m '{"temperature": 5.5}'
```

## Compatibility Checklist

### 2026 IoT Solutions Compatibility

| Solution | Protocol | Status | Notes |
|----------|----------|--------|-------|
| Sonoff Basic | HTTP | ✅ Supported | Requires HTTP endpoint config |
| Tuya Smart | HTTP/MQTT | ✅ Supported | Use Tuya MQTT bridge |
| Aqara Zigbee2MQTT | MQTT | ✅ Supported | Standard MQTT payload |
| Shelly Plus | MQTT/HTTP | ✅ Supported | Native MQTT support |
| ESPHome | MQTT | ✅ Supported | Custom payload format |
| Home Assistant | MQTT | ✅ Supported | Via MQTT integration |
| Modbus Industrial | Modbus TCP | ✅ Supported | Industrial equipment |
| KDS Displays | WebSocket | ✅ Supported | Real-time updates |
| Smart Scales | Modbus/HTTP | ✅ Supported | Depends on manufacturer |

## Support

For issues or questions:
1. Check logs in `storage/logs/laravel.log`
2. Review this documentation
3. Check device manufacturer specifications
4. Contact CatVRF support team

## Changelog

### v1.0.0 (2026-04-23)
- Initial IoT integration release
- MQTT, WebSocket, Modbus TCP support
- Temperature, weight, humidity sensors
- Alert system with configurable rules
- Real-time monitoring dashboards
- Filament admin interface
- ClickHouse integration support
