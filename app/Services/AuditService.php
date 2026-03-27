<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Audit Logging Service
 * Production 2026 CANON
 *
 * Centralized audit logging for all operations
 * - Logs to audit channel
 * - Includes correlation_id for tracing
 * - Records user, tenant, operation, changes
 *
 * @author CatVRF Team
 * @version 2026.03.24
 */
final class AuditService
{
    /**
     * Log an operation
     *
     * @param string $operation Operation name (e.g., 'appointment.create')
     * @param array $data Operation data
     * @param string $correlationId Tracing ID
     * @param array $metadata Additional metadata
     * @return void
     */
    public static function log(string $operation, array $data, string $correlationId, array $metadata = []): void
    {
        $context = array_merge([
            'correlation_id' => $correlationId,
            'operation' => $operation,
            'user_id' => Auth::id(),
            'tenant_id' => tenant()->id ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ], $metadata, $data);

        Log::channel('audit')->info($operation, $context);
    }

    /**
     * Log an error
     *
     * @param string $operation Operation name
     * @param \Exception $exception Exception thrown
     * @param string $correlationId Tracing ID
     * @param array $context Additional context
     * @return void
     */
    public static function logError(string $operation, \Exception $exception, string $correlationId, array $context = []): void
    {
        $errorContext = array_merge([
            'correlation_id' => $correlationId,
            'operation' => $operation,
            'error' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => Auth::id(),
            'tenant_id' => tenant()->id ?? null,
            'timestamp' => now(),
        ], $context);

        Log::channel('audit')->error($operation . ' failed', $errorContext);
    }

    /**
     * Log payment operation
     *
     * @param string $action Action (init, capture, refund)
     * @param array $paymentData Payment data
     * @param string $correlationId Tracing ID
     * @return void
     */
    public static function logPayment(string $action, array $paymentData, string $correlationId): void
    {
        self::log('payment.' . $action, [
            'payment_id' => $paymentData['payment_id'] ?? null,
            'amount' => $paymentData['amount'] ?? null,
            'gateway' => $paymentData['gateway'] ?? null,
            'status' => $paymentData['status'] ?? null,
        ], $correlationId);
    }

    /**
     * Log wallet operation
     *
     * @param string $action Action (hold, release, credit, debit)
     * @param array $walletData Wallet data
     * @param string $correlationId Tracing ID
     * @return void
     */
    public static function logWallet(string $action, array $walletData, string $correlationId): void
    {
        self::log('wallet.' . $action, [
            'wallet_id' => $walletData['wallet_id'] ?? null,
            'amount' => $walletData['amount'] ?? null,
            'reason' => $walletData['reason'] ?? null,
            'balance_before' => $walletData['balance_before'] ?? null,
            'balance_after' => $walletData['balance_after'] ?? null,
        ], $correlationId);
    }

    /**
     * Log fraud check
     *
     * @param array $checkData Fraud check data
     * @param string $correlationId Tracing ID
     * @return void
     */
    public static function logFraudCheck(array $checkData, string $correlationId): void
    {
        self::log('fraud.check', [
            'user_id' => $checkData['user_id'] ?? null,
            'operation_type' => $checkData['operation_type'] ?? null,
            'score' => $checkData['score'] ?? null,
            'decision' => $checkData['decision'] ?? null,
            'amount' => $checkData['amount'] ?? null,
        ], $correlationId);
    }

    /**
     * Log promo application
     *
     * @param array $promoData Promo data
     * @param string $correlationId Tracing ID
     * @return void
     */
    public static function logPromo(array $promoData, string $correlationId): void
    {
        self::log('promo.apply', [
            'code' => $promoData['code'] ?? null,
            'discount_amount' => $promoData['discount_amount'] ?? null,
            'order_amount' => $promoData['order_amount'] ?? null,
            'vertical' => $promoData['vertical'] ?? null,
        ], $correlationId);
    }

    /**
     * Log referral operation
     *
     * @param string $action Action (generate, register, qualify, award)
     * @param array $referralData Referral data
     * @param string $correlationId Tracing ID
     * @return void
     */
    public static function logReferral(string $action, array $referralData, string $correlationId): void
    {
        self::log('referral.' . $action, [
            'referral_id' => $referralData['referral_id'] ?? null,
            'referrer_id' => $referralData['referrer_id'] ?? null,
            'referee_id' => $referralData['referee_id'] ?? null,
            'bonus_amount' => $referralData['bonus_amount'] ?? null,
        ], $correlationId);
    }
}
