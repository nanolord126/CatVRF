<?php declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\ConstructionMaterials\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class MaterialService
{

    private readonly string $correlationId;


    public function __construct(private readonly FraudControlService $fraud,
            string $correlationId = '',
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {
            $this->correlationId = $correlationId ?: Str::uuid()->toString();
        }

        public function orderMaterial(int $materialId, int $quantity, array $data, int $userId, int $tenantId): MaterialOrder
        {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($materialId, $quantity, $data, $userId, $tenantId) {
                $material = ConstructionMaterial::lockForUpdate()->find($materialId);

                if (!$material || $material->current_stock < $quantity) {
                    throw new \RuntimeException('Insufficient stock');
                }

                $order = MaterialOrder::create([
                    'tenant_id' => $tenantId,
                    'uuid' => Str::uuid(),
                    'correlation_id' => $this->correlationId,
                    'material_id' => $materialId,
                    'user_id' => $userId,
                    'quantity' => $quantity,
                    'total_price' => $material->price * $quantity,
                    'status' => 'pending',
                    'delivery_address' => $data['address'] ?? '',
                ]);

                $this->logger->info('Material order created', [
                    'correlation_id' => $this->correlationId,
                    'order_id' => $order->id,
                    'material_id' => $materialId,
                    'quantity' => $quantity,
                ]);

                return $order;
            });
        }

        public function deliverOrder(MaterialOrder $order): void
        {

            $order->update(['status' => 'delivered']);

            $this->logger->info('Material order delivered', [
                'correlation_id' => $this->correlationId,
                'order_id' => $order->id,
            ]);
        }
}
