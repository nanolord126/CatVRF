<?php

declare(strict_types=1);


namespace App\Domains\Consulting\Finances\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;
use App\Domains\Consulting\Finances\Models\FinanceTransaction;

final readonly /**
 * PaymentService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PaymentService
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function __construct(
        private FraudControlService $fraudControlService
    ) {}

    public function processPayment(array $data, string $correlationId): FinanceTransaction
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($data, $correlationId) {
            Log::channel('audit')->info("ОБРАБОТКА ПЛАТЕЖА ЗАПУЩЕНА", ["correlation_id" => $correlationId, "data" => $data]);
            
            // Проверка на фрод ОБЯЗАТЕЛЬНА

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
