<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Infrastructure\Services;

use App\Domains\RealEstate\Domain\Services\MortgageCalculatorServiceInterface;

/**
 * Реализация ипотечного калькулятора по стандартной аннуитетной формуле.
 *
 * PMT = P * (r * (1+r)^n) / ((1+r)^n - 1)
 *
 *  P — сумма кредита (копейки)
 *  r — ежемесячная ставка (annual / 12 / 100)
 *  n — количество месяцев
 */
final readonly class FakeMortgageCalculatorService implements MortgageCalculatorServiceInterface
{
    /**
     * Рассчитать ежемесячный платёж.
     *
     * @param int   $loanAmountKopecks   Сумма кредита в копейках
     * @param int   $downPaymentKopecks  Первоначальный взнос в копейках
     * @param float $annualRatePercent   Годовая ставка в процентах (например 12.5)
     * @param int   $termMonths          Срок кредита в месяцах
     * @return int  Ежемесячный платёж в копейках
     */
    public function calculateMonthlyPayment(
        int $loanAmountKopecks,
        int $downPaymentKopecks,
        float $annualRatePercent,
        int $termMonths,
    ): int {
        $principal = $loanAmountKopecks - $downPaymentKopecks;

        if ($principal <= 0) {
            return 0;
        }

        if ($termMonths <= 0) {
            throw new \DomainException('Срок кредита должен быть больше нуля.');
        }

        $monthlyRate = $annualRatePercent / 12.0 / 100.0;

        if ($monthlyRate < PHP_FLOAT_EPSILON) {
            // Нулевая ставка — простое деление
            return (int) round($principal / $termMonths);
        }

        $factor = (1.0 + $monthlyRate) ** $termMonths;
        $payment = $principal * ($monthlyRate * $factor) / ($factor - 1.0);

        return (int) round($payment);
    }

    /**
     * Рассчитать итоговую переплату по кредиту.
     *
     * @return int Сумма переплаты в копейках
     */
    public function calculateTotalOverpayment(
        int $loanAmountKopecks,
        int $downPaymentKopecks,
        float $annualRatePercent,
        int $termMonths,
    ): int {
        $principal = $loanAmountKopecks - $downPaymentKopecks;

        if ($principal <= 0) {
            return 0;
        }

        $monthly   = $this->calculateMonthlyPayment(
            $loanAmountKopecks,
            $downPaymentKopecks,
            $annualRatePercent,
            $termMonths,
        );

        $totalPaid = $monthly * $termMonths;

        return max(0, $totalPaid - $principal);
    }

    /**
     * Рассчитать максимальную сумму кредита по ежемесячному доходу.
     *
     * Банки применяют коэффициент PTI (Payment-to-Income) = 0.4.
     *
     * @param int   $monthlyIncomeKopecks  Ежемесячный доход в копейках
     * @param float $annualRatePercent     Годовая ставка
     * @param int   $termMonths           Срок кредита в месяцах
     * @return int  Максимальная сумма кредита в копейках
     */
    public function calculateMaxLoanAmount(
        int $monthlyIncomeKopecks,
        float $annualRatePercent,
        int $termMonths,
    ): int {
        $maxMonthlyPayment = (int) floor($monthlyIncomeKopecks * 0.4);

        if ($maxMonthlyPayment <= 0 || $termMonths <= 0) {
            return 0;
        }

        $monthlyRate = $annualRatePercent / 12.0 / 100.0;

        if ($monthlyRate < PHP_FLOAT_EPSILON) {
            return $maxMonthlyPayment * $termMonths;
        }

        $factor = (1.0 + $monthlyRate) ** $termMonths;
        $maxLoan = $maxMonthlyPayment * ($factor - 1.0) / ($monthlyRate * $factor);

        return (int) floor($maxLoan);
    }
}
