<?php

declare(strict_types=1);


namespace App\Jobs;

use App\Services\MeshService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final /**
 * CleanupStreamPeerConnectionsJob
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CleanupStreamPeerConnectionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;
    public int $tries = 3;
    public int $maxExceptions = 1;

    public function __construct(
        private readonly int $olderThanMinutes = 60,
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}

    public function handle(MeshService $meshService): void
    {
        try {
            $deleted = $meshService->cleanupClosedConnections($this->olderThanMinutes);

            Log::channel('audit')->info(
                'Stream peer connections cleanup completed',
                ['deleted' => $deleted, 'older_than_minutes' => $this->olderThanMinutes]
            );
        } catch (\Exception $e) {
            Log::channel('error')->error(
                'Stream peer connections cleanup failed',
                ['error' => $e->getMessage()]
            );

            throw $e;
        }
    }
}
