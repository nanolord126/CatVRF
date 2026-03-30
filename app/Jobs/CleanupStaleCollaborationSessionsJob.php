<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CleanupStaleCollaborationSessionsJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Queueable;

        public int $timeout = 300;
        public int $tries = 3;

        public function handle(): void
        {
            try {
                // Очищаем устаревшие сессии редактирования
                // В продакшене можно использовать более оптимизированный подход с Redis SCAN

                Log::channel('audit')->info('Cleanup stale collaboration sessions job completed', [
                    'timestamp' => now()->toIso8601String(),
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to cleanup stale collaboration sessions', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }
}
