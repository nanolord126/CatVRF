<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Events;

use Modules\Restaurant\Domain\Entities\IoTDevice;
use Modules\Restaurant\Domain\Entities\IoTTelemetry;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class IoTAlertTriggered
{
    use Dispatchable;

    public function __construct(
        public IoTDevice $device,
        public IoTTelemetry $telemetry,
        public string $alertMessage,
    ) {}
}
