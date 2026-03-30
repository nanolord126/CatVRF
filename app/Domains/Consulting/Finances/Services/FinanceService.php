<?php declare(strict_types=1);

namespace App\Domains\Consulting\Finances\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FinanceService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Dependencies injected via constructor
        // Add private readonly properties here
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
