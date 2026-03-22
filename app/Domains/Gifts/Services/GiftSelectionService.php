<?php
declare(strict_types=1);

namespace App\Domains\Gifts\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

final readonly class GiftSelectionService
{
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
