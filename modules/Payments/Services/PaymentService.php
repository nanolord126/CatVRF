<?php declare(strict_types=1);

namespace Modules\Payments\Services;

use App\Modules\Payments\Models\PaymentTransaction;
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
    ): PaymentTransaction {
        $correlationId ??= Str::uuid();

        try {
            Log::channel('audit')->info('payment.service.init.start', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'amount' => $amount,
                'idempotency_key' => $idempotencyKey,
            ]);

            $transaction = DB::transaction(function () use (
                $userId,
                $tenantId,
                $amount,
                $currency,
                $paymentMethod,
                $idempotencyKey,
                $correlationId,
                $metadata,
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
                    'tags' => ['payment_init', 'pending'],
                    'metadata' => $metadata ?? [],
                ]);
            });

            Log::channel('audit')->info('payment.service.init.success', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
                'amount' => $amount,
            ]);

            return $transaction;
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
