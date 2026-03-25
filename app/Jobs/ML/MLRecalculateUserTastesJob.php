<?php

declare(strict_types=1);

namespace App\Jobs\ML;

use App\Models\User;
use App\Services\ML\TasteMLService;
use App\Services\ML\UserTasteProfileService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Ежедневный Job для пересчёта всех ML-профилей вкусов пользователей
 * Вычисляет новые embeddings и категорийные скоры
 * CANON 2026: Запускается каждый день в 03:00 UTC
 */
final class MLRecalculateUserTastesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 3600;
    public int $tries = 3;

    public function __construct(
        private readonly int $batchSize = 100,
    ) {}

    /**
     * Выполнить job
     */
    public function handle(UserTasteProfileService $profileService, TasteMLService $mlService): void
    {
        try {
            Log::channel('audit')->info('Starting daily user taste profiles recalculation');

            // Получить всех активных пользователей постранично
            User::whereActive(true)->chunk($this->batchSize, function ($users) use ($profileService, $mlService) {
                foreach ($users as $user) {
                    try {
                        $this->recalculateUserTaste($user->id, $user->tenant_id, $profileService, $mlService);
                    } catch (Throwable $e) {
                        Log::channel('audit')->error('Failed to recalculate user taste', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

            Log::channel('audit')->info('Daily user taste profiles recalculation completed successfully');
        } catch (Throwable $e) {
            Log::channel('audit')->error('Daily user taste recalculation job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Пересчитать вкусы для одного пользователя
     */
    private function recalculateUserTaste(
        int $userId,
        int $tenantId,
        UserTasteProfileService $profileService,
        TasteMLService $mlService
    ): void {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        // Получить профиль
        $profile = $profileService->getOrCreateProfile($userId, $tenantId, $correlationId);

        // Если профиль не готов к анализу (слишком мало данных), пропустить
        if ($profile->getTotalInteractions() < 3 && !$profile->isColdStart()) {
            return;
        }

        // Вычислить ML-скоры категорий
        $categoryScores = $mlService->calculateCategoryScores($userId, $tenantId);

        // Вычислить behavioral метрики
        $behavioralMetrics = $mlService->calculateBehavioralMetrics($userId, $tenantId);

        // Генерировать embeddings (основной + категорийные)
        $embeddings = $mlService->generateEmbeddings($profile, $categoryScores);

        // Обновить профиль
        $profileService->updateImplicitScores(
            $userId,
            $tenantId,
            $categoryScores,
            $behavioralMetrics,
            $embeddings,
            $correlationId
        );

        Log::channel('audit')->debug('User taste profile recalculated', [
            'user_id' => $userId,
            'data_quality_score' => $profile->refresh()->getDataQualityScore(),
            'correlation_id' => $correlationId,
        ]);
    }
}
