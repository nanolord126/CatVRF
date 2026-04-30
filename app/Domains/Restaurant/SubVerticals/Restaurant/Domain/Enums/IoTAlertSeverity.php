<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Enums;

enum IoTAlertSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case CRITICAL = 'critical';
    case EMERGENCY = 'emergency';

    public function label(): string
    {
        return match ($this) {
            self::INFO => 'Информация',
            self::WARNING => 'Предупреждение',
            self::CRITICAL => 'Критично',
            self::EMERGENCY => 'Чрезвычайная ситуация',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INFO => 'blue',
            self::WARNING => 'yellow',
            self::CRITICAL => 'orange',
            self::EMERGENCY => 'red',
        };
    }

    public function notifyImmediately(): bool
    {
        return match ($this) {
            self::INFO => false,
            self::WARNING => false,
            self::CRITICAL => true,
            self::EMERGENCY => true,
        };
    }

    public function requiresManagerApproval(): bool
    {
        return match ($this) {
            self::INFO, self::WARNING => false,
            self::CRITICAL, self::EMERGENCY => true,
        };
    }
}
