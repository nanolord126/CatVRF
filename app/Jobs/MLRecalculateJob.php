<?php declare(strict_types=1);

namespace App\Jobs;


use App\Services\ML\UserTasteAnalyzerService;
use App\Domains\FraudML\Services\PrometheusMetricsService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Ramsey\Uuid\Uuid;


/**
 * MLRecalculateJob — онлайн-обновление ML-профиля пользователя.
 *
 * Запускается в 5% поведенческих событий из UserBehaviorAnalyzerService.
 * Ежедневный полный перерасчёт — через Kernel (03:00).
 *
 * Правила:
 *  - Обрабатываем только returning-пользователей (у новых нет истории)
 *  - correlation_id сквозной
 *  - Не блокируем основной поток (queue: 'ml')
 *  - Prometheus metrics for monitoring duration, success, and feature drift
 */
final class MLRecalculateJob implements ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

    public int $tries  = 3;
    public int $backoff = 30; // секунд между попытками

    public function __construct(
        private readonly int  $userId,
        private readonly bool $isNewUser,
        private readonly LogManager $logger,
        private readonly PrometheusMetricsService $prometheus,
    ) {}

    public function handle(UserTasteAnalyzerService $tasteAnalyzer): void
    {
        $correlationId = Uuid::uuid4()->toString();
        $startTime = microtime(true);

        // Новых пользователей пропускаем — нет истории для аггрегации
        if ($this->isNewUser) {
            return;
        }

        $user = User::find($this->userId);

        if ($user === null) {
            $this->logger->channel('audit')->warning('MLRecalculateJob: user not found', [
                'user_id' => $this->userId,
                'correlation_id' => $correlationId,
            ]);

            $this->prometheus->recordRetrainSuccess('failed', $correlationId);
            return;
        }

        try {
            $tasteAnalyzer->analyzeAndSaveUserProfile($user);

            $duration = microtime(true) - $startTime;

            // Record Prometheus metrics
            $this->prometheus->recordRetrainDuration($duration, $correlationId);
            $this->prometheus->recordRetrainSuccess('completed', $correlationId);

            $this->logger->channel('audit')->info('MLRecalculateJob completed', [
                'user_id'  => $this->userId,
                'user_type' => 'returning',
                'duration_seconds' => round($duration, 3),
                'correlation_id' => $correlationId,
            ]);

        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('MLRecalculateJob failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            $this->prometheus->recordRetrainSuccess('failed', $correlationId);
            throw $e;
        }
    }

    /**
     * Ежедневный полный перерасчёт — для Kernel::schedule().
     * Запускается в 03:00, обрабатывает returning-пользователей.
     */
    public static function dispatchFullRecalculation(): void
    {
        User::where('created_at', '<', now()->subDays(7))
            ->whereHas('orders')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    self::dispatch($user->id, false)->onQueue('ml');
                }
            });
    }
}

