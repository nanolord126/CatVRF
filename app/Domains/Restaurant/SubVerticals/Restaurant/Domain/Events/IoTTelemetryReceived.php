<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Restaurant\Domain\Entities\IoTDevice;

final readonly class IoTTelemetryReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public IoTDevice $device,
        public array $telemetryData,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('iot-dashboard');
    }

    public function broadcastAs(): string
    {
        return 'TelemetryReceived';
    }
}
