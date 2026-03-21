<?php
declare(strict_types=1);

namespace App\Domains\Finances\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Security\FraudControlService;

final readonly class FinanceService
{
    public function __construct(
        private FraudControlService $fraudControlService
    ) {}

    public function generateReport(string $correlationId): array
    {
        Log::channel('audit')->info("ГЕНЕРАЦИЯ ФИНАНСОВОГО ОТЧЕТА", ["correlation_id" => $correlationId]);
        
        FraudControlService::check(["action" => "report"], $correlationId);
        
        return ["status" => "success", "report_url" => "/reports/{$correlationId}.pdf"];
    }
}
