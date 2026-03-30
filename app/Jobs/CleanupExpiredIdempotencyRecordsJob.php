<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CleanupExpiredIdempotencyRecordsJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public int $tries = 3;
        public int $timeout = 300;  // 5 минут

        /**
         * Выполнить job.
         *
         * @param IdempotencyService $service
         * @return void
         */
        public function handle(IdempotencyService $service): void
        {
            try {
                $deletedCount = $service->cleanup();

                Log::channel('audit')->info('Idempotency cleanup job completed', [
                    'deleted_records' => $deletedCount,
                    'job_id' => $this->job->getJobId(),
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Idempotency cleanup job failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }
}
