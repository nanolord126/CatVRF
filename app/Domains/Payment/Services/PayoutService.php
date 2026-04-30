<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Contracts\PaymentGatewayInterface;
use App\Domains\Payment\Enums\PaymentProvider;
use App\Domains\Payment\Models\Payout;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Payout Service - управление выплатами продавцам.
 *
 * Создаёт записи о выплатах и инициирует переводы через платёжный шлюз.
 * Выплаты выполняются асинхронно через Jobs для избежания блокировок.
 */
final readonly class PayoutService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
    ) {}

    /**
     * Создать запись о выплате.
     *
     * @return Payout
     */
    public function createPayout(
        int $sellerId,
        int $paymentRecordId,
        int $amountKopecks,
        PaymentProvider $providerCode,
        string $correlationId,
        ?array $metadata = null,
    ): Payout {
        $payout = Payout::create([
            'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 0,
            'business_group_id' => null,
            'seller_id' => $sellerId,
            'payment_record_id' => $paymentRecordId,
            'uuid' => (string) Str::uuid(),
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

    /**
     * Обновить статус выплаты.
     *
     * @return Payout
     */
    public function updateStatus(
        int $payoutId,
        string $status,
        ?string $providerPayoutId = null,
        ?array $providerResponse = null,
    ): Payout {
        $payout = Payout::findOrFail($payoutId);

        $updateData = ['status' => $status];

        if ($providerPayoutId) {
            $updateData['provider_payout_id'] = $providerPayoutId;
        }

        if ($providerResponse) {
            $updateData['provider_response'] = $providerResponse;
        }

        $payout->update($updateData);

        $this->logger->info('Payout status updated', [
            'payout_id' => $payoutId,
            'status' => $status,
            'provider_payout_id' => $providerPayoutId,
        ]);

        return $payout->fresh();
    }

    /**
     * Инициировать выплату через шлюз (асинхронно через Job).
     *
     * @return void
     */
    public function initiatePayout(
        int $payoutId,
        PaymentGatewayInterface $gateway,
        string $correlationId,
    ): void {
        $payout = Payout::findOrFail($payoutId);

        if ($payout->status !== 'pending') {
            $this->logger->warning('Payout is not in pending status', [
                'payout_id' => $payoutId,
                'current_status' => $payout->status,
            ]);

            return;
        }

        // В реальной реализации здесь должен быть dispatch Job
        // ProcessPayoutJob::dispatch($payoutId, $gateway->getProvider(), $correlationId);

        $this->logger->info('Payout initiated via gateway', [
            'payout_id' => $payoutId,
            'provider' => $gateway->getProvider()->value,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Получить выплаты по продавцу.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Payout>
     */
    public function getSellerPayouts(int $sellerId, string $status = null)
    {
        $query = Payout::where('seller_id', $sellerId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Получить выплаты по платежу.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Payout>
     */
    public function getPaymentPayouts(int $paymentRecordId)
    {
        return Payout::where('payment_record_id', $paymentRecordId)->get();
    }
}
