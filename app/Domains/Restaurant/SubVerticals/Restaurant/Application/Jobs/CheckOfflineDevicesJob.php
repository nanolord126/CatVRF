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

final class CheckOfflineDevicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public function __construct() {}

    public function handle(IoTHubService $iotHub): void
    {
        try {
            $offlineDevices = $iotHub->checkOfflineDevices();
            
            if (!empty($offlineDevices)) {
                Log::info('Offline devices detected', [
                    'count' => count($offlineDevices),
                    'devices' => array_map(fn ($d) => $d->deviceIdentifier, $offlineDevices),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to check offline devices', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
