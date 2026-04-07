<?php

declare(strict_types=1);

/**
 * Class MigrationConfirmation
 *
 * Part of the Referral vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\Referral\DTOs
 */
final readonly class MigrationConfirmation
{
    /**
     * Безусловный инициализатор статуса миграции B2B клиента.
     *
     * @param bool $isConfirmed Категорически подтверждает, доказана ли миграция.
     * @param string $sourcePlatform Название платформы-источника (например, 'Yandex', 'Dikidi').
     * @param int|null $reducedCommission Опциональная категорически сниженная процентная комиссия (в десятках долей, например 10% или 12%).
     * @param string $message Сопровождающее исключительное текстовое пояснение для логов.
     */
    public function __construct(
        public bool $isConfirmed,
        public string $sourcePlatform,
        public ?int $reducedCommission,
        public string $message) {

    }
}
