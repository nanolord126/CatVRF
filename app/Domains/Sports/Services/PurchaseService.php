<?php declare(strict_types=1);

namespace App\Domains\Sports\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\FraudControlService;


use App\Domains\Sports\Models\Purchase;
use App\Domains\Sports\Models\Membership;
use App\Domains\Sports\Events\PurchaseCreated;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class PurchaseService
{
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
        Log::channel('audit')->info('Service method called in Sports', ['correlation_id' => $correlationId]);

        try {
            $correlationId = $correlationId ?? Str::uuid()->toString();

            Log::channel('audit')->info('Creating purchase', [
                'studio_id' => $studioId,
                'member_id' => $memberId,
                'item_type' => $itemType,
                'correlation_id' => $correlationId,
            ]);

            $purchase = DB::transaction(function () use (
                $studioId,
                $memberId,
                $membershipId,
                $itemType,
                $itemName,
                $quantity,
                $unitPrice,
                $correlationId,
            ) {
                $subtotal = $unitPrice * $quantity;
                $commissionAmount = ($subtotal * 14) / 100;
                $totalAmount = $subtotal + $commissionAmount;

                $purchase = Purchase::create([
                    'tenant_id' => tenant('id'),
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

            Log::channel('audit')->info('Purchase created successfully', [
                'purchase_id' => $purchase->id,
                'total_amount' => $purchase->total_amount,
                'correlation_id' => $correlationId,
            ]);

            return $purchase;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to create purchase', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId ?? null,
            ]);
            throw $e;
        }
    }

    public function confirmPayment(Purchase $purchase, string $transactionId, ?string $correlationId = null): void
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in Sports', ['correlation_id' => $correlationId]);

        try {
            $correlationId = $correlationId ?? Str::uuid()->toString();

            Log::channel('audit')->info('Confirming purchase payment', [
                'purchase_id' => $purchase->id,
                'transaction_id' => $transactionId,
                'correlation_id' => $correlationId,
            ]);

            $purchase->update([
                'payment_status' => 'paid',
                'transaction_id' => $transactionId,
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Purchase payment confirmed', [
                'purchase_id' => $purchase->id,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to confirm payment', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId ?? null,
            ]);
            throw $e;
        }
    }

    public function refundPurchase(Purchase $purchase, string $reason = '', ?string $correlationId = null): void
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in Sports', ['correlation_id' => $correlationId]);

        try {
            $correlationId = $correlationId ?? Str::uuid()->toString();

            Log::channel('audit')->info('Refunding purchase', [
                'purchase_id' => $purchase->id,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            DB::transaction(function () use ($purchase, $reason, $correlationId) {
                $purchase->update([
                    'purchase_status' => 'cancelled',
                    'correlation_id' => $correlationId,
                ]);

                \App\Domains\Sports\Events\PurchaseRefunded::dispatch($purchase, $reason, $correlationId);
            });

            Log::channel('audit')->info('Purchase refunded', [
                'purchase_id' => $purchase->id,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to refund purchase', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId ?? null,
            ]);
            throw $e;
        }
    }
}
