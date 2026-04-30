<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Enums;

enum IoTDeviceType: string
{
    case TEMPERATURE_SENSOR = 'temperature_sensor';
    case WEIGHT_SCALE = 'weight_scale';
    case SMART_TIMER = 'smart_timer';
    case DOOR_SENSOR = 'door_sensor';
    case SMART_PRINTER = 'smart_printer';
    case KDS_DISPLAY = 'kds_display';
    case SMART_STOVE = 'smart_stove';
    case CAMERA_VISION = 'camera_vision';
    case SMART_LOCK = 'smart_lock';
    case OIL_LEVEL_SENSOR = 'oil_level_sensor';
    case WASTE_BIN_SENSOR = 'waste_bin_sensor';
    case HUMIDITY_SENSOR = 'humidity_sensor';
    case GENERIC = 'generic';

    public function label(): string
    {
        return match ($this) {
            self::TEMPERATURE_SENSOR => 'Датчик температуры',
            self::WEIGHT_SCALE => 'Умные весы',
            self::SMART_TIMER => 'Умный таймер',
            self::DOOR_SENSOR => 'Датчик открытия двери',
            self::SMART_PRINTER => 'IoT принтер',
            self::KDS_DISPLAY => 'KDS дисплей',
            self::SMART_STOVE => 'Умная плита',
            self::CAMERA_VISION => 'Камера компьютерного зрения',
            self::SMART_LOCK => 'Умный замок',
            self::OIL_LEVEL_SENSOR => 'Датчик уровня масла',
            self::WASTE_BIN_SENSOR => 'Датчик заполненности мусорного бака',
            self::HUMIDITY_SENSOR => 'Датчик влажности',
            self::GENERIC => 'Универсальное устройство',
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::TEMPERATURE_SENSOR,
            self::HUMIDITY_SENSOR => 'sensors',
            self::WEIGHT_SCALE,
            self::OIL_LEVEL_SENSOR,
            self::WASTE_BIN_SENSOR => 'measurement',
            self::SMART_TIMER,
            self::DOOR_SENSOR,
            self::SMART_LOCK => 'control',
            self::SMART_PRINTER,
            self::KDS_DISPLAY => 'display',
            self::SMART_STOVE,
            self::CAMERA_VISION => 'advanced',
            self::GENERIC => 'generic',
        };
    }

    public function priority(): int
    {
        return match ($this) {
            self::TEMPERATURE_SENSOR,
            self::DOOR_SENSOR => 1, // High priority - critical for food safety
            self::WEIGHT_SCALE,
            self::SMART_TIMER => 2, // Medium-high priority
            self::KDS_DISPLAY,
            self::SMART_PRINTER => 2, // Core kitchen operations
            self::HUMIDITY_SENSOR,
            self::OIL_LEVEL_SENSOR => 3, // Medium priority
            self::SMART_STOVE,
            self::CAMERA_VISION => 4, // Advanced features
            self::WASTE_BIN_SENSOR,
            self::SMART_LOCK => 5, // Nice to have
            self::GENERIC => 6,
        };
    }
}
