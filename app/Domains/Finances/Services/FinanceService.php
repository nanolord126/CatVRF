<?php
declare(strict_types=1);

namespace App\Domains\Finances\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

final readonly class FinanceService
{
    public function __construct(
        private FraudControlService $fraudControlService
    ) {}

    public function generateReport(string $correlationId): array
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
        Log::channel('audit')->info("ГЕНЕРАЦИЯ ФИНАНСОВОГО ОТЧЕТА", ["correlation_id" => $correlationId]);
        
        
        return ["status" => "success", "report_url" => "/reports/{$correlationId}.pdf"];
    }
}
