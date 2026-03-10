<?php

namespace App\Domains\Delivery\Services;

use App\Domains\Delivery\Models\DeliveryOrder;
use App\Models\AuditLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliveryService
{
    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
    }

    public function createOrder(array $data): DeliveryOrder
    {
        try {
            return DB::transaction(function () use ($data) {
                $order = DeliveryOrder::create([...$data, 'tenant_id' => tenant()->id, 'status' => 'pending']);
                AuditLog::create([
                    'entity_type' => 'DeliveryOrder',
                    'entity_id' => $order->id,
                    'action' => 'create',
                    'correlation_id' => $this->correlationId,
                    'user_id' => auth()->id(),
                    'ip_address' => request()->ip(),
                ]);
                return $order;
            });
        } catch (Throwable $e) {
            Log::error('DeliveryService.createOrder failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function assignDriver(DeliveryOrder $order, int $driverId): DeliveryOrder
    {
        return DB::transaction(function () use ($order, $driverId) {
            $order->update(['driver_id' => $driverId, 'status' => 'assigned']);
            return $order;
        });
    }

    public function completeOrder(DeliveryOrder $order, array $completion): DeliveryOrder
    {
        return DB::transaction(function () use ($order, $completion) {
            $order->update(['status' => 'completed', 'completed_at' => now(), ...$completion]);
            return $order;
        });
    }
}
