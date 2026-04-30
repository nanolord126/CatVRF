<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Restaurant\Application\Services\IoTHubService;

final class ProcessIoTTelemetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        private readonly string $deviceIdentifier,
        private readonly array $payload,
    ) {}

    public function handle(IoTHubService $iotHub): void
    {
        try {
            $iotHub->handleIncomingData($this->deviceIdentifier, $this->payload);
        } catch (\Exception $e) {
            Log::error('Failed to process IoT telemetry', [
                'device_identifier' => $this->deviceIdentifier,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->release(30); // Retry after 30 seconds
        }
    }
}
