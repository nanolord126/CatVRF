<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


namespace App\Domains\RealEstate\Domain\Services;

/**
 * Domain service interface for mortgage calculation.
 * Concrete implementations may integrate with bank APIs.
 */
interface MortgageCalculatorServiceInterface
{
    /**
     * Calculate monthly payment in kopecks.
     *
     * @param  int    $propertyPriceKopecks   Full property price
     * @param  int    $downPaymentKopecks     Down payment (must be >= 15% of price)
     * @param  float  $annualRatePercent      Annual interest rate (e.g. 12.5)
     * @param  int    $termYears             Mortgage term in years
     * @return int                            Monthly payment in kopecks
     */
    public function calculateMonthlyPayment(
        int   $propertyPriceKopecks,
        int   $downPaymentKopecks,
        float $annualRatePercent,
        int   $termYears,
    ): int;

    /**
     * Total overpayment over the full mortgage term (in kopecks).
     */
    public function calculateTotalOverpayment(
        int   $propertyPriceKopecks,
        int   $downPaymentKopecks,
        float $annualRatePercent,
        int   $termYears,
    ): int;

    /**
     * Maximum loan amount based on monthly income.
     *
     * @param  int   $monthlyIncomeKopecks  Borrower's monthly income
     * @param  float $annualRatePercent
     * @param  int   $termYears
     * @return int   Max loan in kopecks
     */
    public function calculateMaxLoanAmount(
        int   $monthlyIncomeKopecks,
        float $annualRatePercent,
        int   $termYears,
    ): int;
}
