<?php declare(strict_types=1);

namespace App\Domains\Logistics\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class ShipmentService
{

    public function __construct(private readonly FraudControlService $fraud,
            private readonly TrackingService $trackingService,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function createShipment(
            int $tenantId,
            int $courierServiceId,
            int $customerId,
            string $originAddress,
            string $destinationAddress,
            float $weight,
            float $declaredValue,
            float $shippingCost,
            string $correlationId
    ): Shipment {

            return $this->db->transaction(function () use (
                $tenantId,
                $courierServiceId,
                $customerId,
                $originAddress,
                $destinationAddress,
                $weight,
                $declaredValue,
                $shippingCost,
                $correlationId
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

                $this->logger->info('Shipment created', [
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

                    $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
            $this->db->transaction(function () use ($shipment, $reason, $correlationId) {
                $shipment->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                    'cancelled_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Shipment cancelled', [
                    'shipment_id' => $shipment->id,
                    'tenant_id' => $shipment->tenant_id,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        public function updateShipmentStatus(Shipment $shipment, string $status, string $correlationId): void
        {

                    $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
            $this->db->transaction(function () use ($shipment, $status, $correlationId) {
                $shipment->update([
                    'status' => $status,
                    'correlation_id' => $correlationId,
                ]);

                if ($status === 'picked_up') {
                    $shipment->update(['picked_up_at' => now()]);
                } elseif ($status === 'delivered') {
                    $shipment->update(['delivered_at' => now()]);
                }

                $this->logger->info('Shipment status updated', [
                    'shipment_id' => $shipment->id,
                    'status' => $status,
                    'correlation_id' => $correlationId,
                ]);
            });
        }
}
