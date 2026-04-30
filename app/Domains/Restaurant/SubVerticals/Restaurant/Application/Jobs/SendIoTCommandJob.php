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

final class SendIoTCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 15;

    public function __construct(
        private readonly int $deviceId,
        private readonly string $command,
        private readonly array $payload = [],
    ) {}

    public function handle(IoTHubService $iotHub): void
    {
        try {
            $success = $iotHub->sendCommand($this->deviceId, $this->command, $this->payload);
            
            if (!$success) {
                Log::warning('IoT command failed', [
                    'device_id' => $this->deviceId,
                    'command' => $this->command,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send IoT command', [
                'device_id' => $this->deviceId,
                'command' => $this->command,
                'error' => $e->getMessage(),
            ]);
            
            $this->release(60); // Retry after 1 minute
        }
    }
}
