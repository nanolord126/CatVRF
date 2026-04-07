<?php

declare(strict_types=1);

namespace App\Domains\Referral\DTOs;

/**
 * Class ReferralStats
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
final readonly class ReferralStats
{
    /**
     * Безусловный конструктор для жесткой фиксации статистики.
     *
     * @param int $totalReferrals Общее категорическое количество приглашенных (зарегистрированных).
     * @param int $totalQualified Число исключительно тех приглашенных, кто достиг порога оборота (qualified).
     * @param int $totalTurnover Суммарный абсолютный оборот приглашенных бизнесов (в копейках).
     * @param int $totalBonusEarned Общая безусловная сумма начисленных вознаграждений рефереру (в копейках).
     */
    public function __construct(
        public int $totalReferrals,
        public int $totalQualified,
        public int $totalTurnover,
        public int $totalBonusEarned) {

    }

    /**
     * Категорически преобразует строго типизированный DTO в массив для сериализации в JSON.
     *
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'total_referrals' => $this->totalReferrals,
            'total_qualified' => $this->totalQualified,
            'total_turnover' => $this->totalTurnover,
            'total_bonus_earned' => $this->totalBonusEarned,
        ];
    }
}
