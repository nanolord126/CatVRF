<?php

declare(strict_types=1);

namespace Modules\Payments\Services;

use App\Models\Tenant;
use App\Domains\FraudML\DTOs\PaymentFraudMLDto;
use App\Domains\FraudML\Services\PaymentFraudMLService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Common\Services\AbstractTechnicalVerticalService;
use Modules\Payments\Gateways\PaymentGatewayInterface;
use App\Models\PaymentTransaction;
use DomainException;

final class PaymentsService extends AbstractTechnicalVerticalService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly IdempotencyService $idempotencyService,
        private readonly PaymentFraudMLService $fraudMl,
    ) {}

    public function initPayment(
        int $amount,
        string $currency,
        string $description,
        string $returnUrl,
        array $metadata = []
    ): PaymentTransaction {
        $this->logInfo('Initializing payment', compact('amount', 'currency', 'description'));

        // 0. FRAUD CHECK - ML-based
        try {
            $fraudDto = new PaymentFraudMLDto(
                tenant_id: $this->tenant->id,
                user_id: $this->userId ?? 0,
                operation_type: 'payment_init',
                amount_kopecks: $amount * 100,
                ip_address: request()->ip() ?? '127.0.0.1',
                device_fingerprint: request()->header('User-Agent') ?? 'unknown',
                correlation_id: $this->correlationId,
                idempotency_key: $this->idempotencyService->generateKey(compact('amount', 'currency', 'metadata')),
                vertical_code: $metadata['vertical_code'] ?? 'payment',
            );

            $fraudResult = $this->fraudMl->scorePayment($fraudDto);

            if ($fraudResult['decision'] === 'block') {
                Log::warning('Payment blocked by fraud detection', [
                    'tenant_id' => $this->tenant->id,
                    'fraud_score' => $fraudResult['score'],
                    'explanation' => $fraudResult['explanation'] ?? null,
                    'correlation_id' => $this->correlationId,
                ]);
                throw new DomainException('Payment blocked by fraud detection system');
            }
        } catch (DomainException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Fraud check failed', [
                'tenant_id' => $this->tenant->id,
                'error' => $e->getMessage(),
            ]);
            // Fail-open for reliability
        }

        // 1. Idempotency check
        $idempotencyKey = $this->idempotencyService->generateKey(compact('amount', 'currency', 'metadata'));
        if ($existingTransaction = $this->idempotencyService->check($idempotencyKey)) {
            $this->logInfo('Idempotent payment request detected', ['transaction_id' => $existingTransaction->id]);
            return $existingTransaction;
        }

        // 2. Start DB transaction
        return DB::transaction(function () use ($amount, $currency, $description, $returnUrl, $metadata, $idempotencyKey) {
            // 3. Create local transaction record
            $transaction = PaymentTransaction::create([
                'tenant_id' => $this->tenant->id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'pending',
                'correlation_id' => $this->correlationId,
                'metadata' => $metadata,
            ]);

            // 4. Call the gateway
            $gatewayResponse = $this->gateway
                ->forTenant($this->tenant)
                ->withCorrelationId($this->correlationId)
                ->init($transaction, $returnUrl);

            // 5. Update transaction with gateway info
            $transaction->update([
                'provider_payment_id' => $gatewayResponse->getProviderPaymentId(),
                'payment_url' => $gatewayResponse->getPaymentUrl(),
            ]);

            // 6. Record idempotency
            $this->idempotencyService->record($idempotencyKey, $transaction);

            $this->logInfo('Payment initialized successfully', ['transaction_id' => $transaction->id]);

            return $transaction;
        });
    }

    public function getStatus(string $transactionId): string
    {
        $transaction = $this->findTransaction($transactionId);
        $gatewayStatus = $this->gateway->getStatus($transaction->provider_payment_id);

        // Sync status if needed
        if ($transaction->status !== $gatewayStatus) {
            $transaction->update(['status' => $gatewayStatus]);
            $this->logInfo('Payment status synced', ['transaction_id' => $transaction->id, 'new_status' => $gatewayStatus]);
        }

        return $gatewayStatus;
    }

    public function handleWebhook(array $payload): void
    {
        $this->logInfo('Handling webhook', ['payload' => $payload]);

        if (!$this->gateway->validateWebhook($payload)) {
            $this->logError('Invalid webhook signature');
            // Or throw an exception
            return;
        }

        $providerPaymentId = $this->gateway->getProviderPaymentIdFromWebhook($payload);
        $status = $this->gateway->getStatusFromWebhook($payload);

        $transaction = PaymentTransaction::where('provider_payment_id', $providerPaymentId)->firstOrFail();

        if ($transaction->status !== $status) {
            $transaction->update(['status' => $status]);
            // Dispatch event, e.g., PaymentCompleted
            event(new \Modules\Payments\Events\PaymentStatusChanged($transaction, $status));
            $this->logInfo('Webhook processed, status updated', ['transaction_id' => $transaction->id, 'new_status' => $status]);
        }
    }

    private function findTransaction(string $transactionId): PaymentTransaction
    {
        // Apply tenant scope for security
        return $this->getTenantScope()
            ->where('uuid', $transactionId) // Assuming you use UUIDs for public IDs
            ->orWhere('id', $transactionId)
            ->firstOrFail();
    }

    protected function getModelQuery(): Builder
    {
        return PaymentTransaction::query();
    }
}
