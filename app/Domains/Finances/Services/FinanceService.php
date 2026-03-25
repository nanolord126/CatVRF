declare(strict_types=1);

<?php
declare(strict_types=1);

namespace App\Domains\Finances\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

final readonly /**
 * FinanceService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FinanceService
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function __construct(
        private FraudControlService $fraudControlService
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}

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
        $this->log->channel('audit')->info("ГЕНЕРАЦИЯ ФИНАНСОВОГО ОТЧЕТА", ["correlation_id" => $correlationId]);
        
        
        return ["status" => "success", "report_url" => "/reports/{$correlationId}.pdf"];
    }
}
