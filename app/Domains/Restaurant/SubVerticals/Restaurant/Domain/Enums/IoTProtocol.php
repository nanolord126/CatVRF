<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Enums;

enum IoTProtocol: string
{
    case MQTT = 'mqtt';
    case WEBSOCKET = 'websocket';
    case MODBUS_TCP = 'modbus_tcp';
    case MODBUS_RTU = 'modbus_rtu';
    case HTTP = 'http';
    case COAP = 'coap';

    public function label(): string
    {
        return match ($this) {
            self::MQTT => 'MQTT',
            self::WEBSOCKET => 'WebSocket',
            self::MODBUS_TCP => 'Modbus TCP',
            self::MODBUS_RTU => 'Modbus RTU',
            self::HTTP => 'HTTP/REST',
            self::COAP => 'CoAP',
        };
    }

    public function isRealtime(): bool
    {
        return match ($this) {
            self::MQTT, self::WEBSOCKET, self::COAP => true,
            self::MODBUS_TCP, self::MODBUS_RTU, self::HTTP => false,
        };
    }

    public function defaultPort(): int
    {
        return match ($this) {
            self::MQTT => 1883,
            self::WEBSOCKET => 80, // or 443 for wss
            self::MODBUS_TCP => 502,
            self::MODBUS_RTU => -1, // Serial, no port
            self::HTTP => 80,
            self::COAP => 5683,
        };
    }
}
