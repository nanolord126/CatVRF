<?php

declare(strict_types=1);

/**
 * Class QualificationResult
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
final readonly class QualificationResult
{
    /**
     * Исключительный конструктор транспортного объекта квалификации.
     *
     * @param bool $isQualified Удовлетворяет ли реферал категорическим условиям акции.
     * @param int $currentTurnover Текущий безусловный оборот или трата реферала (в копейках).
     * @param int|null $bonusAmount Сумма категорического бонуса, положенного к начислению (в копейках).
     * @param string $message Исключительно человекочитаемое сообщение о причинах статуса.
     */
    public function __construct(
        public bool $isQualified,
        public int $currentTurnover,
        public ?int $bonusAmount,
        public string $message) {

    }
}
