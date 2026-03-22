<?php declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use Illuminate\Support\Facades\DB;

use App\Domains\Logistics\Models\DeliveryOrder;
use Illuminate\Support\Str;

final class LogisticsService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function createDeliveryOrder(array $data, int $userId, int $tenantId): DeliveryOrder
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($data, $userId, $tenantId) {
        $order = DeliveryOrder::create([
            'tenant_id' => $tenantId,
            'uuid' => Str::uuid(),
            'correlation_id' => $this->correlationId,
            'user_id' => $userId,
            'address' => $data['address'],
            'status' => 'pending',
            'price' => $data['price'] ?? 0,
        ]);

        Log::channel('audit')->info('Delivery order created', [
            'correlation_id' => $this->correlationId,
            'order_id' => $order->id,
        ]);

        return $order;
        });
    }

    /**
     * Выполняет операцию в транзакции с аудитом.
     */
    public function executeInTransaction(callable $callback)
    {


        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($callback) {
            return $callback();
        });
    }
}