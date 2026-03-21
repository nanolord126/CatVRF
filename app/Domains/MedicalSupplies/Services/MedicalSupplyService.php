<?php declare(strict_types=1);

namespace App\Domains\MedicalSupplies\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\MedicalSupplies\Models\MedicalSupply;
use Illuminate\Support\Str;

final class MedicalSupplyService
{
    public function __construct(
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function getSuppliesByType(string $type)
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getSuppliesByType'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getSuppliesByType', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getSuppliesByType'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getSuppliesByType', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getSuppliesByType'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getSuppliesByType', ['domain' => __CLASS__]);

        Log::channel('audit')->info('Get medical supplies', [
            'correlation_id' => $this->correlationId,
            'type' => $type,
        ]);

        return MedicalSupply::where('type', $type)->get();
    }
}
