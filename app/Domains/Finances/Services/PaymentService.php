<?php
declare(strict_types=1);

namespace App\Domains\Finances\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Security\FraudControlService;
use App\Domains\Finances\Models\FinanceTransaction;

final readonly class PaymentService
{
    public function __construct(
        private FraudControlService $fraudControlService
    ) {}

    public function processPayment(array $data, string $correlationId): FinanceTransaction
    {
        return DB::transaction(function () use ($data, $correlationId) {
            Log::channel('audit')->info("ОБРАБОТКА ПЛАТЕЖА ЗАПУЩЕНА", ["correlation_id" => $correlationId, "data" => $data]);
            
            // Проверка на фрод ОБЯЗАТЕЛЬНА
            FraudControlService::check($data, $correlationId);

            $transaction = FinanceTransaction::create([
                "tenant_id" => tenant("id") ?? 1,
                "correlation_id" => $correlationId,
                "amount" => $data["amount"] ?? 0,
                "type" => "payment",
                "status" => "processed",
                "tags" => []
            ]);

            Log::channel('audit')->info("ПЛАТЕЖ УСПЕШНО ОБРАБОТАН", ["correlation_id" => $correlationId, "id" => $transaction->id]);

            return $transaction;
        });
    }
}
