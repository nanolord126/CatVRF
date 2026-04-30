<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Ramsey\Uuid\UuidInterface;
use Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use Modules\Payment\Domain\Entities\Payout;
use Modules\Payment\Domain\Repositories\PayoutRepositoryInterface;
use Modules\Payment\Domain\ValueObjects\PaymentProvider;
use Psr\Log\LoggerInterface;

/**
 * Payout Service - управление выплатами продавцам.
 */
final readonly class PayoutService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
        private readonly PayoutRepositoryInterface $payoutRepository,
        private readonly UuidInterface $uuid,
    ) {}

    public function createPayout(
        int $sellerId,
        int $paymentRecordId,
        int $amountKopecks,
        PaymentProvider $providerCode,
        string $correlationId,
        ?array $metadata = null,
    ): Payout {
        $payout = $this->payoutRepository->create([
            'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 0,
            'business_group_id' => null,
            'seller_id' => $sellerId,
            'payment_record_id' => $paymentRecordId,
            'uuid' => $this->uuid->toString(),
            'amount_kopecks' => $amountKopecks,
            'provider_code' => $providerCode->value,
            'status' => 'pending',
            'correlation_id' => $correlationId,
            'metadata' => $metadata,
        ]);

        $this->logger->info('Payout record created', [
            'payout_id' => $payout->id,
            'uuid' => $payout->uuid,
            'seller_id' => $sellerId,
            'amount_kopecks' => $amountKopecks,
            'correlation_id' => $correlationId,
        ]);

        $this->audit->log(
            action: 'payout_created',
            subjectType: Payout::class,
            subjectId: $payout->id,
            newValues: [
                'seller_id' => $sellerId,
                'amount_kopecks' => $amountKopecks,
                'provider_code' => $providerCode->value,
            ],
            correlationId: $correlationId,
        );

        return $payout;
    }

    public function updateStatus(
        int $payoutId,
        string $status,
        ?string $providerPayoutId = null,
        ?array $providerResponse = null,
    ): Payout {
        $updateData = ['status' => $status];

        if ($providerPayoutId) {
            $updateData['provider_payout_id'] = $providerPayoutId;
        }

        if ($providerResponse) {
            $updateData['provider_response'] = $providerResponse;
        }

        $payout = $this->payoutRepository->update($payoutId, $updateData);

        $this->logger->info('Payout status updated', [
            'payout_id' => $payoutId,
            'status' => $status,
            'provider_payout_id' => $providerPayoutId,
        ]);

        return $payout;
    }

    public function initiatePayout(
        int $payoutId,
        PaymentGatewayInterface $gateway,
        string $correlationId,
    ): void {
        $payout = $this->payoutRepository->findById($payoutId);

        if (!$payout || $payout->status !== 'pending') {
            $this->logger->warning('Payout is not in pending status', [
                'payout_id' => $payoutId,
                'current_status' => $payout?->status ?? 'not_found',
            ]);

            return;
        }

        $this->logger->info('Payout initiated via gateway', [
            'payout_id' => $payoutId,
            'provider' => $gateway->getProvider()->value,
            'correlation_id' => $correlationId,
        ]);
    }

    public function getSellerPayouts(int $sellerId, ?string $status = null): array
    {
        return $this->payoutRepository->findBySellerId($sellerId, $status);
    }

    public function getPaymentPayouts(int $paymentRecordId): array
    {
        return $this->payoutRepository->findByPaymentRecordId($paymentRecordId);
    }
}
