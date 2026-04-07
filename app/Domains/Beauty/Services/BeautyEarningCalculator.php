<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Finances\Domain\Interfaces\EarningCalculatorInterface;
use App\Models\Appointment; // Assuming this is the model for the Beauty vertical
use Carbon\Carbon;

/**
 * Class BeautyEarningCalculator
 *
 * Part of the Beauty vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Beauty\Services
 */
final readonly class BeautyEarningCalculator implements EarningCalculatorInterface
{
    private const COMMISSION_RATE = 0.14; // 14%

    /**
     * Handle getVertical operation.
     *
     * @throws \DomainException
     */
    public function getVertical(): string
    {
        return 'beauty';
    }

    /**
     * Handle calculateForTenant operation.
     *
     * @throws \DomainException
     */
    public function calculateForTenant(int $tenantId, Carbon $from, Carbon $to): int
    {
        $totalRevenue = Appointment::where('tenant_id', $tenantId)
            ->whereBetween('completed_at', [$from, $to])
            ->where('status', 'completed')
            ->sum('price');

        $commission = (int) ($totalRevenue * self::COMMISSION_RATE);
        
        // In a real app, you'd subtract bonuses, refunds, etc.
        $netEarnings = $totalRevenue - $commission;

        return $netEarnings;
    }

    /**
     * Возвращает текущую ставку комиссии платформы.
     */
    public function getCommissionRate(): float
    {
        return self::COMMISSION_RATE;
    }

    /**
     * Человекочитаемое название вертикали.
     */
    public function getVerticalLabel(): string
    {
        return 'Салоны красоты';
    }
}
