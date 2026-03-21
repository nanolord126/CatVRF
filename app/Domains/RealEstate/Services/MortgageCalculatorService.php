<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use Illuminate\Support\Facades\Log;

/**
 * Service для расчёта ипотеки и условий кредита.
 * Production 2026.
 */
final class MortgageCalculatorService
{
    public function calculateMortgage(
        int $propertyPrice,
        int $initialPayment,
        int $loanTermMonths,
        float $interestRate,
        string $correlationId = '',
    ): array {
        try {
            $loanAmount = $propertyPrice - $initialPayment;
            $monthlyRate = $interestRate / 100 / 12;

            // Формула расчёта ежемесячного платежа (аннуитет)
            if ($monthlyRate === 0.0) {
                $monthlyPayment = (int) ($loanAmount / $loanTermMonths);
            } else {
                $monthlyPayment = (int) (
                    $loanAmount * $monthlyRate * (1 + $monthlyRate) ** $loanTermMonths
                    / ((1 + $monthlyRate) ** $loanTermMonths - 1)
                );
            }

            $totalPayment = $monthlyPayment * $loanTermMonths;
            $totalInterest = $totalPayment - $loanAmount;

            Log::channel('audit')->info('Mortgage calculated', [
                'property_price' => $propertyPrice,
                'loan_amount' => $loanAmount,
                'monthly_payment' => $monthlyPayment,
                'total_interest' => $totalInterest,
                'correlation_id' => $correlationId,
            ]);

            return [
                'loan_amount' => $loanAmount,
                'monthly_payment' => $monthlyPayment,
                'total_payment' => $totalPayment,
                'total_interest' => $totalInterest,
                'interest_rate' => $interestRate,
                'loan_term_months' => $loanTermMonths,
            ];
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Mortgage calculation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function compareCredits(array $loans): array
    {
        $comparison = [];
        foreach ($loans as $bank => $data) {
            $comparison[$bank] = $this->calculateMortgage(
                $data['property_price'],
                $data['initial_payment'],
                $data['loan_term_months'],
                $data['interest_rate'],
            );
        }
        return $comparison;
    }
}
