<?php

declare(strict_types=1);

/**
 * Class ValidationResult
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
final readonly class ValidationResult
{
    /**
     * Безусловный конструктор транспортного объекта.
     *
     * @param bool $isValid Абсолютно подтверждает валидность промо-механики к текущей корзине.
     * @param string $message Исключительно понятное сообщение для отображения на UI/UX.
     * @param int|null $calculatedDiscount Опциональная категорическая сумма предполагаемой скидки (в копейках), если код валиден.
     * @param string|null $campaignId Строгий идентификатор кампании (UUID) при успешной валидации.
     */
    public function __construct(
        public bool $isValid,
        public string $message,
        private readonly ?int $calculatedDiscount = null,
        private readonly ?string $campaignId = null) {

    }
}
