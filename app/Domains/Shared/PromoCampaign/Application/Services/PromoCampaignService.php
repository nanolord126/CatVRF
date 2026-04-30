<?php

declare(strict_types=1);

namespace Modules\PromoCampaign\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\PromoCampaign\Domain\Repositories\PromoCampaignRepositoryInterface;
use Modules\PromoCampaign\Application\DTOs\DiscountResult;
use Modules\Fraud\Application\Services\FraudControlService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection;

/**
 * Ключевой оркестрационный сервис Application-слоя, абсолютно и категорически управляющий
 * процессом применения, валидации и учета расходования бюджета промо-кампаний.
 *
 * Исключительно соответствует строгим архитектурным канонам: обязательно содержит DB::transaction,
 * tenant scoping, интеграцию с сервисом антифрода (FraudControlService) и распределенное Redis-кэширование
 * для предотвращения состояния гонки (race conditions) при массовом параллельном применении кодов.
 */
final readonly class PromoCampaignService
{
    use WithAuditLogging;

    /**
     * Строго инициализирует сервис всеми необходимыми и исключительно readonly зависимостями.
     *
     * @param  PromoCampaignRepositoryInterface  $repository  Абстракция доступа к постоянному хранилищу.
     * @param  FraudControlService  $fraudControl  Сервис централизованного пресечения мошеннических действий.
     */
    public function __construct(
        private PromoCampaignRepositoryInterface $repository,
        private FraudControlService $fraudControl,
        private readonly ConnectionInterface $db,
        private readonly LogManager $log,
        private readonly Connection $redis,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Исключительно надежно и транзакционно применяет промокод к текущему заказу/корзине пользователя.
     *
     * Выполняет строгие проверки доступности бюджета, проверяет антифрод, применяет пессимистичные
     * блокировки (optimistic/pessimistic locking) для предотвращения двойного списания квот.
     *
     * @param  string  $code  Строковое представление предоставленного клиентом промокода.
     * @param  int  $tenantId  Безусловно обязательный идентификатор арендатора (tenant scoping).
     * @param  int  $userId  Идентификатор текущего зарегистрированного покупателя.
     * @param  int  $orderAmountKopecks  Текущая сумма заказа в копейках для валидации порогов.
     * @param  string  $correlationId  Обязательный UUID идентификатор для транзакционной трассировки.
     * @return DiscountResult Строго типизированный объект с детальным результатом операции.
     */
    public function applyPromo(
        string $code,
        int $tenantId,
        int $userId,
        int $orderAmountKopecks,
        string $correlationId
    ): DiscountResult {
        $this->logAction('promo_application_initiated', 'PromoCampaign', null, [
            'code' => $code,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ], $userId, $tenantId);

        // 1. Обязательная проверка на предмет мошенничества перед любыми операциями (фрод-контроль)
        // Строго предотвращает массовые брутфорс-атаки на подбор активных промокодов.
        $this->fraudControl->checkPromoAbuse($userId, $tenantId, $correlationId);

        // 2. Использование распределенного Redis-лока для предотвращения race condition одного юзера
        $lockKey = "promo_apply_lock:tenant:{$tenantId}:user:{$userId}";
        $lock = $this->redis->set($lockKey, 'locked', 'EX', 10, 'NX');

        if (! $lock) {
            $this->logAction('promo_parallel_attempt_blocked', 'PromoCampaign', null, [
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ], $userId, $tenantId);

            return new DiscountResult(false, 0, 'Пожалуйста, подождите завершения предыдущей операции.');
        }

        try {
            // 3. Выполнение изменения исключительно в пределах атомарной БД-транзакции
            return $this->db->transaction(function () use ($code, $tenantId, $userId, $orderAmountKopecks, $correlationId) {
                // Извлечение сущности с применением внутренней блокировки (lockForUpdate)
                $campaign = $this->repository->findByCodeAndTenant($code, $tenantId);

                if ($campaign === null) {
                    return new DiscountResult(false, 0, 'Указанный промокод категорически не найден или недействителен.');
                }

                if (! $campaign->isApplicable($orderAmountKopecks)) {
                    $this->logAction('promo_conditions_not_met', 'PromoCampaign', $campaign->getId(), [
                        'correlation_id' => $correlationId,
                    ], $userId, $tenantId);

                    return new DiscountResult(false, 0, 'Условия применения текущей промо-кампании не выполнены.');
                }

                // В реальном проекте здесь будет стратегия паттерна расчета скидки (процентная/фиксированная).
                // Для MVP-остова моделируем фиксированную выгоду.
                $discountKopecks = (int) ($orderAmountKopecks * 0.1); // Пример: 10%

                $campaign->applyUsage($discountKopecks);

                // Строгое сохранение обновленного стейта кампании обратно в БД
                $this->repository->save($campaign);

                // Обязательное создание лога аудита в БД для финансовых проверок
                $this->repository->logUsage(
                    $campaign->getId(),
                    $tenantId,
                    $userId,
                    'applied',
                    $discountKopecks,
                    $correlationId
                );

                $this->logAction('promo_code_applied', 'PromoCampaign', $campaign->getId(), [
                    'discount_kopecks' => $discountKopecks,
                    'correlation_id' => $correlationId,
                ], $userId, $tenantId);

                return new DiscountResult(true, $discountKopecks);
            });
        } catch (\Throwable $e) {
            $this->log->channel('promo')->error('Критическая непредвиденная ошибка при применении промокода', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        } finally {
            $this->redis->del($lockKey);
        }
    }
}
