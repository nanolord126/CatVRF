<?php declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

use App\Domains\Logistics\Models\DeliveryOrder;
use Illuminate\Support\Str;

final class LogisticsService
{
    public function __construct(
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function createDeliveryOrder(array $data): DeliveryOrder
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'createDeliveryOrder'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createDeliveryOrder', ['domain' => __CLASS__]);

        $order = DeliveryOrder::create([
            'tenant_id' => auth()->user()->tenant_id,
            'uuid' => Str::uuid(),
            'correlation_id' => $this->correlationId,
            'user_id' => auth()->id(),
            'address' => $data['address'],
            'status' => 'pending',
            'price' => $data['price'] ?? 0,
        ]);

        Log::channel('audit')->info('Delivery order created', [
            'correlation_id' => $this->correlationId,
            'order_id' => $order->id,
        ]);

        return $order;
    }

    /**
     * Выполняет операцию в транзакции с аудитом.
     */
    public function executeInTransaction(callable $callback)
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'executeInTransaction'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL executeInTransaction', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($callback) {
            return $callback();
        });
    }
}