<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Restaurant\Domain\Entities\IoTDevice;

final readonly class IoTDeviceAlertTriggered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public IoTDevice $device,
        public string $alertMessage,
        public array $telemetryData,
    ) {}
}
