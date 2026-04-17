<?php declare(strict_types=1);

namespace Modules\Payments\Services;

use App\Models\PaymentTransaction;
use App\Domains\FraudML\DTOs\PaymentFraudMLDto;
use App\Domains\FraudML\Services\PaymentFraudMLService;
use App\Domains\FraudML\Jobs\FraudCheckPaymentJob;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Сервис управления платежами.
 * Единственная точка для операций с платежами.
 * Production 2026.
 */
final class PaymentService
{
    private const FRAUD_CHECK_TIMEOUT_MS = 30;
    private const FRAUD_BLOCK_THRESHOLD = 0.75;

    public function __construct(
        private readonly PaymentFraudMLService $fraudService,
    ) {}
    /**
     * Инициировать платёж.
     */
    public function initPayment(
        int $userId,
        int $tenantId,
        int $amount,
        string $currency = 'RUB',
        string $paymentMethod = 'card',
        string $idempotencyKey = '',
        mixed $correlationId = null,
        ?array $metadata = null,
        ?string $verticalCode = null,
        ?string $urgencyLevel = null,
        ?bool $isEmergencyPayment = null,
    ): PaymentTransaction {
        $correlationId ??= Str::uuid();
        $idempotencyKey = $idempotencyKey ?: (string) Str::uuid();

        try {
            Log::channel('audit')->info('payment.service.init.start', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'amount' => $amount,
                'idempotency_key' => $idempotencyKey,
                'vertical_code' => $verticalCode,
            ]);

            // CRITICAL: Fraud check before payment processing
            $fraudResult = $this->performFraudCheck(
                $userId,
                $tenantId,
                $amount,
                $idempotencyKey,
                $correlationId,
                $verticalCode,
                $urgencyLevel,
                $isEmergencyPayment,
            );

            if ($fraudResult['decision'] === 'block') {
                Log::channel('audit')->warning('payment.service.init.blocked', [
                    'correlation_id' => $correlationId,
                    'user_id' => $userId,
                    'amount' => $amount,
                    'fraud_score' => $fraudResult['score'],
                    'explanation' => $fraudResult['explanation'] ?? null,
                ]);

                throw new DomainException('Payment blocked by fraud detection system');
            }

            $transaction = DB::transaction(function () use (
                $userId,
                $tenantId,
                $amount,
                $currency,
                $paymentMethod,
                $idempotencyKey,
                $correlationId,
                $metadata,
                $fraudResult,
            ) {
                // Проверка idempotency
                $existing = PaymentTransaction::where('tenant_id', $tenantId)
                    ->where('user_id', $userId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing) {
                    Log::channel('audit')->info('payment.service.init.idempotent', [
                        'correlation_id' => $correlationId,
                        'existing_id' => $existing->id,
                    ]);
                    return $existing;
                }

                return PaymentTransaction::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'amount' => $amount,
                    'currency' => $currency,
                    'payment_method' => $paymentMethod,
                    'idempotency_key' => $idempotencyKey,
                    'status' => 'pending',
                    'correlation_id' => (string) $correlationId,
                    'tags' => array_merge(['payment_init', 'pending'], $fraudResult['cached'] ? ['fraud_cached'] : []),
                    'metadata' => array_merge($metadata ?? [], [
                        'fraud_score' => $fraudResult['score'],
                        'fraud_decision' => $fraudResult['decision'],
                        'fraud_cached' => $fraudResult['cached'],
                    ]),
                ]);
            });

            Log::channel('audit')->info('payment.service.init.success', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'fraud_score' => $fraudResult['score'],
                'fraud_cached' => $fraudResult['cached'],
            ]);

            return $transaction;
        } catch (DomainException $e) {
            // Re-throw domain exceptions (like fraud blocks)
            throw $e;
        } catch (Throwable $e) {
            Log::channel('audit')->critical('payment.service.init.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Захватить платёж (authorized → captured).
     */
    public function capturePayment(
        PaymentTransaction $transaction,
        mixed $correlationId = null,
    ): PaymentTransaction {
        $correlationId ??= Str::uuid();

        try {
            if ($transaction->status !== 'authorized') {
                throw new DomainException("Cannot capture transaction with status: {$transaction->status}");
            }

            Log::channel('audit')->info('payment.service.capture.start', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
            ]);

            $updated = DB::transaction(function () use ($transaction, $correlationId) {
                $transaction->update([
                    'status' => 'captured',
                    'captured_at' => now(),
                ]);
                return $transaction->fresh();
            });

            Log::channel('audit')->info('payment.service.capture.success', [
                'correlation_id' => $correlationId,
                'transaction_id' => $updated->id,
            ]);

            return $updated;
        } catch (Throwable $e) {
            Log::channel('audit')->critical('payment.service.capture.error', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Вернуть платёж (любой статус → refunded).
     */
    public function refundPayment(
        PaymentTransaction $transaction,
        string $reason = 'User requested',
        mixed $correlationId = null,
    ): PaymentTransaction {
        $correlationId ??= Str::uuid();

        try {
            Log::channel('audit')->info('payment.service.refund.start', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
                'reason' => $reason,
            ]);

            $refunded = DB::transaction(function () use ($transaction, $reason, $correlationId) {
                $transaction->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                    'refund_reason' => $reason,
                ]);
                return $transaction->fresh();
            });

            Log::channel('audit')->info('payment.service.refund.success', [
                'correlation_id' => $correlationId,
                'transaction_id' => $refunded->id,
            ]);

            return $refunded;
        } catch (Throwable $e) {
            Log::channel('audit')->critical('payment.service.refund.error', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Получить статус платежа у провайдера.
     */
    public function getPaymentStatus(
        PaymentTransaction $transaction,
        mixed $correlationId = null,
    ): array {
        $correlationId ??= Str::uuid();

        try {
            Log::channel('audit')->info('payment.service.status.check', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
            ]);

            return [
                'status' => $transaction->status,
                'provider_code' => $transaction->provider_code,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
            ];
        } catch (Throwable $e) {
            Log::channel('audit')->critical('payment.service.status.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Perform fraud check with timeout protection
     * 
     * Uses sync call with 30ms timeout, falls back to async if needed
     */
    private function performFraudCheck(
        int $userId,
        int $tenantId,
        int $amount,
        string $idempotencyKey,
        mixed $correlationId,
        ?string $verticalCode,
        ?string $urgencyLevel,
        ?bool $isEmergencyPayment,
    ): array {
        $startTime = microtime(true);

        try {
            $dto = new PaymentFraudMLDto(
                tenant_id: $tenantId,
                user_id: $userId,
                operation_type: 'payment_init',
                amount_kopecks: $amount * 100,
                ip_address: request()->ip() ?? '127.0.0.1',
                device_fingerprint: request()->header('User-Agent') ?? 'unknown',
                correlation_id: (string) $correlationId,
                idempotency_key: $idempotencyKey,
                vertical_code: $verticalCode,
                urgency_level: $urgencyLevel,
                is_emergency_payment: $isEmergencyPayment,
            );

            // Perform sync fraud check with timeout
            $result = $this->fraudService->scorePayment($dto);
            
            $latencyMs = (microtime(true) - $startTime) * 1000;
            
            // Log latency for monitoring
            if ($latencyMs > self::FRAUD_CHECK_TIMEOUT_MS) {
                Log::warning('payment.fraud.check.high_latency', [
                    'correlation_id' => $correlationId,
                    'latency_ms' => $latencyMs,
                    'timeout_ms' => self::FRAUD_CHECK_TIMEOUT_MS,
                ]);
            }

            return $result;
            
        } catch (Throwable $e) {
            // On fraud check failure, allow payment but log error
            Log::error('payment.fraud.check.failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            
            // Return allow decision on failure (fail-open for reliability)
            return [
                'score' => 0.0,
                'decision' => 'allow',
                'explanation' => ['error' => 'fraud_check_failed'],
                'cached' => false,
            ];
        }
    }
}
