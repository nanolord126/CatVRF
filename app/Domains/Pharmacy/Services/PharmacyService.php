<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Services;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class PharmacyService
{
    public function __construct(
        private readonly \App\Services\FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
    ) {}

    /**
     * Создание заказа в аптеке с проверкой наличия и рецептов.
     */
    public function createOrder(int $pharmacyId, array $items, string $correlationId): PharmacyOrder
    {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'pharmacy_order',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($pharmacyId, $items, $correlationId): PharmacyOrder {
            $pharmacy = Pharmacy::findOrFail($pharmacyId);
            $totalAmount = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $medication = Medication::lockForUpdate()->findOrFail($item['medication_id']);

                if ($medication->stock_quantity < $item['quantity']) {
                    throw new RuntimeException("Insufficient stock for {$medication->name}. Available: {$medication->stock_quantity}.");
                }

                if ($medication->requires_prescription) {
                    $this->validateActivePrescription($this->guard->id(), $medication->id);
                }

                $lineTotal = $medication->price * $item['quantity'];
                $totalAmount += $lineTotal;

                $orderItems[] = [
                    'medication_id' => $medication->id,
                    'quantity' => $item['quantity'],
                    'price_at_order' => $medication->price,
                    'line_total' => $lineTotal,
                    'correlation_id' => $correlationId,
                ];

                $medication->decrement('stock_quantity', $item['quantity']);
            }

            $order = PharmacyOrder::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $pharmacy->tenant_id,
                'user_id' => $this->guard->id(),
                'pharmacy_id' => $pharmacyId,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'idempotency_key' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'tags' => ['source' => 'platform'],
            ]);

            $order->items()->createMany($orderItems);

            $this->logger->info('Pharmacy order created', [
                'order_id' => $order->id,
                'order_uuid' => $order->uuid,
                'pharmacy_id' => $pharmacyId,
                'items_count' => count($orderItems),
                'total_amount' => $totalAmount,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Подтверждение готовности заказа к выдаче / доставке.
     */
    public function markReadyForPickup(int $orderId, string $correlationId): PharmacyOrder
    {
        return $this->db->transaction(function () use ($orderId, $correlationId): PharmacyOrder {
            $order = PharmacyOrder::lockForUpdate()->findOrFail($orderId);

            if ($order->status !== 'pending') {
                throw new RuntimeException("Order {$orderId} cannot be marked ready from status: {$order->status}.");
            }

            $order->update([
                'status' => 'ready_for_pickup',
                'ready_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Pharmacy order ready for pickup', [
                'order_id' => $order->id,
                'correlation_id' => $correlationId,
            ]);

            return $order->refresh();
        });
    }

    /**
     * Завершение заказа с выплатой аптеке.
     */
    public function completeOrder(int $orderId, string $correlationId): PharmacyOrder
    {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'pharmacy_order_complete',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($orderId, $correlationId): PharmacyOrder {
            $order = PharmacyOrder::with('pharmacy')->lockForUpdate()->findOrFail($orderId);

            if ($order->status !== 'ready_for_pickup') {
                throw new RuntimeException("Order {$orderId} is not ready for completion.");
            }

            $platformFee = (int) ($order->total_amount * 0.14);
            $payoutAmount = $order->total_amount - $platformFee;

            $this->wallet->credit(
                userId: $order->pharmacy->owner_id,
                amount: $payoutAmount,
                type: 'pharmacy_payout',
                reason: "Pharmacy order #{$order->id} completed",
                correlationId: $correlationId,
            );

            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
                'payout_amount' => $payoutAmount,
                'platform_fee' => $platformFee,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Pharmacy order completed with payout', [
                'order_id' => $order->id,
                'payout_amount' => $payoutAmount,
                'platform_fee' => $platformFee,
                'correlation_id' => $correlationId,
            ]);

            return $order->refresh();
        });
    }

    /**
     * Валидация рецепта для рецептурных препаратов.
     */
    private function validateActivePrescription(int $userId, int $medicationId): void
    {
        $hasPrescription = Prescription::where('user_id', $userId)
            ->where('status', 'verified')
            ->where('expires_at', '>=', now())
            ->exists();

        if (!$hasPrescription) {
            throw new RuntimeException("Valid prescription required for medication ID: {$medicationId}.");
        }
    }

    /**
     * Поиск лекарств по МНН или торговому названию.
     */
    public function searchMedications(string $query, int $pharmacyId): \Illuminate\Support\Collection
    {
        return Medication::where('pharmacy_id', $pharmacyId)
            ->where('stock_quantity', '>', 0)
            ->where(function ($q) use ($query): void {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('inn', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->get();
    }
}
