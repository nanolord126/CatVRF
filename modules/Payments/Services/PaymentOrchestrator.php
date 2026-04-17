<?php declare(strict_types=1);

namespace Modules\Payments\Services;

use App\Models\PaymentTransaction;
use App\Models\Tenant;
use App\Services\FraudControlService;
use App\Domains\FraudML\DTOs\PaymentFraudMLDto;
use App\Domains\FraudML\Services\PaymentFraudMLService;
use App\Services\WalletService;
use Illuminate\Database\Connection;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Common\Services\AbstractTechnicalVerticalService;
use Modules\Payments\Gateways\PaymentGatewayInterface;
use DomainException;

final class PaymentOrchestrator extends AbstractTechnicalVerticalService
{
    public function __construct(
        private readonly Connection $db,
        private readonly LogManager $log,
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly IdempotencyService $idempotency,
        private readonly PaymentGatewayInterface $gateway,
        private readonly PaymentFraudMLService $fraudMl,
    ) {}

    public function isEnabled(): bool
    {
        return true;
    }

    public function initPayment(
        int $amount,
        string $currency,
        string $paymentMethod,
        int $walletId,
        string $idempotencyKey,
        string $description,
        array $metadata,
        bool $recurrent,
        ?string $ip,
        ?string $device,
    ): array {
        $tenantId = $this->resolveTenantId();
        $correlationId = $this->getCorrelationId();

        $payloadHash = IdempotencyService::hashPayload([
            'amount' => $amount,
            'currency' => $currency,
            'payment_method' => $paymentMethod,
            'wallet_id' => $walletId,
            'metadata' => $metadata,
            'recurrent' => $recurrent,
        ]);

        $hit = $this->idempotency->forTenant(new Tenant(['id' => $tenantId]))
            ->withCorrelationId($correlationId)
            ->check('init_payment', $idempotencyKey, $payloadHash, (int) config('payments.idempotency_ttl', 86400));

        if ($hit['hit'] === true && $hit['response'] !== null) {
            return $hit['response'];
        }

        // FRAUD CHECK - ML-based (replaces rule-based)
        try {
            $fraudDto = new PaymentFraudMLDto(
                tenant_id: $tenantId,
                user_id: auth()->id() ?? 0,
                operation_type: 'payment_orchestrator_init',
                amount_kopecks: $amount * 100,
                ip_address: $ip ?? request()->ip() ?? '127.0.0.1',
                device_fingerprint: $device ?? request()->header('User-Agent') ?? 'unknown',
                correlation_id: $correlationId,
                idempotency_key: $idempotencyKey,
                vertical_code: $metadata['vertical_code'] ?? 'payment',
            );

            $fraudResult = $this->fraudMl->scorePayment($fraudDto);

            if ($fraudResult['decision'] === 'block') {
                Log::warning('Payment blocked by fraud detection', [
                    'tenant_id' => $tenantId,
                    'fraud_score' => $fraudResult['score'],
                    'explanation' => $fraudResult['explanation'] ?? null,
                    'correlation_id' => $correlationId,
                ]);
                throw new \RuntimeException('Payment blocked by fraud detection system');
            }
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Fraud check failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            // Fail-open for reliability
        }

        $transaction = $this->db->transaction(function () use ($amount, $currency, $paymentMethod, $walletId, $idempotencyKey, $description, $metadata, $recurrent, $correlationId) {
            $orderId = (string) Str::uuid();

            $init = $this->gateway->init([
                'amount' => $amount,
                'order_id' => $orderId,
                'description' => $description,
                'metadata' => $metadata,
                'recurrent' => $recurrent,
            ]);

            $transaction = PaymentTransaction::create([
                'tenant_id' => $this->resolveTenantId(),
                'user_id' => auth()->id(),
                'wallet_id' => $walletId,
                'uuid' => $orderId,
                'idempotency_key' => $idempotencyKey,
                'provider_payment_id' => $init->providerPaymentId,
                'payment_method' => $paymentMethod,
                'status' => PaymentTransaction::STATUS_PENDING,
                'amount' => $amount,
                'currency' => $currency,
                'correlation_id' => $correlationId,
                'metadata' => $metadata,
                'tags' => ['payment_init'],
            ]);

            return [$transaction, $init];
        });

        /** @var PaymentTransaction $transaction */
        [$transaction, $init] = $transaction;

        $response = [
            'transaction_id' => $transaction->id,
            'provider_payment_id' => $transaction->provider_payment_id,
            'payment_url' => $init->paymentUrl,
        ];

        $this->idempotency->record('init_payment', $idempotencyKey, $response);

        $this->auditLog($this->log, 'payment.init', [
            'transaction_id' => $transaction->id,
        ]);

        return $response;
    }

    public function syncStatus(PaymentTransaction $transaction): string
    {
        $status = $this->gateway->getStatus((string) $transaction->provider_payment_id);

        if ($status !== $transaction->status) {
            $this->db->transaction(function () use ($transaction, $status): void {
                $transaction->update([
                    'status' => $status,
                    'captured_at' => $status === PaymentTransaction::STATUS_CAPTURED ? now() : $transaction->captured_at,
                ]);
            });

            if ($status === PaymentTransaction::STATUS_CAPTURED && $transaction->wallet_id !== null) {
                $this->wallet->credit($transaction->wallet_id, $transaction->amount, 'payment_captured', $this->getCorrelationId());
            }
        }

        return $status;
    }

    public function refundPayment(PaymentTransaction $transaction, ?int $amount, string $reason): PaymentTransaction
    {
        $refundAmount = $amount ?? $transaction->amount;

        $this->gateway->refund((string) $transaction->provider_payment_id, $refundAmount);

        $this->db->transaction(function () use ($transaction, $refundAmount, $reason): void {
            $transaction->update([
                'status' => PaymentTransaction::STATUS_REFUNDED,
                'refunded_at' => now(),
                'metadata' => array_merge($transaction->metadata ?? [], ['refund_reason' => $reason, 'refund_amount' => $refundAmount]),
            ]);

            if ($transaction->wallet_id !== null) {
                $this->wallet->credit($transaction->wallet_id, $refundAmount, 'payment_refund', $this->getCorrelationId());
            }
        });

        return $transaction->fresh();
    }

    public function handleWebhook(array $payload): PaymentTransaction
    {
        if (!$this->gateway->validateWebhook($payload)) {
            throw new \RuntimeException('Invalid signature');
        }

        $parsed = $this->gateway->parseWebhook($payload);

        /** @var PaymentTransaction $transaction */
        $transaction = PaymentTransaction::where('provider_payment_id', $parsed['provider_payment_id'])->firstOrFail();

        $this->forTenant(new Tenant(['id' => $transaction->tenant_id]));

        $status = $parsed['status'];

        $this->db->transaction(function () use ($transaction, $status): void {
            $transaction->update([
                'status' => $status,
                'captured_at' => $status === PaymentTransaction::STATUS_CAPTURED ? now() : $transaction->captured_at,
            ]);
        });

        if ($status === PaymentTransaction::STATUS_CAPTURED && $transaction->wallet_id !== null) {
            $this->wallet->credit($transaction->wallet_id, $transaction->amount, 'payment_captured', $this->getCorrelationId());
        }

        return $transaction->fresh();
    }
}
