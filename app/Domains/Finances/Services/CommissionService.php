<?php

declare(strict_types=1);

namespace App\Domains\Finances\Services;

use App\Domains\Finances\DTOs\CalculateCommissionDto;
use Psr\Log\LoggerInterface;

/**
 * Сервис расчёта комиссии платформы.
 *
 * Учитывает B2B/B2C, тарифный план (tier) и специфику вертикали.
 */
final readonly class CommissionService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * Возвращает базовую комиссию (в процентах от 0 до 100).
     */
    private function getBaseCommissionRate(bool $isB2B, string $b2bTier): float
    {
        if (!$isB2B) {
            // Для физических лиц базовая комиссия 14%
            return 14.0;
        }

        // Для B2B дифференцированная шкала
        return match ($b2bTier) {
            'platinum' => 8.0,
            'gold'     => 10.0,
            'silver'   => 11.0,
            default    => 12.0,
        };
    }

    /**
     * Минимальная фиксированная комиссия в копейках.
     * Применяется при мелких суммах, чтобы обеспечить рентабельность.
     */
    private function getMinimumCommissionKopecks(): int
    {
        return 100;
    }

    /**
     * Рассчитать сумму комиссии в копейках.
     *
     * Если рассчитанная комиссия меньше минимальной фиксированной,
     * применяется минимальное значение.
     */
    public function calculate(CalculateCommissionDto $dto): int
    {
        $rate = $this->getBaseCommissionRate($dto->isB2B, $dto->b2bTier);

        $commissionKopecks = (int) round($dto->amountKopecks * ($rate / 100));

        $minimumCommission = $this->getMinimumCommissionKopecks();
        if ($commissionKopecks < $minimumCommission && $dto->amountKopecks > 0) {
            $commissionKopecks = $minimumCommission;
        }

        $this->logger->info('Commission calculated', [
            'amount_kopecks'     => $dto->amountKopecks,
            'rate'               => $rate,
            'is_b2b'             => $dto->isB2B,
            'tier'               => $dto->b2bTier,
            'vertical'           => $dto->vertical,
            'commission_kopecks' => $commissionKopecks,
            'correlation_id'     => $dto->correlationId,
        ]);

        return $commissionKopecks;
    }
}
