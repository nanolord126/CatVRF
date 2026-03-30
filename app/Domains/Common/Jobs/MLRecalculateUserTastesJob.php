<?php declare(strict_types=1);

namespace App\Domains\Common\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MLRecalculateUserTastesJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use InteractsWithQueue;
        use Queueable;
        use SerializesModels;

        public int $tries = 3;

        public int $timeout = 600; // 10 минут на всё

        public string $queue = 'default';

        public function __construct(
            private readonly TasteMLService $mlService,
        ) {}

        public function handle(): void
        {
            try {
                $correlationId = Str::uuid()->toString();

                Log::channel('audit')->info('MLRecalculateUserTastesJob started', [
                    'correlation_id' => $correlationId,
                ]);

                // 1. Получить всех пользователей с активными профилями вкусов
                $profiles = UserTasteProfile::where('is_enabled', true)
                    ->where('interaction_count', '>', 0) // Только если были взаимодействия
                    ->orderBy('last_calculated_at') // Сначала давно не обновлённые
                    ->limit(1000) // Максимум 1000 пользователей в один день
                    ->get(['user_id', 'tenant_id', 'interaction_count']);

                $processed = 0;
                $successful = 0;
                $failed = 0;

                foreach ($profiles as $profile) {
                    try {
                        // 2. Пересчитать embedding для каждого пользователя
                        $result = $this->mlService->recalculateProfileEmbedding(
                            $profile->user_id,
                            $profile->tenant_id,
                            $correlationId,
                        );

                        if ($result) {
                            $successful++;
                        } else {
                            $failed++;
                        }

                        $processed++;
                    } catch (\Throwable $e) {
                        $failed++;

                        Log::channel('audit')->error('Failed to recalculate single user taste', [
                            'user_id' => $profile->user_id,
                            'error' => $e->getMessage(),
                            'correlation_id' => $correlationId,
                        ]);
                    }

                    // Каждые 100 профилей выводим статус
                    if ($processed % 100 === 0) {
                        Log::channel('audit')->info('MLRecalculateUserTastesJob progress', [
                            'processed' => $processed,
                            'successful' => $successful,
                            'failed' => $failed,
                            'correlation_id' => $correlationId,
                        ]);
                    }
                }

                // 3. Итоговый отчёт
                Log::channel('audit')->info('MLRecalculateUserTastesJob completed', [
                    'processed' => $processed,
                    'successful' => $successful,
                    'failed' => $failed,
                    'execution_time' => now()->diffInSeconds(now()),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('MLRecalculateUserTastesJob failed', [
                    'error' => $e->getMessage(),
                    'exception' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        /**
         * Обработка ошибки при критическом сбое job
         */
        public function failed(\Throwable $exception): void
        {
            Log::channel('audit')->critical('MLRecalculateUserTastesJob failed permanently', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }
}
