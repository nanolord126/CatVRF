<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Payment\DTOs\CreatePaymentRecordDto;
use App\Domains\Payment\DTOs\GatewayRequestDto;
use App\Domains\Payment\DTOs\GatewayResponseDto;
use App\Domains\Payment\DTOs\UpdatePaymentRecordDto;
use App\Domains\Payment\Enums\GatewayProvider;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Jobs\PaymentFraudCheckJob;
use App\Domains\Payment\Models\PaymentRecord;
use App\Domains\Wallet\Services\AtomicWalletService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

/**
 * PaymentEngineService - Orchestrates payment flow with proper architecture.
 *
 * CRITICAL: Follows the new payment architecture:
 * 1. Idempotency check (Redis)
 * 2. Fraud check (rule-based, fast path)
 * 3. DB transaction for payment record creation
 * 4. Async FraudML job (non-blocking)
 * 5. Gateway call (outside DB transaction)
 * 6. Status update with proper idempotency
 *
 * Architecture improvements:
 * - No DB::transaction around gateway calls
 * - Async FraudML inference
 * - Circuit breaker for gateway resilience
 * - Atomic wallet operations via AtomicWalletService
 * - Proper idempotency at gateway level
 *
 * @package App\Domains\Payment\Services
 */
final readonly class PaymentEngineService
{
    public function __construct(
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
        private FraudControlService $fraud,
        private AuditService $audit,
        private PaymentService $paymentService,
        private IdempotencyService $idempotency,
        private PaymentGatewayService $gateway,
        private AtomicWalletService $walletService,
    ) {}

    /**
     * Create payment with proper architecture.
     *
     * Flow:
     * 1. Check idempotency (Redis)
     * 2. Rule-based fraud check (fast)
     * 3. DB transaction: create payment record + hold wallet
     * 4. Dispatch async FraudML job
     * 5. Call gateway (outside transaction)
     * 6. Update payment status
     *
     * @param CreatePaymentRecordDto $dto
     * @param string $returnUrl
     * @return PaymentRecord
     */
    public function createPayment(CreatePaymentRecordDto $dto, string $returnUrl): PaymentRecord
    {
        $correlationId = $dto->correlationId;

        // Step 1: Idempotency check
        $existingPaymentId = $this->idempotency->check($correlationId);
        if ($existingPaymentId !== null) {
            $this->logger->info('Payment already processed (idempotency)', [
                'correlation_id' => $correlationId,
                'existing_payment_id' => $existingPaymentId,
            ]);

            return $this->paymentService->findById($existingPaymentId);
        }

        // Step 2: Rule-based fraud check (fast path)
        $userId = $this->getCurrentUserId() ?? 0;
        $this->fraud->check($userId, 'payment_create', $dto->amountKopecks, null, null, $correlationId);

        // Step 3: DB transaction - create payment record + hold wallet
        $payment = $this->db->transaction(function () use ($dto, $userId): PaymentRecord {
            $payment = $this->paymentService->create($dto);

            // Hold amount in wallet if wallet_id is provided
            if ($dto->walletId !== null) {
                $this->walletService->hold(
                    walletId: $dto->walletId,
                    amount: $dto->amountKopecks,
                    correlationId: $correlationId,
                    sourceType: PaymentRecord::class,
                    sourceId: $payment->id,
                    verticalCode: $dto->verticalCode ?? null,
                );
            }

            return $payment;
        });

        // Step 4: Mark idempotency key
        $this->idempotency->mark($correlationId, $payment->id);

        // Step 5: Dispatch async FraudML job (non-blocking)
        PaymentFraudCheckJob::dispatch($payment->id);

        // Step 6: Call gateway (OUTSIDE DB transaction)
        try {
            $gatewayRequest = new GatewayRequestDto(
                provider: GatewayProvider::from($dto->providerCode),
                amountKopecks: $dto->amountKopecks,
                correlationId: $correlationId,
                description: $dto->description ?? "Payment #{$payment->id}",
                returnUrl: $returnUrl,
                tenantId: $dto->tenantId,
            );

            $gatewayResponse = $this->gateway->createPayment($gatewayRequest);

            // Step 7: Update payment with gateway response
            $updateDto = new UpdatePaymentRecordDto(
                paymentRecordId: $payment->id,
                status: $gatewayResponse->status->value,
                providerPaymentId: $gatewayResponse->providerPaymentId,
                correlationId: $correlationId,
                metadata: array_merge($dto->metadata ?? [], [
                    'confirmation_url' => $gatewayResponse->confirmationUrl,
                    'gateway_response' => $gatewayResponse->rawResponse,
                ]),
            );

            $payment = $this->paymentService->updateStatus($updateDto);

            $this->logger->info('Payment created successfully', [
                'payment_id' => $payment->id,
                'correlation_id' => $correlationId,
                'provider_payment_id' => $gatewayResponse->providerPaymentId,
                'status' => $payment->status->value,
            ]);

            return $payment;

        } catch (\Exception $e) {
            // Gateway failed - mark payment as failed
            $this->logger->error('Payment gateway failed', [
                'payment_id' => $payment->id,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            $updateDto = new UpdatePaymentRecordDto(
                paymentRecordId: $payment->id,
                status: PaymentStatus::FAILED->value,
                correlationId: $correlationId,
                metadata: [
                    'gateway_error' => $e->getMessage(),
                ],
            );

            $this->paymentService->updateStatus($updateDto);

            // Release wallet hold
            if ($dto->walletId !== null) {
                try {
                    // TODO: Implement release hold operation
                } catch (\Exception $releaseError) {
                    $this->logger->error('Failed to release wallet hold after gateway error', [
                        'payment_id' => $payment->id,
                        'error' => $releaseError->getMessage(),
                    ]);
                }
            }

            throw $e;
        }
    }

    /**
     * Capture payment.
     *
     * @param int $paymentId
     * @param string $correlationId
     * @return PaymentRecord
     */
    public function capturePayment(int $paymentId, string $correlationId): PaymentRecord
    {
        $payment = $this->paymentService->findById($paymentId);
        if ($payment === null) {
            throw new \InvalidArgumentException("Payment not found: {$paymentId}");
        }

        if ($payment->status !== PaymentStatus::WAITING_FOR_CAPTURE) {
            throw new \InvalidArgumentException("Payment cannot be captured from status: {$payment->status->value}");
        }

        // Call gateway (outside DB transaction)
        $gatewayRequest = new GatewayRequestDto(
            provider: GatewayProvider::from($payment->provider_code),
            amountKopecks: $payment->amount_kopecks,
            correlationId: $correlationId,
            description: "Capture payment #{$payment->id}",
            returnUrl: '',
            tenantId: $payment->tenant_id,
            providerPaymentId: $payment->provider_payment_id,
        );

        $gatewayResponse = $this->gateway->capturePayment($gatewayRequest);

        // Update payment status
        $updateDto = new UpdatePaymentRecordDto(
            paymentRecordId: $paymentId,
            status: $gatewayResponse->status->value,
            correlationId: $correlationId,
        );

        return $this->paymentService->updateStatus($updateDto);
    }

    /**
     * Refund payment.
     *
     * @param int $paymentId
     * @param int|null $amountKopecks Partial refund amount (null for full refund)
     * @param string $correlationId
     * @return PaymentRecord
     */
    public function refundPayment(int $paymentId, ?int $amountKopecks, string $correlationId): PaymentRecord
    {
        $payment = $this->paymentService->findById($paymentId);
        if ($payment === null) {
            throw new \InvalidArgumentException("Payment not found: {$paymentId}");
        }

        if (!in_array($payment->status, [PaymentStatus::COMPLETED, PaymentStatus::PARTIALLY_REFUNDED], true)) {
            throw new \InvalidArgumentException("Payment cannot be refunded from status: {$payment->status->value}");
        }

        $refundAmount = $amountKopecks ?? $payment->amount_kopecks;

        // Call gateway (outside DB transaction)
        $gatewayRequest = new GatewayRequestDto(
            provider: GatewayProvider::from($payment->provider_code),
            amountKopecks: $refundAmount,
            correlationId: $correlationId,
            description: "Refund payment #{$payment->id}",
            returnUrl: '',
            tenantId: $payment->tenant_id,
            providerPaymentId: $payment->provider_payment_id,
        );

        $gatewayResponse = $this->gateway->refundPayment($gatewayRequest);

        // Update payment status
        $status = $amountKopecks === null ? PaymentStatus::REFUNDED : PaymentStatus::PARTIALLY_REFUNDED;

        $updateDto = new UpdatePaymentRecordDto(
            paymentRecordId: $paymentId,
            status: $status->value,
            correlationId: $correlationId,
        );

        return $this->paymentService->updateStatus($updateDto);
    }

    /**
     * Get current user ID.
     */
    private function getCurrentUserId(): ?int
    {
        $user = $this->guard->user();

        return $user?->getAuthIdentifier();
    }
}
