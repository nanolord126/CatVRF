<?php

declare(strict_types=1);

namespace App\Domains\Referral\Listeners;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\Referral\Events\TurnoverReachedEvent;
use App\Domains\Referral\Services\ReferralService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
/**
 * Категорический асинхронный слушатель (Listener) события достижения оборота.
 *
 * Безусловно обращается к ReferralService для инициации процесса начисления бонуса,
 * не блокируя основной HTTP-отклик транзакции клиента.
 */
final class AwardBonusOnTurnoverListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Внедрение зависимостей для обработки логики.
     */
    public function __construct(
        private readonly ReferralService $referralService, private readonly Request $request, private readonly LoggerInterface $logger
    ) {

    }

    /**
     * Обработка исключительно важного события начисления награды.
     *
     * @param TurnoverReachedEvent $event Объект события.
     */
    public function handle(TurnoverReachedEvent $event): void
    {
        $this->logger->info('Запуск асинхронной задачи начисления реферального бонуса (Listener)', [
            'referral_id' => $event->referralId,
            'recipient_id' => $event->recipientId,
            'correlation_id' => $event->correlationId,
        ]);

        $success = $this->referralService->awardBonus(
            referralId: $event->referralId,
            recipientId: $event->recipientId,
            correlationId: $event->correlationId
        );

        if (!$success) {
            $this->logger->error('Критическая ошибка зачисления бонусного вознаграждения в фоновом режиме', [
                'referral_id' => $event->referralId,
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);
            
            // В идеальном мире здесь генерируется Exception для логики retry (повторных попыток) Queue Job.
        }
    }
}
