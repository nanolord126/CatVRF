<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;

use App\Domains\Auto\Models\Vehicle;
use Illuminate\Support\Facades\Log;

final class InsuranceCalculatorService
{
    public function calculatePremium(
        int $vehicleId,
        string $insuranceType,
        int $coverageAmount,
        int $durationMonths
    ): int {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        Log::channel('audit')->info('Calculating insurance premium', [
            'correlation_id' => $correlationId,
            'vehicle_id' => $vehicleId,
            'insurance_type' => $insuranceType,
        ]);

        try {
            $vehicle = Vehicle::findOrFail($vehicleId);

            // Base rate по типу страховки
            $baseRate = match ($insuranceType) {
                'osago' => 0.03, // 3% от стоимости покрытия
                'kasko' => 0.07, // 7%
                'full' => 0.10,  // 10%
                default => 0.05,
            };

            // Коэффициент по году выпуска
            $ageCoefficient = $this->calculateAgeCoefficient($vehicle->year);

            // Коэффициент по пробегу
            $mileageCoefficient = $this->calculateMileageCoefficient($vehicle->mileage);

            // Базовая премия
            $basePremium = (int) ($coverageAmount * $baseRate);

            // Применяем коэффициенты
            $adjustedPremium = $basePremium * $ageCoefficient * $mileageCoefficient;

            // Коэффициент по длительности (скидка за год)
            $durationDiscount = $durationMonths >= 12 ? 0.9 : 1.0;

            $finalPremium = (int) ($adjustedPremium * $durationDiscount);

            Log::channel('audit')->info('Insurance premium calculated', [
                'correlation_id' => $correlationId,
                'premium' => $finalPremium,
            ]);

            return $finalPremium;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Insurance premium calculation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function calculateAgeCoefficient(int $year): float
    {
        $age = date('Y') - $year;

        return match (true) {
            $age <= 3 => 1.0,
            $age <= 7 => 1.2,
            $age <= 10 => 1.5,
            default => 2.0,
        };
    }

    private function calculateMileageCoefficient(?int $mileage): float
    {
        if (!$mileage) {
            return 1.0;
        }

        return match (true) {
            $mileage <= 50000 => 1.0,
            $mileage <= 100000 => 1.1,
            $mileage <= 150000 => 1.3,
            default => 1.5,
        };
    }
}
