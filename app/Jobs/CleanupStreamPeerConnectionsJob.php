<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CleanupStreamPeerConnectionsJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
