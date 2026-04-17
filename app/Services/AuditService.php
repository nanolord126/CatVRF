<?php declare(strict_types=1);

namespace App\Services;



use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use App\Jobs\AuditLogJob;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

/**
 * Audit Logging Service
 * Production 2026 CANON — CatVRF 2026
 *
 * Централизованное логирование всех мутаций.
 * - Все записи через $this->logger->channel('audit') + DB (audit_logs)
 * - correlation_id обязателен в каждом вызове
 * - Асинхронная запись в БД через AuditLogJob
 *
 * Новый канонический API (constructor injection):
 *   $this->audit->record('action', Model::class, $id, $old, $new, $correlationId);
 *   $this->audit->logModelEvent('created', $model);
 *
 * Legacy static API (@deprecated, только для обратной совместимости):
 *   AuditService::log('operation', $data, $correlationId);
 */
final class AuditService
{
    public function __construct(
        private readonly Request $request,
        private readonly AuthManager $authManager,
        private readonly \Illuminate\Contracts\Queue\Queue $queue,
        private readonly LogManager $logger,
    ) {}

    // ─────────────────────────────────────────────────────────────
    // CANONICAL INSTANCE METHODS (use these in new code)
    // ─────────────────────────────────────────────────────────────

    /**
     * Записать аудит-событие асинхронно (канонический метод).
     * Вызывать ПОСЛЕ успешной транзакции.
     */
    public function record(
        string  $action,
        string  $subjectType,
        ?int    $subjectId,
        array   $oldValues    = [],
        array   $newValues    = [],
        ?string $correlationId = null,
    ): void {
        $cid = $correlationId ?? Str::uuid()->toString();

        AuditLogJob::dispatch([
            'tenant_id'           => function_exists('tenant') && tenant() ? tenant()->id : null,
            'business_group_id'   => $this->request->get('business_group_id'),
            'user_id'             => $this->authManager->id(),
            'action'              => $action,
            'subject_type'        => $subjectType,
            'subject_id'          => $subjectId,
            'old_values'          => $oldValues,
            'new_values'          => $newValues,
            'ip_address'          => $this->request->ip(),
            'device_fingerprint'  => hash('sha256', $this->request->ip() . $this->request->userAgent()),
            'correlation_id'      => $cid,
        ])->onQueue('audit-logs');

        $this->logger->channel('audit')->info($action, [
            'subject_type'   => $subjectType,
            'subject_id'     => $subjectId,
            'correlation_id' => $cid,
            'user_id'        => $this->authManager->id(),
            'tenant_id'      => function_exists('tenant') && tenant() ? tenant()->id : null,
        ]);
    }

    /**
     * Удобная обёртка для событий моделей (created / updated / deleted).
     * Вызывается из Model::booted() после успешного сохранения.
     */
    public function logModelEvent(
        string  $event,
        Model   $model,
        array   $old          = [],
        ?string $correlationId = null,
    ): void {
        $new = match ($event) {
            default   => $model->getChanges() ?: $model->toArray(),
        };

        $this->record(
            action:        $event,
            subjectType:   get_class($model),
            subjectId:     $model->getKey(),
            oldValues:     $old,
            newValues:     $new,
            correlationId: $correlationId,
        );
    }

    // ─────────────────────────────────────────────────────────────
    // LEGACY STATIC API — @deprecated, используйте instance-методы
    // ─────────────────────────────────────────────────────────────

    /**
     * Instance-метод log() — alias для record(), совместим с доменными сервисами.
     *
     * Сигнатура: log(action, subjectType, subjectId, old, new, correlationId)
     * Пример: $this->audit->log('wallet_credited', Wallet::class, $id, $old, $new, $cid);
     */
    public function log(
        string  $action,
        string  $subjectType,
        ?int    $subjectId     = null,
        array   $oldValues     = [],
        array   $newValues     = [],
        ?string $correlationId = null,
    ): void {
        $this->record($action, $subjectType, $subjectId, $oldValues, $newValues, $correlationId);
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
            'user_id' => $this->authManager->id(),
            'tenant_id' => tenant()->id ?? null,
            'timestamp' => now(),
        ], $context);

        $this->logger->channel('audit')->error($operation . ' failed', $errorContext);
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
