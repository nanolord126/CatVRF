<?php declare(strict_types=1);

namespace App\Domains\Sports\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class PurchaseService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    public function createPurchase(
            int $studioId,
            int $memberId,
            ?int $membershipId,
            string $itemType,
            string $itemName,
            int $quantity,
            float $unitPrice,
            ?string $correlationId = null,
        ): Purchase {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in Sports', ['correlation_id' => $correlationId]);

            try {
                $correlationId = $correlationId ?? Str::uuid()->toString();

                $this->logger->info('Creating purchase', [
                    'studio_id' => $studioId,
                    'member_id' => $memberId,
                    'item_type' => $itemType,
                    'correlation_id' => $correlationId,
                ]);

                $purchase = $this->db->transaction(function () use (
                    $studioId,
                    $memberId,
                    $membershipId,
                    $itemType,
                    $itemName,
                    $quantity,
                    $unitPrice,
                    $correlationId) {
                    $subtotal = $unitPrice * $quantity;
                    $commissionAmount = ($subtotal * 14) / 100;
                    $totalAmount = $subtotal + $commissionAmount;

                    $purchase = Purchase::create([
                        'tenant_id' => tenant()?->id,
                        'studio_id' => $studioId,
                        'membership_id' => $membershipId,
                        'buyer_id' => $memberId,
                        'item_type' => $itemType,
                        'item_name' => $itemName,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                        'commission_amount' => $commissionAmount,
                        'total_amount' => $totalAmount,
                        'payment_status' => 'pending',
                        'purchase_status' => 'active',
                        'purchased_at' => now(),
                        'correlation_id' => $correlationId,
                    ]);

                    PurchaseCreated::dispatch($purchase, $correlationId);

                    return $purchase;
                });

                $this->logger->info('Purchase created successfully', [
                    'purchase_id' => $purchase->id,
                    'total_amount' => $purchase->total_amount,
                    'correlation_id' => $correlationId,
                ]);

                return $purchase;
            } catch (Throwable $e) {
                $this->logger->error('Failed to create purchase', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? null,
                ]);
                throw $e;
            }
        }

        public function confirmPayment(Purchase $purchase, string $transactionId, ?string $correlationId = null): void
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in Sports', ['correlation_id' => $correlationId]);

            try {
                $correlationId = $correlationId ?? Str::uuid()->toString();

                $this->logger->info('Confirming purchase payment', [
                    'purchase_id' => $purchase->id,
                    'transaction_id' => $transactionId,
                    'correlation_id' => $correlationId,
                ]);

                $purchase->update([
                    'payment_status' => 'paid',
                    'transaction_id' => $transactionId,
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Purchase payment confirmed', [
                    'purchase_id' => $purchase->id,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->error('Failed to confirm payment', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? null,
                ]);
                throw $e;
            }
        }

        public function refundPurchase(Purchase $purchase, string $reason = '', ?string $correlationId = null): void
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in Sports', ['correlation_id' => $correlationId]);

            try {
                $correlationId = $correlationId ?? Str::uuid()->toString();

                $this->logger->info('Refunding purchase', [
                    'purchase_id' => $purchase->id,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                $this->db->transaction(function () use ($purchase, $reason, $correlationId) {
                    $purchase->update([
                        'purchase_status' => 'cancelled',
                        'correlation_id' => $correlationId,
                    ]);

                    \App\Domains\Sports\Events\PurchaseRefunded::dispatch($purchase, $reason, $correlationId);
                });

                $this->logger->info('Purchase refunded', [
                    'purchase_id' => $purchase->id,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->error('Failed to refund purchase', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? null,
                ]);
                throw $e;
            }
        }
}
