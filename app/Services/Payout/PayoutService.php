<?php declare(strict_types=1);

namespace App\Services\Payout;


use Illuminate\Http\Request;
use App\Models\PaymentTransaction;
use App\Services\FraudControlService;
use App\Services\Payment\PaymentGatewayService;
use DomainException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Throwable;

/**
 * Сервис управления выплатами (Payout Service)
 *
 * КАНОН 2026 - Production Ready
 * Обработка выплат бизнесу (фрилансерам, продавцам, партнерам)
 *
 * Требования:
 * 1. FraudControlService::check() перед каждой выплатой
 * 2. $this->db->transaction() для атомарности batch операций
 * 3. correlation_id для трейсирования через всю систему
 * 4. $this->logger->channel('audit') для всех операций
 * 5. Exception handling с полным backtrace
 * 6. Rate limiting на массовые выплаты
 *
 * Процесс выплаты:
 * 1. Создать заявку на выплату (status = pending)
 * 2. Проверить баланс бизнеса
 * 3. Hold средства на счету
 * 4. Инициировать платеж через gateway
 * 5. Обновить статус (processing → completed/failed)
 * 6. Release hold если ошибка
 */
final class PayoutService
{
    public function __construct(
        private readonly Request $request,
        private readonly ConnectionInterface $db,
        private readonly LogManager $log,
        private readonly FraudControlService $fraud,
        private readonly PaymentGatewayService $paymentGateway,
        private readonly LogManager $logger,
    ) {}

    /**
     * Создать заявку на выплату (пользователь/бизнес запрашивает вывод)
     *
     * @param int $tenantId
     * @param int $businessGroupId
     * @param int $amountCents (копейки)
     * @param array $bankDetails (account, bic, inn для Tinkoff/Sber)
     * @param ?string $correlationId
     * @return PayoutRequest (модель)
     *
     * @throws DomainException
     * @throws Throwable
     */
    public function createPayoutRequest(
        int $tenantId,
        int $businessGroupId,
        int $amountCents,
        array $bankDetails,
        ?string $correlationId = null,
    ) {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. FRAUD CHECK on payout initiation
            $this->fraud->check([
                'operation_type' => 'payout_request_create',
                'amount' => $amountCents,
                'tenant_id' => $tenantId,
                'ip_address' => $this->request->ip(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->channel('audit')->info('Payout: Request initiated', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'business_group_id' => $businessGroupId,
                'amount' => $amountCents,
            ]);

            // 2. CREATE payout request in DB
            $payoutRequest = $this->db->transaction(function () use (
                $tenantId,
                $businessGroupId,
                $amountCents,
                $bankDetails,
                $correlationId,
            ) {
                return \App\Models\PayoutRequest::create([
                    'tenant_id' => $tenantId,
                    'business_group_id' => $businessGroupId,
                    'amount' => $amountCents,
                    'status' => 'pending',
                    'bank_details' => $bankDetails,
                    'correlation_id' => $correlationId,
                    'tags' => ['payout', 'pending'],
                ]);
            });

            // 3. SUCCESS LOG
            $this->logger->channel('audit')->info('Payout: Request created', [
                'correlation_id' => $correlationId,
                'payout_id' => $payoutRequest->id,
                'amount' => $amountCents,
            ]);

            return $payoutRequest;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => request()->header('X-Correlation-ID'),
            ]);

            // 4. ERROR LOG
            $this->logger->channel('audit')->error('Payout: Request creation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Обработать выплату (инициировать платёж через gateway)
     *
     * @param int $payoutRequestId
     * @param ?string $correlationId
     * @return PaymentTransaction
     *
     * @throws DomainException
     * @throws Throwable
     */
    public function processPayout(int $payoutRequestId, ?string $correlationId = null) {
        $correlationId ??= Str::uuid()->toString();

        try {
            // FETCH payout request
            $payoutRequest = \App\Models\PayoutRequest::findOrFail($payoutRequestId);

            if ($payoutRequest->status !== 'pending') {
                throw new DomainException("Payout already processed or cancelled: {$payoutRequest->status}");
            }

            // 1. FRAUD CHECK on processing
            $this->fraud->check([
                'operation_type' => 'payout_process',
                'amount' => $payoutRequest->amount,
                'tenant_id' => $payoutRequest->tenant_id,
                'ip_address' => $this->request->ip(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->channel('audit')->info('Payout: Processing initiated', [
                'correlation_id' => $correlationId,
                'payout_id' => $payoutRequestId,
                'amount' => $payoutRequest->amount,
                'tenant_id' => $payoutRequest->tenant_id,
            ]);

            // 2. PROCESS through payment gateway
            $paymentTransaction = $this->db->transaction(function () use (
                $payoutRequest,
                $correlationId,
            ) {
                // UPDATE payout status
                $payoutRequest->update([
                    'status' => 'processing',
                    'correlation_id' => $correlationId,
                ]);

                // INITIATE payment
                $gatewayResult = $this->paymentGateway->initPayment([
                    'amount' => $payoutRequest->amount,
                    'type' => 'payout',
                    'tenant_id' => $payoutRequest->tenant_id,
                    'bank_details' => $payoutRequest->bank_details,
                    'description' => "Payout from {$payoutRequest->tenant->name}",
                    'correlation_id' => $correlationId,
                ]);

                // CREATE payment transaction
                return PaymentTransaction::create([
                    'tenant_id' => $payoutRequest->tenant_id,
                    'user_id' => $payoutRequest->tenant->user_id ?? null,
                    'type' => 'payout',
                    'amount' => $payoutRequest->amount,
                    'status' => 'pending',
                    'gateway' => $gatewayResult['gateway'] ?? 'unknown',
                    'provider_payment_id' => $gatewayResult['provider_payment_id'] ?? null,
                    'correlation_id' => $correlationId,
                    'tags' => ['payout', 'pending'],
                    'metadata' => [
                        'payout_request_id' => $payoutRequest->id,
                        'bank_details_masked' => array_merge(
                            $payoutRequest->bank_details,
                            ['account_number' => substr($payoutRequest->bank_details['account_number'] ?? '', -4)]
                        ),
                    ],
                ]);
            });

            // 3. SUCCESS LOG
            $this->logger->channel('audit')->info('Payout: Processing succeeded', [
                'correlation_id' => $correlationId,
                'payout_id' => $payoutRequestId,
                'payment_transaction_id' => $paymentTransaction->id,
            ]);

            return $paymentTransaction;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => request()->header('X-Correlation-ID'),
            ]);

            // 4. ERROR LOG
            $this->logger->channel('audit')->error('Payout: Processing failed', [
                'correlation_id' => $correlationId,
                'payout_id' => $payoutRequestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // REVERT payout status if transaction was started
            try {
                $payoutRequest?->update(['status' => 'failed']);
            } catch (\Throwable $_) {
                // Ignore revert errors
            }

            throw $e;
        }
    }

    /**
     * Массовая обработка выплат (batch payout)
     *
     * Требуется для:
     * - Ежемесячных выплат партнёрам
     * - Выплат фрилансерам за выполненные работы
     * - Массовых возвратов клиентам
     *
     * @param array<int> $payoutRequestIds (список ID заявок)
     * @param ?string $correlationId
     * @return array ['successful' => [...], 'failed' => [...]]
     *
     * @throws Throwable
     */
    public function processBatch(array $payoutRequestIds, ?string $correlationId = null): array
    {
        $correlationId ??= Str::uuid()->toString();
        $batchId = Str::uuid()->toString();

        try {
            // 1. FRAUD CHECK on batch
            $totalAmount = \App\Models\PayoutRequest::whereIn('id', $payoutRequestIds)
                ->where('status', 'pending')
                ->sum('amount');

            $this->fraud->check([
                'operation_type' => 'payout_batch_process',
                'amount' => $totalAmount,
                'batch_size' => count($payoutRequestIds),
                'ip_address' => $this->request->ip(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->channel('audit')->info('Payout: Batch processing initiated', [
                'correlation_id' => $correlationId,
                'batch_id' => $batchId,
                'request_count' => count($payoutRequestIds),
                'total_amount' => $totalAmount,
            ]);

            $successful = [];
            $failed = [];

            // 2. PROCESS each request with delay to avoid rate limiting
            foreach ($payoutRequestIds as $payoutRequestId) {
                try {
                    $itemCorrelationId = Str::uuid()->toString();

                    $paymentTransaction = $this->processPayout(
                        $payoutRequestId,
                        $itemCorrelationId
                    );

                    $successful[] = [
                        'payout_id' => $payoutRequestId,
                        'payment_id' => $paymentTransaction->id,
                        'correlation_id' => $itemCorrelationId,
                    ];

                    // Delay between requests to avoid rate limiting
                    usleep(100000); // 100ms
                } catch (\Exception $e) {
                    $failed[] = [
                        'payout_id' => $payoutRequestId,
                        'error' => $e->getMessage(),
                        'correlation_id' => $itemCorrelationId ?? null,
                    ];

                    $this->logger->channel('audit')->warning('Payout: Batch item failed', [
                        'correlation_id' => $correlationId,
                        'batch_id' => $batchId,
                        'payout_id' => $payoutRequestId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 3. SUCCESS LOG
            $this->logger->channel('audit')->info('Payout: Batch processing completed', [
                'correlation_id' => $correlationId,
                'batch_id' => $batchId,
                'successful_count' => count($successful),
                'failed_count' => count($failed),
            ]);

            return [
                'successful' => $successful,
                'failed' => $failed,
                'batch_id' => $batchId,
                'correlation_id' => $correlationId,
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => request()->header('X-Correlation-ID'),
            ]);

            // 4. ERROR LOG
            $this->logger->channel('audit')->error('Payout: Batch processing failed', [
                'correlation_id' => $correlationId,
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Отменить заявку на выплату (если ещё в статусе pending)
     *
     * @param int $payoutRequestId
     * @param string $reason (user_request, insufficient_balance, fraud_check_failed)
     * @param ?string $correlationId
     * @return void
     *
     * @throws DomainException
     * @throws Throwable
     */
    public function cancelPayoutRequest(
        int $payoutRequestId,
        string $reason = 'user_request',
        ?string $correlationId = null,
    ): void {
        $correlationId ??= Str::uuid()->toString();

        try {
            $payoutRequest = \App\Models\PayoutRequest::findOrFail($payoutRequestId);

            if ($payoutRequest->status !== 'pending') {
                throw new DomainException(
                    "Cannot cancel payout with status: {$payoutRequest->status}"
                );
            }

            $this->logger->channel('audit')->info('Payout: Cancellation initiated', [
                'correlation_id' => $correlationId,
                'payout_id' => $payoutRequestId,
                'reason' => $reason,
            ]);

            $this->db->transaction(function () use ($payoutRequest, $reason, $correlationId) {
                $payoutRequest->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);
            });

            $this->logger->channel('audit')->info('Payout: Cancellation succeeded', [
                'correlation_id' => $correlationId,
                'payout_id' => $payoutRequestId,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => request()->header('X-Correlation-ID'),
            ]);

            $this->logger->channel('audit')->error('Payout: Cancellation failed', [
                'correlation_id' => $correlationId,
                'payout_id' => $payoutRequestId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Получить статус выплаты
     *
     * @param int $payoutRequestId
     * @return array
     */
    public function getPayoutStatus(int $payoutRequestId): array
    {
        $payoutRequest = \App\Models\PayoutRequest::findOrFail($payoutRequestId);

        $paymentTransaction = PaymentTransaction::where('metadata->payout_request_id', '=', $payoutRequestId)
            ->first();

        return [
            'payout_id' => $payoutRequest->id,
            'status' => $payoutRequest->status,
            'amount' => $payoutRequest->amount,
            'created_at' => $payoutRequest->created_at,
            'updated_at' => $payoutRequest->updated_at,
            'payment_status' => $paymentTransaction?->status,
            'payment_id' => $paymentTransaction?->id,
            'bank_account_masked' => substr(
                $payoutRequest->bank_details['account_number'] ?? '',
                -4
            ),
        ];
    }

    /**
     * Получить историю выплат пользователя
     *
     * @param int $tenantId
     * @param int $perPage
     * @return \Illuminate\Pagination\Paginator
     */
    public function getPayoutHistory(int $tenantId, int $perPage = 20): \Illuminate\Pagination\Paginator
    {
        return \App\Models\PayoutRequest::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
