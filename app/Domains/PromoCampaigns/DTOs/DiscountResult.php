<?php

declare(strict_types=1);

namespace App\Domains\PromoCampaigns\DTOs;

/**
 * Class DiscountResult
 *
 * Part of the PromoCampaigns vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\PromoCampaigns\DTOs
 */
final readonly class DiscountResult
{
    /**
     * Исключительный инициализатор финализированного результата применения акции.
     *
     * @param bool $success Безусловный статус успешного изменения цен.
     * @param int $originalAmount Исходная сумма корзины/бронирования ДО применения скидки (в копейках).
     * @param int $discountAmount Категорически точный размер примененной скидки (в копейках).
     * @param int $finalAmount Итоговая сумма к обязательной оплате (в копейках).
     * @param string $message Человекочитаемое объяснение (например, "Скидка 500 рублей успешно применена").
     * @param string|null $promoUseId Безусловный идентификатор записи фиксации использования (PromoUse ID).
     */
    public function __construct(
        public bool $success,
        public int $originalAmount,
        public int $discountAmount,
        public int $finalAmount,
        public string $message,
        private readonly ?string $promoUseId = null) {

    }
}
