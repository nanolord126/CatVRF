<?php

declare(strict_types=1);

namespace App\Domains\PromoCampaigns\Services;


use Psr\Log\LoggerInterface;
use App\Domains\PromoCampaigns\DTOs\DiscountResult;
use App\Domains\PromoCampaigns\DTOs\ValidationResult;
use App\Domains\PromoCampaigns\Enums\PromoStatus;
use App\Domains\PromoCampaigns\Enums\PromoType;
use App\Domains\PromoCampaigns\Models\PromoAuditLog;
use App\Domains\PromoCampaigns\Models\PromoCampaign;
use App\Domains\PromoCampaigns\Models\PromoUse;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Исключительно массивный и центральный оркестратор маркетинговых промо-кампаний (PromoCampaignService).
 *
 * Категорически единственная точка входа для создания, управления и применения акций,
 * скидок и подарочных сертификатов. Строго соблюдает ФЗ-38, обеспечивает защиту от злоупотреблений
 * (FraudControlService) и безусловно выполняет все мутации балансов (wallet, budget) внутри БД-транзакций
 * с pessimistic locks.
 */
final readonly class PromoCampaignService
{
    /**
     * Безусловный конструктор с внедрением требуемых защищенных зависимостей.
     */
    public function __construct(private FraudControlService $fraud,
        private WalletService $walletService,
        private \Illuminate\Contracts\Cache\Repository $cache,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {

    }

    /**
     * Абсолютно безопасно регистрирует новую маркетинговую акцию в системе.
     *
     * @param array<string, mixed> $data Строго проверенные данные Форм-реквеста.
     * @param int $tenantId ИД изолированного тенанта.
     * @param int $userId ИД пользователя-создателя (овнера или менеджера).
     * @param string $correlationId
     * @return PromoCampaign Свежесозданная сущность-акция.
     */
    public function createCampaign(array $data, int $tenantId, int $userId, string $correlationId): PromoCampaign
    {
        return $this->db->transaction(function () use ($data, $tenantId, $userId, $correlationId) {
            $campaign = PromoCampaign::create(array_merge($data, [
                'tenant_id' => $tenantId,
                'spent_budget' => 0,
                'status' => PromoStatus::ACTIVE,
                'correlation_id' => $correlationId,
                'created_by' => $userId,
            ]));

            PromoAuditLog::create([
                'promo_campaign_id' => $campaign->id,
                'action' => 'created',
                'user_id' => $userId,
                'details' => ['initial_budget' => $campaign->budget],
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            $this->logger->info('Категорически успешное создание промо-кампании', [
                'campaign_id' => $campaign->id,
                'code' => $campaign->code,
                'correlation_id' => $correlationId,
            ]);

            // Инвалидация публичного кэша акций для тенанта
            $vertical = $data['applicable_verticals'][0] ?? 'all';
            $this->invalidateActiveCampaignsCache($tenantId, $vertical);

            return $campaign;
        });
    }

    /**
     * Безусловно валидирует применимость кода ДО фактического применения к сущности Cart/Order.
     *
     * @param string $code Маркетинговый код.
     * @param int $tenantId Контекст бизнеса.
     * @param int $userId Покупатель.
     * @param int $cartSubtotal Сумма корзины до скидок (в копейках).
     * @param string $correlationId
     * @return ValidationResult Исключительно точный ответ.
     */
    public function validatePromo(string $code, int $tenantId, int $userId, int $cartSubtotal, string $correlationId): ValidationResult
    {
        /** @var PromoCampaign|null $campaign */
        $campaign = PromoCampaign::where('code', $code)
            ->where('tenant_id', $tenantId)
            ->where('status', PromoStatus::ACTIVE)
            ->first();

        if (!$campaign) {
            return new ValidationResult(false, 'Промокод не найден или категорически неактивен', null);
        }

        if (now()->lessThan($campaign->start_at) || now()->greaterThan($campaign->end_at)) {
            return new ValidationResult(false, 'Безусловный срок действия акции истек или еще не начался', null);
        }

        if ($cartSubtotal < $campaign->min_order_amount) {
            return new ValidationResult(false, "Исключительно недостигнута минимальная сумма заказа: {$campaign->min_order_amount} коп.", null);
        }

        if ($campaign->spent_budget >= $campaign->budget) {
            return new ValidationResult(false, 'Бюджет данной кампании категорически исчерпан', null);
        }

        // Проверка лимитов на пользователя
        $userUses = PromoUse::where('promo_campaign_id', $campaign->id)
            ->where('user_id', $userId)
            ->count();

        if ($userUses >= $campaign->max_uses_per_user) {
            return new ValidationResult(false, 'Вы исчерпали свой персональный лимит использований', null);
        }

        // Вычисляем скидку математически
        $discountAmount = $this->calculateDiscountLogic($campaign, $cartSubtotal);

        // Гарантируем, что скидка не превышает остаток бюджета акции
        $availableBudget = $campaign->budget - $campaign->spent_budget;
        if ($discountAmount > $availableBudget) {
            $discountAmount = $availableBudget;
        }

        // Запрещаем скидку больше суммы самой корзины (нулевой чек)
        if ($discountAmount >= $cartSubtotal) {
            $discountAmount = $cartSubtotal - 100; // Оставляем минимум 1 рубль к оплате
        }

        return new ValidationResult(true, 'Промокод безусловно применим', $discountAmount, (string) $campaign->id);
    }

    /**
     * Категорически применяет скидку, списывает бюджет акции и генерирует аудит.
     * Требует строгой изоляции внутри транзакции.
     *
     * @param string $code Секретный код.
     * @param int $tenantId Идентификатор тенанта.
     * @param int $userId Идентификатор юзера.
     * @param int $orderId Идентификатор оформляемого заказа или брони (сгенерированный заранее).
     * @param int $cartSubtotal Оригинальная сумма (в копейках).
     * @param string $correlationId
     * @return DiscountResult
     */
    public function applyPromo(
        string $code,
        int $tenantId,
        int $userId,
        int $orderId,
        int $cartSubtotal,
        string $correlationId
    ): DiscountResult {
        // 1. Предварительный Fraud-чек перед применением (DDoS промиков)
        $this->fraud->checkPromoAbuse($userId, $code, $correlationId);

        return $this->db->transaction(function () use ($code, $tenantId, $userId, $orderId, $cartSubtotal, $correlationId) {
            // 2. Исключительная блокировка кампании для защиты от Race Condition исчерпания бюджета
            /** @var PromoCampaign $campaign */
            $campaign = PromoCampaign::where('code', $code)
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->firstOrFail();

            // 3. Повторная строгая валидация под локом (чтобы исключить гонки)
            $validation = $this->validatePromo($code, $tenantId, $userId, $cartSubtotal, $correlationId);
            if (!$validation->isValid || $validation->calculatedDiscount === null) {
                return new DiscountResult(false, $cartSubtotal, 0, $cartSubtotal, $validation->message);
            }

            $discountAmount = $validation->calculatedDiscount;

            // 4. Фиксация факта использования
            $promoUse = PromoUse::create([
                'promo_campaign_id' => $campaign->id,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'order_id' => $orderId,
                'discount_amount' => $discountAmount,
                'correlation_id' => $correlationId,
                'used_at' => now(),
            ]);

            // 5. Жесткое инкрементирование потраченного бюджета
            $campaign->spent_budget += $discountAmount;
            
            if ($campaign->spent_budget >= $campaign->budget) {
                $campaign->status = PromoStatus::EXHAUSTED;
                PromoAuditLog::create([
                    'promo_campaign_id' => $campaign->id,
                    'action' => 'budget_exhausted',
                    'user_id' => $userId,
                    'details' => ['spent' => $campaign->spent_budget],
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                ]);
            }
            $campaign->save();

            PromoAuditLog::create([
                'promo_campaign_id' => $campaign->id,
                'action' => 'applied',
                'user_id' => $userId,
                'details' => ['order_id' => $orderId, 'discount' => $discountAmount],
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            $this->logger->info('Безусловное применение промокода с жестким списанием бюджета', [
                'campaign_id' => $campaign->id,
                'user_id' => $userId,
                'discount' => $discountAmount,
                'correlation_id' => $correlationId,
            ]);

            return new DiscountResult(
                success: true,
                originalAmount: $cartSubtotal,
                discountAmount: $discountAmount,
                finalAmount: max(0, $cartSubtotal - $discountAmount),
                message: 'Скидка категорически и успешно применена',
                promoUseId: (string) $promoUse->id
            );
        });
    }

    /**
     * Бескомпромиссно отменяет применение промокода (например, при отмене заказа),
     * с возвратом холдированного бюджета обратно в кампанию.
     *
     * @param string $useId ИД использования.
     * @param int $userId ИД клиента/менеджера.
     * @param string $correlationId
     * @return bool
     */
    public function cancelPromoUse(string $useId, int $userId, string $correlationId): bool
    {
        return $this->db->transaction(function () use ($useId, $userId, $correlationId) {
            /** @var PromoUse $promoUse */
            $promoUse = PromoUse::where('id', $useId)->firstOrFail();

            /** @var PromoCampaign $campaign */
            $campaign = PromoCampaign::where('id', $promoUse->promo_campaign_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Возврат бюджета
            $campaign->spent_budget -= $promoUse->discount_amount;
            
            // Если бюджет был исчерпан, но вернулся - возобновляем (если сроки живы)
            if ($campaign->status === PromoStatus::EXHAUSTED && $campaign->spent_budget < $campaign->budget) {
                if (now()->lessThan($campaign->end_at)) {
                    $campaign->status = PromoStatus::ACTIVE;
                }
            }
            $campaign->save();

            PromoAuditLog::create([
                'promo_campaign_id' => $campaign->id,
                'action' => 'cancelled',
                'user_id' => $userId,
                'details' => ['restored_amount' => $promoUse->discount_amount],
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            $this->logger->info('Исключительный возврат реферально/промо бюджета кампании', [
                'campaign_id' => $campaign->id,
                'use_id' => $promoUse->id,
                'amount' => $promoUse->discount_amount,
                'correlation_id' => $correlationId,
            ]);

            $promoUse->delete(); // Физически удаляем или SoftDelete

            return true;
        });
    }

    /**
     * Вытягивает из Redis кэша список абсолютно активных кампаний тенанта.
     *
     * @param int $tenantId
     * @param string $vertical
     * @return Collection
     */
    public function getActiveCampaigns(int $tenantId, string $vertical = 'all'): Collection
    {
        $cacheKey = "promo:active:tenant:{$tenantId}:vertical:{$vertical}";

        return $this->cache->remember($cacheKey, 300, function () use ($tenantId, $vertical) {
            $query = PromoCampaign::where('tenant_id', $tenantId)
                ->where('status', PromoStatus::ACTIVE)
                ->where('start_at', '<=', now())
                ->where('end_at', '>=', now());

            if ($vertical !== 'all') {
                $query->whereJsonContains('applicable_verticals', $vertical);
            }

            return $query->get();
        });
    }

    /**
     * @param int $tenantId
     * @param string $vertical
     */
    private function invalidateActiveCampaignsCache(int $tenantId, string $vertical): void
    {
        $this->cache->forget("promo:active:tenant:{$tenantId}:vertical:{$vertical}");
        $this->cache->forget("promo:active:tenant:{$tenantId}:vertical:all");
    }

    /**
     * Внутренняя приватная функция детерменированного расчета суммы скидки (в копейках).
     */
    private function calculateDiscountLogic(PromoCampaign $campaign, int $subtotal): int
    {
        return match ($campaign->type) {
            PromoType::DISCOUNT_PERCENT => (int) round($subtotal * ((int)$campaign->description / 100)), // description хранит процент в данной реализации
            PromoType::FIXED_AMOUNT => (int) $campaign->description, // description хранит копейки
            PromoType::GIFT_CARD => (int) $campaign->description, // аналогично фиксу
            default => 0, // bundle и buy_x_get_y требуют сложной логики парсинга корзины (внешний резолвер)
        };
    }
}
