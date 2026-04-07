<?php

declare(strict_types=1);

namespace Modules\PromoCampaign\Domain\Entities;

use Modules\PromoCampaign\Domain\Enums\PromoType;
use Modules\PromoCampaign\Domain\Enums\PromoStatus;
use Modules\PromoCampaign\Domain\ValueObjects\PromoCode;
use Carbon\CarbonImmutable;

/**
 * Ключевая доменная сущность (Aggregate Root), представляющая промо-кампанию.
 *
 * Исключительно инкапсулирует все бизнес-правила применения акции, проверки сроков действия,
 * лимитов бюджетирования, количества использований и тенант-изоляции.
 * Строго гарантирует невозможность применения промокода, вышедшего за рамки заданных ограничений.
 */
final class PromoCampaign
{
    /**
     * Конструктор, строго инициализирующий сущность промо-кампании.
     *
     * @param string $id Уникальный идентификатор кампании.
     * @param int $tenantId Идентификатор арендатора (tenant_id).
     * @param PromoType $type Тип проводимой акции.
     * @param PromoCode $code Уникальный промокод.
     * @param PromoStatus $status Текущий категорический статус акции.
     * @param int $budget Общий выделенный бюджет в копейках.
     * @param int $spentBudget Израсходованный на текущий момент бюджет в копейках.
     * @param int|null $minOrderAmount Минимальная сумма заказа для применения скидки в копейках.
     * @param int $maxUsesTotal Максимальное общее количество применений промокода.
     * @param int $currentUses Текущее количество зарегистрированных применений.
     * @param CarbonImmutable|null $startAt Время официального начала кампании.
     * @param CarbonImmutable|null $endAt Время гарантированного завершения кампании.
     */
    public function __construct(
        private readonly string $id,
        private readonly int $tenantId,
        private readonly PromoType $type,
        private readonly PromoCode $code,
        private PromoStatus $status,
        private readonly int $budget,
        private int $spentBudget,
        private readonly ?int $minOrderAmount,
        private readonly int $maxUsesTotal,
        private int $currentUses,
        private readonly ?CarbonImmutable $startAt,
        private readonly ?CarbonImmutable $endAt
    ) {
    }

    /**
     * Строго выполняет комплексную валидацию применимости промо-кампании в текущий момент времени.
     *
     * @param int $orderAmount Сумма заказа/бронирования в копейках для проверки порога минимального заказа.
     * @return bool Абсолютно истинно, если кампания активна, время подходит, бюджет есть, лимиты не исчерпаны.
     */
    public function isApplicable(int $orderAmount): bool
    {
        if ($this->status !== PromoStatus::ACTIVE) {
            return false;
        }

        if ($this->minOrderAmount !== null && $orderAmount < $this->minOrderAmount) {
            return false;
        }

        if ($this->maxUsesTotal > 0 && $this->currentUses >= $this->maxUsesTotal) {
            return false;
        }

        if ($this->budget > 0 && $this->spentBudget >= $this->budget) {
            return false;
        }

        $now = CarbonImmutable::now();

        if ($this->startAt !== null && $now->isBefore($this->startAt)) {
            return false;
        }

        if ($this->endAt !== null && $now->isAfter($this->endAt)) {
            return false;
        }

        return true;
    }

    /**
     * Категорически фиксирует единичное применение промо-кампании и расходует указанный бюджет.
     *
     * @param int $discountKopecks Применяемая скидка в копейках, которая будет списана из бюджета.
     * @return void
     */
    public function applyUsage(int $discountKopecks): void
    {
        $this->currentUses++;
        $this->spentBudget += $discountKopecks;

        if (($this->maxUsesTotal > 0 && $this->currentUses >= $this->maxUsesTotal) || 
            ($this->budget > 0 && $this->spentBudget >= $this->budget)) {
            $this->status = PromoStatus::EXHAUSTED;
        }
    }

    /**
     * @return string Возвращает уникальный ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return int Возвращает ID тенанта.
     */
    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    /**
     * @return PromoType Возвращает строго типизированный тип промо.
     */
    public function getType(): PromoType
    {
        return $this->type;
    }

    /**
     * @return PromoCode Возвращает объект значения промокода.
     */
    public function getCode(): PromoCode
    {
        return $this->code;
    }

    /**
     * @return PromoStatus Возвращает статус.
     */
    public function getStatus(): PromoStatus
    {
        return $this->status;
    }
}
