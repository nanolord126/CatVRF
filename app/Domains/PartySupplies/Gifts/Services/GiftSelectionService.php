<?php declare(strict_types=1);

namespace App\Domains\PartySupplies\Gifts\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GiftSelectionService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Dependencies injected via constructor
        // Add private readonly properties here
        public function __construct(
            private FraudControlService $fraudControlService
        ) {}

        public function recommendGift(array $criteria, string $correlationId): array
        {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
            Log::channel('audit')->info("ПОДБОР ПОДАРКА", ["correlation_id" => $correlationId]);


            return [
                "recommended_ids" => [1, 2, 3]
            ];
        }
}
