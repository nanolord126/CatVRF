<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\Security\IdempotencyService;
use App\Services\Wallet\WalletService;
use App\Domains\RealEstate\Models\PropertyTransaction;
use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\DTOs\EscrowDepositDto;
use App\Domains\RealEstate\DTOs\EscrowReleaseDto;
use App\Domains\RealEstate\DTOs\EscrowRefundDto;
use App\Domains\RealEstate\DTOs\SplitPaymentDto;
use App\Domains\RealEstate\Domain\Enums\TransactionStatusEnum;
use App\Domains\RealEstate\Domain\Events\EscrowDepositCreated;
use App\Domains\RealEstate\Domain\Events\EscrowFundsReleased;
use App\Domains\RealEstate\Domain\Events\EscrowFundsRefunded;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

final readonly class RealEstateEscrowWalletService
{
    private const ESCROW_HOLD_DURATION_MINUTES = 30;
    private const COMMISSION_RATE_B2C = 0.14;
    private const COMMISSION_RATE_B2B = 0.10;
    private const MINIMUM_ESCROW_AMOUNT = 10000.00;
    private const MAXIMUM_ESCROW_AMOUNT = 50000000.00;

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private IdempotencyService $idempotency,
        private WalletService $wallet,
    ) {}

    public function createEscrowDeposit(EscrowDepositDto $dto): PropertyTransaction
    {
        $this->fraud->check(
            userId: $dto->buyerId,
            operationType: 'escrow_deposit',
            amount: (int) $dto->amount,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $dto->correlationId,
        );
        $this->validateEscrowAmount($dto->amount);
        $this->validatePropertyEligibility($dto->propertyId);

        if ($dto->idempotencyKey !== null) {
            $existing = $this->idempotency->checkAndLock($dto->idempotencyKey, 'escrow_deposit');
            if ($existing !== null) {
                return PropertyTransaction::findOrFail($existing['transaction_id']);
            }
        }

        return DB::transaction(function () use ($dto) {
            $transaction = PropertyTransaction::create([
                'tenant_id' => $dto->tenantId,
                'business_group_id' => $dto->businessGroupId,
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => $dto->correlationId,
                'property_id' => $dto->propertyId,
                'buyer_id' => $dto->buyerId,
                'seller_id' => $dto->sellerId,
                'agent_id' => $dto->agentId,
                'amount' => $dto->amount,
                'currency' => $dto->currency,
                'status' => TransactionStatusEnum::ESCROW_PENDING->value,
                'escrow_hold_until' => now()->addMinutes(self::ESCROW_HOLD_DURATION_MINUTES),
                'is_b2b' => $dto->isB2b,
                'commission_rate' => $dto->isB2b ? self::COMMISSION_RATE_B2B : self::COMMISSION_RATE_B2C,
                'commission_amount' => $dto->amount * ($dto->isB2b ? self::COMMISSION_RATE_B2B : self::COMMISSION_RATE_B2C),
                'split_config' => $dto->splitConfig,
                'metadata' => [
                    'payment_method' => $dto->paymentMethod,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ],
                'tags' => array_merge($dto->tags ?? [], ['escrow', 'deposit']),
            ]);

            $this->wallet->hold(
                tenantId: 0,
                amount: (int) $dto->amount,
                reason: $transaction->uuid,
                correlationId: $dto->correlationId,
                walletId: $dto->buyerWalletId,
            );

            Log::channel('audit')->info('Escrow deposit created', [
                'transaction_id' => $transaction->id,
                'transaction_uuid' => $transaction->uuid,
                'property_id' => $dto->propertyId,
                'amount' => $dto->amount,
                'correlation_id' => $dto->correlationId,
                'tenant_id' => $dto->tenantId,
            ]);

            // CRM sync would be handled by event listeners

            event(new EscrowDepositCreated($transaction, $dto->correlationId));

            if ($dto->idempotencyKey !== null) {
                $this->idempotency->store($dto->idempotencyKey, 'escrow_deposit', [
                    'transaction_id' => $transaction->id,
                ]);
            }

            return $transaction;
        });
    }

    public function releaseEscrowFunds(EscrowReleaseDto $dto): PropertyTransaction
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'escrow_release',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $dto->correlationId,
        );

        $transaction = PropertyTransaction::where('uuid', $dto->transactionUuid)
            ->where('tenant_id', $dto->tenantId)
            ->lockForUpdate()
            ->firstOrFail();

        $this->validateEscrowRelease($transaction);

        return DB::transaction(function () use ($dto, $transaction) {
            $transaction->update([
                'status' => TransactionStatusEnum::ESCROW_RELEASED->value,
                'released_at' => now(),
                'release_reason' => $dto->reason,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'released_by' => $dto->releasedBy,
                    'release_notes' => $dto->releaseNotes,
                ]),
            ]);

            $this->wallet->releaseHold(
                $transaction->buyer->wallet_id,
                $transaction->uuid,
                $dto->correlationId,
            );

            $this->executeSplitPayment($transaction, $dto->correlationId);

            Log::channel('audit')->info('Escrow funds released', [
                'transaction_id' => $transaction->id,
                'transaction_uuid' => $transaction->uuid,
                'property_id' => $transaction->property_id,
                'amount' => $transaction->amount,
                'correlation_id' => $dto->correlationId,
                'tenant_id' => $dto->tenantId,
            ]);

            // CRM sync would be handled by event listeners

            event(new EscrowFundsReleased($transaction, $dto->correlationId));

            return $transaction->fresh();
        });
    }

    public function refundEscrowFunds(EscrowRefundDto $dto): PropertyTransaction
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'escrow_refund',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $dto->correlationId,
        );

        $transaction = PropertyTransaction::where('uuid', $dto->transactionUuid)
            ->where('tenant_id', $dto->tenantId)
            ->lockForUpdate()
            ->firstOrFail();

        $this->validateEscrowRefund($transaction, $dto->reason);

        return DB::transaction(function () use ($dto, $transaction) {
            $transaction->update([
                'status' => TransactionStatusEnum::ESCROW_REFUNDED->value,
                'refunded_at' => now(),
                'refund_reason' => $dto->reason,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'refunded_by' => $dto->refundedBy,
                    'refund_notes' => $dto->refundNotes,
                ]),
            ]);

            $this->wallet->release(
                tenantId: 0,
                amount: (int) $transaction->amount,
                correlationId: $dto->correlationId,
                walletId: $transaction->buyer->wallet_id ?? 0,
            );

            // Payment refund would be handled by PaymentService via event listener

            Log::channel('audit')->info('Escrow funds refunded', [
                'transaction_id' => $transaction->id,
                'transaction_uuid' => $transaction->uuid,
                'property_id' => $transaction->property_id,
                'amount' => $transaction->amount,
                'reason' => $dto->reason,
                'correlation_id' => $dto->correlationId,
                'tenant_id' => $dto->tenantId,
            ]);

            // CRM sync would be handled by event listeners

            event(new EscrowFundsRefunded($transaction, $dto->correlationId));

            return $transaction->fresh();
        });
    }

    public function executeSplitPayment(PropertyTransaction $transaction, string $correlationId): void
    {
        $splitConfig = $transaction->split_config ?? [];
        $totalAmount = $transaction->amount;
        $commissionAmount = $transaction->commission_amount;
        $netAmount = $totalAmount - $commissionAmount;

        $sellerShare = $netAmount * ($splitConfig['seller_share_percent'] ?? 0.85);
        $agentShare = $netAmount * ($splitConfig['agent_share_percent'] ?? 0.15);

        $this->wallet->credit(
            tenantId: 0,
            amount: (int) $sellerShare,
            type: 'escrow_release',
            sourceId: $transaction->id,
            correlationId: $correlationId,
            reason: 'seller_share',
            sourceType: 'property_transaction',
            walletId: $transaction->seller->wallet_id ?? 0,
        );

        if ($transaction->agent_id !== null && $agentShare > 0) {
            $this->wallet->credit(
                tenantId: 0,
                amount: (int) $agentShare,
                type: 'escrow_release',
                sourceId: $transaction->id,
                correlationId: $correlationId,
                reason: 'agent_share',
                sourceType: 'property_transaction',
                walletId: $transaction->agent->wallet_id ?? 0,
            );
        }

        $platformWalletId = (int) config('real_estate.platform_wallet_id', 1);
        $this->wallet->credit(
            tenantId: 0,
            amount: (int) $commissionAmount,
            type: 'commission',
            sourceId: $transaction->id,
            correlationId: $correlationId,
            reason: 'platform_commission',
            sourceType: 'property_transaction',
            walletId: $platformWalletId,
        );
    }

    public function calculateSplitPayment(SplitPaymentDto $dto): array
    {
        $totalAmount = $dto->amount;
        $commissionRate = $dto->isB2b ? self::COMMISSION_RATE_B2B : self::COMMISSION_RATE_B2C;
        $commissionAmount = $totalAmount * $commissionRate;
        $netAmount = $totalAmount - $commissionAmount;

        $sellerShare = $netAmount * ($dto->sellerSharePercent / 100);
        $agentShare = $netAmount * ($dto->agentSharePercent / 100);

        return [
            'total_amount' => $totalAmount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'net_amount' => $netAmount,
            'seller_share' => $sellerShare,
            'agent_share' => $agentShare,
            'currency' => $dto->currency,
        ];
    }

    public function getEscrowStatus(string $transactionUuid, int $tenantId): array
    {
        $transaction = PropertyTransaction::where('uuid', $transactionUuid)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $buyerWalletId = $transaction->buyer->wallet_id ?? 0;
        $buyerWallet = $this->wallet->getBalance($buyerWalletId);
        $holdAmount = 0; // Would need to implement getHoldAmount in WalletService

        return [
            'transaction_uuid' => $transaction->uuid,
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'escrow_hold_until' => $transaction->escrow_hold_until,
            'is_hold_active' => $transaction->escrow_hold_until->isFuture(),
            'buyer_balance' => $buyerWallet,
            'hold_amount' => $holdAmount,
            'commission_amount' => $transaction->commission_amount,
            'split_config' => $transaction->split_config,
            'created_at' => $transaction->created_at,
            'updated_at' => $transaction->updated_at,
        ];
    }

    private function validateEscrowAmount(float $amount): void
    {
        if ($amount < self::MINIMUM_ESCROW_AMOUNT) {
            throw new Exception(
                sprintf('Minimum escrow amount is %s', number_format(self::MINIMUM_ESCROW_AMOUNT, 2))
            );
        }

        if ($amount > self::MAXIMUM_ESCROW_AMOUNT) {
            throw new Exception(
                sprintf('Maximum escrow amount is %s', number_format(self::MAXIMUM_ESCROW_AMOUNT, 2))
            );
        }
    }

    private function validatePropertyEligibility(int $propertyId): void
    {
        $property = Property::where('id', $propertyId)
            ->where('status', 'available')
            ->first();

        if ($property === null) {
            throw new Exception('Property is not available for escrow transaction');
        }
    }

    private function validateEscrowRelease(PropertyTransaction $transaction): void
    {
        if ($transaction->status !== TransactionStatusEnum::ESCROW_PENDING->value) {
            throw new Exception('Transaction is not in escrow pending state');
        }

        if ($transaction->escrow_hold_until->isPast()) {
            throw new Exception('Escrow hold period has expired');
        }
    }

    private function validateEscrowRefund(PropertyTransaction $transaction, string $reason): void
    {
        if ($transaction->status !== TransactionStatusEnum::ESCROW_PENDING->value) {
            throw new Exception('Transaction is not in escrow pending state');
        }

        $validReasons = ['buyer_cancellation', 'seller_rejection', 'fraud_detected', 'property_unavailable', 'mutual_agreement'];
        if (!in_array($reason, $validReasons, true)) {
            throw new Exception('Invalid refund reason');
        }
    }
}
