<?php
declare(strict_types=1);

namespace App\Domains\Gifts\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Security\FraudControlService;

final readonly class GiftSelectionService
{
    public function __construct(
        private FraudControlService $fraudControlService
    ) {}

    public function recommendGift(array $criteria, string $correlationId): array
    {
        Log::channel('audit')->info("ПОДБОР ПОДАРКА", ["correlation_id" => $correlationId]);
        
        FraudControlService::check($criteria, $correlationId);
        
        return [
            "recommended_ids" => [1, 2, 3]
        ];
    }
}
