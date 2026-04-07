<?php declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\ConstructionMaterials\Services;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class ConstructionMaterialService
{

    private readonly string $correlationId;


    public function __construct(private readonly WalletService $walletService,
            private readonly FraudControlService $fraud,
            string $correlationId = '',
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function orderMaterial(
            int $materialId,
            int $quantity,
            string $deliveryAddress,
            int $userId,
            int $tenantId,
            ?string $correlationIdOverride = null
        ): MaterialOrder {

            $correlationId = $correlationIdOverride ?: Str::uuid()->toString();

            try {
                // Fraud check
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'material_order', amount: 0, correlationId: $correlationId ?? '');

                return $this->db->transaction(function () use ($materialId, $quantity, $deliveryAddress, $correlationId, $userId, $tenantId) {
                    // Lock material for update
                    $material = ConstructionMaterial::lockForUpdate()->find($materialId);

                    if (!$material) {
                        throw new \DomainException('Material not found', 404);
                    }

                    if ($material->current_stock < $quantity) {
                        throw new \DomainException('Insufficient stock. Available: ' . $material->current_stock, 422);
                    }

                    // Calculate prices
                    $unitPrice = $material->price;
                    $totalPrice = $unitPrice * $quantity;

                    // Create order
                    $order = MaterialOrder::create([
                        'tenant_id' => $tenantId,
                        'uuid' => Str::uuid(),
                        'correlation_id' => $correlationId,
                        'material_id' => $materialId,
                        'user_id' => $userId,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'status' => 'pending',
                        'delivery_address' => $deliveryAddress,
                    ]);

                    // Deduct from stock
                    $material->update([
                        'current_stock' => $material->current_stock - $quantity,
                    ]);

                    // Log audit
                    $this->logger->info('Construction material order created', [
                        'correlation_id' => $correlationId,
                        'order_id' => $order->id,
                        'material_id' => $materialId,
                        'quantity' => $quantity,
                        'total_price' => $totalPrice,
                        'user_id' => $userId,
                    ]);

                    // Invalidate cache
                    cache()->forget('material:' . $materialId);

                    return $order;
                });
            } catch (\Throwable $e) {
                $this->logger->error('Material order failed', [
                    'correlation_id' => $correlationId,
                    'material_id' => $materialId,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        public function deliverOrder(MaterialOrder $order, string $trackingNumber = null): void
        {

            $correlationId = $order->correlation_id ?? Str::uuid()->toString();

            try {
                $order->update([
                    'status' => 'delivered',
                    'tracking_number' => $trackingNumber,
                    'delivery_date' => Carbon::now(),
                ]);

                $this->logger->info('Material order delivered', [
                    'correlation_id' => $correlationId,
                    'order_id' => $order->id,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Delivery failed', [
                    'correlation_id' => $correlationId,
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        public function cancelOrder(MaterialOrder $order): void
        {

            $correlationId = $order->correlation_id ?? Str::uuid()->toString();

            try {
                $this->db->transaction(function () use ($order) {
                    $material = $order->material;

                    if ($material) {
                        $material->update([
                            'current_stock' => $material->current_stock + $order->quantity,
                        ]);
                    }

                    $order->update(['status' => 'cancelled']);

                    cache()->forget('material:' . $order->material_id);
                });

                $this->logger->info('Material order cancelled', [
                    'correlation_id' => $correlationId,
                    'order_id' => $order->id,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Cancellation failed', [
                    'correlation_id' => $correlationId,
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        public function getMaterialsLowStock(): iterable
        {

            return ConstructionMaterial::where('current_stock', '<=', $this->db->raw('min_stock_threshold'))
                ->get();
        }

        public function checkMaterialAvailability(int $materialId, int $quantity): bool
        {

            $material = ConstructionMaterial::find($materialId);

            return $material && $material->current_stock >= $quantity;
        }
}
