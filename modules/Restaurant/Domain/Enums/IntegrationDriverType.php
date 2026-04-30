<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Enums;

enum IntegrationDriverType: string
{
    // Уровень 1 - Прямые аппаратные интеграции (внутренние)
    case KDS_DISPLAY = 'kds_display';      // Внутренний KDS через WebSocket
    case KITCHEN_PRINTER = 'kitchen_printer'; // Аппаратные принтеры (ESC/POS)
    case SIGNAL_SYSTEM = 'signal_system';    // Звуковая/световая сигнализация

    // Уровень 2 - Внутренние протоколы
    case MQTT = 'mqtt';                     // MQTT для планшетов поваров
    case WEBSOCKET = 'websocket';           // WebSocket для браузерных клиентов

    // Уровень 3 - Лёгкая интеграция
    case BROWSER_PRINT = 'browser_print';   // Печать через браузер
    case GENERIC_HTTP = 'generic_http';     // Универсальный HTTP драйвер для кастомных интеграций

    public function level(): int
    {
        return match ($this) {
            self::KDS_DISPLAY,
            self::KITCHEN_PRINTER,
            self::SIGNAL_SYSTEM => 1,

            self::MQTT,
            self::WEBSOCKET => 2,

            self::BROWSER_PRINT,
            self::GENERIC_HTTP => 3,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::KDS_DISPLAY => 'KDS Display (WebSocket)',
            self::KITCHEN_PRINTER => 'Kitchen Printer',
            self::SIGNAL_SYSTEM => 'Signal System',
            self::MQTT => 'MQTT (Tablets)',
            self::WEBSOCKET => 'WebSocket (Browsers)',
            self::BROWSER_PRINT => 'Browser Print',
            self::GENERIC_HTTP => 'Generic HTTP API',
        };
    }

    public function isHardware(): bool
    {
        return in_array($this, [self::KITCHEN_PRINTER, self::SIGNAL_SYSTEM], true);
    }

    public function isInternal(): bool
    {
        return true; // Все драйверы теперь внутренние
    }
}
