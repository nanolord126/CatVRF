<?php declare(strict_types=1);

namespace App\Domains\MedicalSupplies\Services;

use App\Services\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\MedicalSupplies\Models\MedicalSupply;
use Illuminate\Support\Str;

final class MedicalSupplyService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly string $correlationId = '',
    ) {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function getSuppliesByType(string $type)
    {
        Log::channel('audit')->info('Get medical supplies', [
            'correlation_id' => $this->correlationId,
            'type' => $type,
        ]);

        return MedicalSupply::where('type', $type)->get();
    }
}
