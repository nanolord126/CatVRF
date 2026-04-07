<?php declare(strict_types=1);

namespace App\Jobs;

use App\Services\ML\UserTasteAnalyzerService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;


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
 */
final class MLRecalculateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries  = 3;
    public int $backoff = 30; // секунд между попытками

    public function __construct(
        private readonly int  $userId,
        private readonly bool $isNewUser,
        private readonly LogManager $logger,
    ) {}

    public function handle(UserTasteAnalyzerService $tasteAnalyzer): void
    {
        // Новых пользователей пропускаем — нет истории для аггрегации
        if ($this->isNewUser) {
            return;
        }

        $user = User::find($this->userId);

        if ($user === null) {
            $this->logger->channel('audit')->warning('MLRecalculateJob: user not found', [
                'user_id' => $this->userId,
            ]);
            return;
        }

        $tasteAnalyzer->analyzeAndSaveUserProfile($user);

        $this->logger->channel('audit')->info('MLRecalculateJob completed', [
            'user_id'  => $this->userId,
            'user_type' => 'returning',
        ]);
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
