<?php declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Logistics\Models\Courier;
use Illuminate\Support\Facades\DB;

final class CourierService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,)
    {
    }

    /**
     * Получить ближайшего доступного курьера в зоне
     */
    public function findNearestCourier(string $zoneId, string $correlationId): ?Courier
    {
        try {
            $courier = Courier::where('zone_id', $zoneId)
                ->where('is_available', true)
                ->where('current_load', '<', 10)
                ->orderBy('current_load', 'asc')
                ->first();

            if ($courier) {
                Log::channel('audit')->info('Nearest courier found', [
                    'zone_id' => $zoneId,
                    'courier_id' => $courier->id,
                    'load' => $courier->current_load,
                    'correlation_id' => $correlationId,
                ]);
            }

            return $courier;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Courier search failed', [
                'zone_id' => $zoneId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Зарезервировать курьера для доставки
     */
    public function assignCourier(int $courierId, int $deliveryId, string $correlationId): bool
    {


        try {
                        $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
            DB::transaction(function () use ($courierId, $deliveryId, $correlationId) {
                $courier = Courier::lockForUpdate()->findOrFail($courierId);
                $courier->increment('current_load');

                DB::table('delivery_orders')
                    ->where('id', $deliveryId)
                    ->update(['courier_id' => $courierId, 'status' => 'assigned']);

                Log::channel('audit')->info('Courier assigned', [
                    'courier_id' => $courierId,
                    'delivery_id' => $deliveryId,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Courier assignment failed', [
                'courier_id' => $courierId,
                'delivery_id' => $deliveryId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Завершить доставку
     */
    public function completeDelivery(int $courierId, int $deliveryId, string $correlationId): bool
    {


        try {
                        $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
            DB::transaction(function () use ($courierId, $deliveryId, $correlationId) {
                $courier = Courier::lockForUpdate()->findOrFail($courierId);
                $courier->decrement('current_load');

                DB::table('delivery_orders')
                    ->where('id', $deliveryId)
                    ->update(['status' => 'completed', 'completed_at' => now()]);

                Log::channel('audit')->info('Delivery completed', [
                    'courier_id' => $courierId,
                    'delivery_id' => $deliveryId,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Delivery completion failed', [
                'courier_id' => $courierId,
                'delivery_id' => $deliveryId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
