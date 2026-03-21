<?php
declare(strict_types=1);

namespace App\Domains\SportingGoods\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Security\FraudControlService;
use InvalidArgumentException;

final readonly class SizeGuideService
{
    public function __construct(
        private FraudControlService $fraudControlService
    ) {}

    public function calculateSize(array $data, string $correlationId): array
    {
        Log::channel('audit')->info("РАСЧЕТ РАЗМЕРА", ["correlation_id" => $correlationId]);
        
        FraudControlService::check($data, $correlationId);
        
        if (empty($data["height"])) {
            Log::channel('audit')->error("Ошибка расчета размера", ["correlation_id" => $correlationId]);
            throw new InvalidArgumentException("Missing height parameter.");
        }
        
        return ["size" => "L"];
    }
}
