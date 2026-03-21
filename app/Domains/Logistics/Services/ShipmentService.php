<?php declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use App\Domains\Logistics\Events\ShipmentCreated;
use App\Domains\Logistics\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ShipmentService
{
    public function __construct(
        private readonly TrackingService $trackingService,
    ) {}

    public function createShipment(
        int $tenantId,
        int $courierServiceId,
        int $customerId,
        string $originAddress,
        string $destinationAddress,
        float $weight,
        float $declaredValue,
        float $shippingCost,
        string $correlationId,
    ): Shipment {
        return DB::transaction(function () use (
            $tenantId,
            $courierServiceId,
            $customerId,
            $originAddress,
            $destinationAddress,
            $weight,
            $declaredValue,
            $shippingCost,
            $correlationId,
        ) {
            $commissionAmount = $shippingCost * 0.14;

            $shipment = Shipment::create([
                'tenant_id' => $tenantId,
                'courier_service_id' => $courierServiceId,
                'customer_id' => $customerId,
                'tracking_number' => Str::uuid(),
                'origin_address' => $originAddress,
                'destination_address' => $destinationAddress,
                'weight' => $weight,
                'declared_value' => $declaredValue,
                'shipping_cost' => $shippingCost,
                'commission_amount' => $commissionAmount,
                'status' => 'pending',
                'transaction_id' => Str::uuid(),
                'correlation_id' => $correlationId,
            ]);

            ShipmentCreated::dispatch($shipment, $correlationId);

            Log::channel('audit')->info('Shipment created', [
                'shipment_id' => $shipment->id,
                'tenant_id' => $tenantId,
                'customer_id' => $customerId,
                'shipping_cost' => $shippingCost,
                'commission_amount' => $commissionAmount,
                'correlation_id' => $correlationId,
            ]);

            return $shipment;
        });
    }

    public function cancelShipment(Shipment $shipment, string $reason, string $correlationId): void
    {
        DB::transaction(function () use ($shipment, $reason, $correlationId) {
            $shipment->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Shipment cancelled', [
                'shipment_id' => $shipment->id,
                'tenant_id' => $shipment->tenant_id,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function updateShipmentStatus(Shipment $shipment, string $status, string $correlationId): void
    {
        DB::transaction(function () use ($shipment, $status, $correlationId) {
            $shipment->update([
                'status' => $status,
                'correlation_id' => $correlationId,
            ]);

            if ($status === 'picked_up') {
                $shipment->update(['picked_up_at' => now()]);
            } elseif ($status === 'delivered') {
                $shipment->update(['delivered_at' => now()]);
            }

            Log::channel('audit')->info('Shipment status updated', [
                'shipment_id' => $shipment->id,
                'status' => $status,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
