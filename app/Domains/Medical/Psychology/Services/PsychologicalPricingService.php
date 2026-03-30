<?php declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PsychologicalPricingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Расчет финальной стоимости с учетом акций и лояльности.
         */
        public function calculateFinalPrice(int $serviceId, int $clientId): int
        {
            $service = PsyServiceModel::findOrFail($serviceId);
            $basePrice = $service->price;

            // В 2026 тут вызывается PromoCampaignService
            // Если это первый визит - скидка 10%
            $isFirstTime = !\App\Domains\Medical\Psychology\Models\PsychologicalBooking::where('client_id', $clientId)->exists();

            if ($isFirstTime) {
                $basePrice = (int) ($basePrice * 0.9);
            }

            return $basePrice;
        }
}
