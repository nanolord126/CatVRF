<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\Enums;

enum FeatureStoreStatus: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';
    case STALE = 'stale';
    case ERROR = 'error';
}

enum ModelServingMode: string
{
    case LIVE = 'live';
    case SHADOW = 'shadow';
    case CANARY = 'canary';
    case RAMP = 'ramp';
}

enum DriftStatus: string
{
    case HEALTHY = 'healthy';
    case WARNING = 'warning';
    case CRITICAL = 'critical';
    case UNKNOWN = 'unknown';

    public static function fromPsiAndAccuracy(float $psi, float $accuracyDrop): self
    {
        if ($psi > 0.25 || $accuracyDrop > 0.10) {
            return self::CRITICAL;
        }
        if ($psi > 0.10 || $accuracyDrop > 0.05) {
            return self::WARNING;
        }
        return self::HEALTHY;
    }
}
