<?php declare(strict_types=1);

namespace App\Domains\Electronics\Services;

use App\Domains\Electronics\DTOs\SplitPaymentRequestDto;
use App\Domains\Electronics\DTOs\SplitPaymentResponseDto;
use App\Domains\Electronics\Events\SplitPaymentCompletedEvent;
use App\Domains\Electronics\Events\EscrowReleasedEvent;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Services\PaymentService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class ElectronicsWalletService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private PaymentService $payment,
        private Cache $cache,
        private DatabaseManager $db,
        private LoggerInterface $logger,
    ) {
    }

    public function processSplitPayment(SplitPaymentRequestDto $dto): SplitPaymentResponseDto
    {
        $correlationId = $dto->correlationId;
        $paymentId = (string) Str::uuid();

        $this->fraud->check(
            userId: $dto->userId,
            operationType: 'electronics_split_payment',
            amount: $dto->totalAmountKopecks / 100,
            correlationId: $correlationId
        );

        if (!$dto->validatePaymentSources()) {
            return new SplitPaymentResponseDto(
                success: false,
                correlationId: $correlationId,
                paymentId: $paymentId,
                paymentResults: [],
                totalAmountKopecks: $dto->totalAmountKopecks,
                escrowEnabled: $dto->useEscrow,
                escrowReleaseDate: null,
                metadata: $dto->metadata,
                failureReason: 'Payment sources total does not match order total',
            );
        }

        $cacheKey = sprintf(
            'split_payment:%s:%s',
            $dto->orderId,
            $dto->idempotencyKey ?? $paymentId
        );

        $cachedResult = $this->cache->get($cacheKey);
        if ($cachedResult !== null) {
            $this->logger->info('Split payment cache hit', [
                'order_id' => $dto->orderId,
                'correlation_id' => $correlationId,
            ]);

            return SplitPaymentResponseDto::fromArray($cachedResult);
        }

        return $this->db->transaction(function () use ($dto, $correlationId, $paymentId, $cacheKey) {
            $paymentResults = [];
            $allSuccessful = true;
            $totalProcessed = 0;

            foreach ($dto->paymentSources as $index => $source) {
                $result = $this->processPaymentSource($dto, $source, $index, $paymentId);
                $paymentResults[] = $result;

                if ($result['status'] !== 'completed') {
                    $allSuccessful = false;
                }

                $totalProcessed += $result['amount_kopecks'];
            }

            if (!$allSuccessful) {
                $this->rollbackPayments($paymentResults, $correlationId);

                return new SplitPaymentResponseDto(
                    success: false,
                    correlationId: $correlationId,
                    paymentId: $paymentId,
                    paymentResults: $paymentResults,
                    totalAmountKopecks: $totalProcessed,
                    escrowEnabled: false,
                    escrowReleaseDate: null,
                    metadata: $dto->metadata,
                    failureReason: 'One or more payment sources failed',
                );
            }

            $escrowReleaseDate = null;
            if ($dto->useEscrow) {
                $escrowReleaseDate = now()->addDays($dto->escrowReleaseDays)->toIso8601String();
                $this->createEscrowHold($dto, $paymentId, $escrowReleaseDate, $correlationId);
            } else {
                $this->releaseFundsToMerchant($dto, $paymentId, $correlationId);
            }

            $this->saveSplitPaymentRecord($dto, $paymentResults, $paymentId, $escrowReleaseDate, $correlationId);

            $response = new SplitPaymentResponseDto(
                success: true,
                correlationId: $correlationId,
                paymentId: $paymentId,
                paymentResults: $paymentResults,
                totalAmountKopecks: $dto->totalAmountKopecks,
                escrowEnabled: $dto->useEscrow,
                escrowReleaseDate: $escrowReleaseDate,
                metadata: $dto->metadata,
            );

            $this->cache->put($cacheKey, $response->toArray(), now()->addHours(24));

            event(new SplitPaymentCompletedEvent($dto, $response, $correlationId));

            Log::channel('audit')->info('Electronics split payment completed', [
                'order_id' => $dto->orderId,
                'user_id' => $dto->userId,
                'payment_id' => $paymentId,
                'correlation_id' => $correlationId,
                'total_amount' => $dto->totalAmountKopecks,
                'escrow_enabled' => $dto->useEscrow,
            ]);

            return $response;
        });
    }

    private function processPaymentSource(
        SplitPaymentRequestDto $dto,
        array $source,
        int $index,
        string $paymentId
    ): array {
        $sourceType = $source['source'];
        $amount = $source['amount_kopecks'];
        $metadata = $source['metadata'] ?? [];

        try {
            $result = match ($sourceType) {
                'wallet' => $this->processWalletPayment($dto, $amount, $metadata, $paymentId),
                'card' => $this->processCardPayment($dto, $amount, $metadata, $paymentId),
                'bonus' => $this->processBonusPayment($dto, $amount, $metadata, $paymentId),
                'sbp' => $this->processSBPPayment($dto, $amount, $metadata, $paymentId),
                default => throw new \InvalidArgumentException("Unsupported payment source: {$sourceType}"),
            };

            return [
                'source' => $sourceType,
                'amount_kopecks' => $amount,
                'status' => 'completed',
                'transaction_id' => $result['transaction_id'],
                'metadata' => array_merge($metadata, $result['metadata'] ?? []),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Payment source processing failed', [
                'source' => $sourceType,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'correlation_id' => $dto->correlationId,
            ]);

            return [
                'source' => $sourceType,
                'amount_kopecks' => $amount,
                'status' => 'failed',
                'transaction_id' => null,
                'error' => $e->getMessage(),
                'metadata' => $metadata,
            ];
        }
    }

    private function processWalletPayment(
        SplitPaymentRequestDto $dto,
        int $amount,
        array $metadata,
        string $paymentId
    ): array {
        $walletId = $this->getUserWalletId($dto->userId);

        $this->wallet->debit(
            walletId: $walletId,
            amountKopecks: $amount,
            type: 'payment',
            description: "Split payment for order {$dto->orderId}",
            metadata: array_merge($metadata, [
                'order_id' => $dto->orderId,
                'payment_id' => $paymentId,
                'correlation_id' => $dto->correlationId,
            ])
        );

        return [
            'transaction_id' => "wallet_{$paymentId}_{$walletId}",
            'metadata' => ['wallet_id' => $walletId],
        ];
    }

    private function processCardPayment(
        SplitPaymentRequestDto $dto,
        int $amount,
        array $metadata,
        string $paymentId
    ): array {
        $paymentResult = $this->payment->initPayment(
            userId: $dto->userId,
            amountKopecks: $amount,
            paymentMethod: 'card',
            description: "Split payment for order {$dto->orderId}",
            metadata: array_merge($metadata, [
                'order_id' => $dto->orderId,
                'payment_id' => $paymentId,
                'correlation_id' => $dto->correlationId,
            ])
        );

        return [
            'transaction_id' => $paymentResult['transaction_id'],
            'metadata' => $paymentResult['metadata'] ?? [],
        ];
    }

    private function processBonusPayment(
        SplitPaymentRequestDto $dto,
        int $amount,
        array $metadata,
        string $paymentId
    ): array {
        $walletId = $this->getUserWalletId($dto->userId);

        $this->wallet->debit(
            walletId: $walletId,
            amountKopecks: $amount,
            type: 'bonus',
            description: "Bonus payment for order {$dto->orderId}",
            metadata: array_merge($metadata, [
                'order_id' => $dto->orderId,
                'payment_id' => $paymentId,
                'correlation_id' => $dto->correlationId,
            ])
        );

        return [
            'transaction_id' => "bonus_{$paymentId}_{$walletId}",
            'metadata' => ['wallet_id' => $walletId],
        ];
    }

    private function processSBPPayment(
        SplitPaymentRequestDto $dto,
        int $amount,
        array $metadata,
        string $paymentId
    ): array {
        $paymentResult = $this->payment->initPayment(
            userId: $dto->userId,
            amountKopecks: $amount,
            paymentMethod: 'sbp',
            description: "SBP payment for order {$dto->orderId}",
            metadata: array_merge($metadata, [
                'order_id' => $dto->orderId,
                'payment_id' => $paymentId,
                'correlation_id' => $dto->correlationId,
            ])
        );

        return [
            'transaction_id' => $paymentResult['transaction_id'],
            'metadata' => $paymentResult['metadata'] ?? [],
        ];
    }

    private function getUserWalletId(int $userId): int
    {
        $wallet = DB::table('wallets')
            ->where('user_id', $userId)
            ->where('tenant_id', tenant()->id)
            ->first();

        if (!$wallet) {
            $walletId = DB::table('wallets')->insertGetId([
                'user_id' => $userId,
                'tenant_id' => tenant()->id,
                'current_balance' => 0,
                'hold_amount' => 0,
                'correlation_id' => Str::uuid()->toString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $walletId;
        }

        return $wallet->id;
    }

    private function rollbackPayments(array $paymentResults, string $correlationId): void
    {
        foreach ($paymentResults as $result) {
            if ($result['status'] === 'completed') {
                try {
                    $this->rollbackSinglePayment($result, $correlationId);
                } catch (\Throwable $e) {
                    $this->logger->error('Payment rollback failed', [
                        'transaction_id' => $result['transaction_id'],
                        'error' => $e->getMessage(),
                        'correlation_id' => $correlationId,
                    ]);
                }
            }
        }
    }

    private function rollbackSinglePayment(array $paymentResult, string $correlationId): void
    {
        $source = $paymentResult['source'];
        $transactionId = $paymentResult['transaction_id'];

        match ($source) {
            'wallet', 'bonus' => $this->wallet->credit(
                walletId: $paymentResult['metadata']['wallet_id'],
                amountKopecks: $paymentResult['amount_kopecks'],
                type: 'refund',
                description: "Rollback for failed split payment",
                metadata: ['original_transaction_id' => $transactionId, 'correlation_id' => $correlationId]
            ),
            'card', 'sbp' => $this->payment->refund(
                transactionId: $transactionId,
                amountKopecks: $paymentResult['amount_kopecks'],
                reason: 'Split payment rollback',
                metadata: ['correlation_id' => $correlationId]
            ),
            default => null,
        };
    }

    private function createEscrowHold(
        SplitPaymentRequestDto $dto,
        string $paymentId,
        string $escrowReleaseDate,
        string $correlationId
    ): void {
        DB::table('electronics_escrow_holds')->insert([
            'order_id' => $dto->orderId,
            'user_id' => $dto->userId,
            'tenant_id' => tenant()->id,
            'payment_id' => $paymentId,
            'amount_kopecks' => $dto->totalAmountKopecks,
            'status' => 'held',
            'release_date' => $escrowReleaseDate,
            'correlation_id' => $correlationId,
            'metadata' => json_encode($dto->metadata),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->cache->put(
            "escrow_release:{$paymentId}",
            [
                'order_id' => $dto->orderId,
                'user_id' => $dto->userId,
                'amount_kopecks' => $dto->totalAmountKopecks,
                'release_date' => $escrowReleaseDate,
            ],
            \Carbon\Carbon::parse($escrowReleaseDate)
        );
    }

    private function releaseFundsToMerchant(
        SplitPaymentRequestDto $dto,
        string $paymentId,
        string $correlationId
    ): void {
        $merchantId = $this->getOrderMerchantId($dto->orderId);
        $merchantWalletId = $this->getMerchantWalletId($merchantId);

        $commissionRate = $this->getCommissionRate($dto->userId, $dto->totalAmountKopecks);
        $commissionAmount = (int) ($dto->totalAmountKopecks * $commissionRate);
        $merchantAmount = $dto->totalAmountKopecks - $commissionAmount;

        $this->wallet->credit(
            walletId: $merchantWalletId,
            amountKopecks: $merchantAmount,
            type: 'payout',
            description: "Payment for order {$dto->orderId}",
            metadata: [
                'order_id' => $dto->orderId,
                'payment_id' => $paymentId,
                'commission_rate' => $commissionRate,
                'correlation_id' => $correlationId,
            ]
        );

        $platformWalletId = $this->getPlatformWalletId();
        $this->wallet->credit(
            walletId: $platformWalletId,
            amountKopecks: $commissionAmount,
            type: 'commission',
            description: "Commission from order {$dto->orderId}",
            metadata: [
                'order_id' => $dto->orderId,
                'payment_id' => $paymentId,
                'commission_rate' => $commissionRate,
                'correlation_id' => $correlationId,
            ]
        );
    }

    public function releaseEscrow(string $paymentId, string $reason, string $correlationId): bool
    {
        return $this->db->transaction(function () use ($paymentId, $reason, $correlationId) {
            $escrowHold = DB::table('electronics_escrow_holds')
                ->where('payment_id', $paymentId)
                ->where('status', 'held')
                ->lockForUpdate()
                ->first();

            if (!$escrowHold) {
                throw new \RuntimeException('Escrow hold not found or already released');
            }

            $dto = new SplitPaymentRequestDto(
                orderId: $escrowHold->order_id,
                userId: $escrowHold->user_id,
                correlationId: $correlationId,
                totalAmountKopecks: $escrowHold->amount_kopecks,
                paymentSources: [],
                useEscrow: false,
                escrowReleaseDays: 0,
                metadata: json_decode($escrowHold->metadata, true),
            );

            $this->releaseFundsToMerchant($dto, $paymentId, $correlationId);

            DB::table('electronics_escrow_holds')
                ->where('id', $escrowHold->id)
                ->update([
                    'status' => 'released',
                    'release_reason' => $reason,
                    'released_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->cache->forget("escrow_release:{$paymentId}");

            event(new EscrowReleasedEvent($escrowHold, $reason, $correlationId));

            Log::channel('audit')->info('Escrow released', [
                'payment_id' => $paymentId,
                'order_id' => $escrowHold->order_id,
                'amount' => $escrowHold->amount_kopecks,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            return true;
        });
    }

    private function getOrderMerchantId(int $orderId): int
    {
        $order = DB::table('orders')->where('id', $orderId)->first();
        return $order ? (int) $order->merchant_id : 0;
    }

    private function getMerchantWalletId(int $merchantId): int
    {
        $wallet = DB::table('wallets')
            ->where('merchant_id', $merchantId)
            ->where('tenant_id', tenant()->id)
            ->first();

        return $wallet ? $wallet->id : $this->createMerchantWallet($merchantId);
    }

    private function createMerchantWallet(int $merchantId): int
    {
        return DB::table('wallets')->insertGetId([
            'merchant_id' => $merchantId,
            'tenant_id' => tenant()->id,
            'current_balance' => 0,
            'hold_amount' => 0,
            'correlation_id' => Str::uuid()->toString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getPlatformWalletId(): int
    {
        $wallet = DB::table('wallets')
            ->where('tenant_id', tenant()->id)
            ->where('is_platform', true)
            ->first();

        return $wallet ? $wallet->id : $this->createPlatformWallet();
    }

    private function createPlatformWallet(): int
    {
        return DB::table('wallets')->insertGetId([
            'tenant_id' => tenant()->id,
            'is_platform' => true,
            'current_balance' => 0,
            'hold_amount' => 0,
            'correlation_id' => Str::uuid()->toString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getCommissionRate(int $userId, int $amountKopecks): float
    {
        $isB2B = DB::table('business_groups')
            ->where('owner_id', $userId)
            ->exists();

        if ($isB2B) {
            $amountRubles = $amountKopecks / 100;
            return match (true) {
                $amountRubles >= 1000000 => 0.08,
                $amountRubles >= 500000 => 0.10,
                default => 0.12,
            };
        }

        return 0.14;
    }

    private function saveSplitPaymentRecord(
        SplitPaymentRequestDto $dto,
        array $paymentResults,
        string $paymentId,
        ?string $escrowReleaseDate,
        string $correlationId
    ): void {
        DB::table('electronics_split_payments')->insert([
            'order_id' => $dto->orderId,
            'user_id' => $dto->userId,
            'tenant_id' => tenant()->id,
            'payment_id' => $paymentId,
            'total_amount_kopecks' => $dto->totalAmountKopecks,
            'payment_sources' => json_encode($paymentResults),
            'escrow_enabled' => $dto->useEscrow,
            'escrow_release_date' => $escrowReleaseDate,
            'correlation_id' => $correlationId,
            'metadata' => json_encode($dto->metadata),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
