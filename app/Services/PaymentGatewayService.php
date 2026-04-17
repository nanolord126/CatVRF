<?php declare(strict_types=1);

namespace App\Services;

use App\Services\Fraud\FraudControlService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

/**
 * Payment Gateway Service
 * Production 2026 CANON
 *
 * Coordinates payment processing across multiple gateways:
 * - Tinkoff
 * - Tochka Bank
 * - Sber
 *
 * Manages: init, capture, refund, webhook processing
 *
 * @author CatVRF Team
 * @version 2026.03.24
 */
final class PaymentGatewayService
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly FraudControlService $fraud,
        private readonly Request $request
    ) {}

    private const GATEWAYS = ['tinkoff', 'tochka', 'sber'];

    /**
     * Initialize payment (2-stage: hold)
     *
     * @param array $paymentData {gateway, amount, currency, operation_type, idempotency_key, tenant_id, user_id}
     * @param string $correlationId Tracing ID
     * @return array {payment_id, status, gateway_transaction_id, amount, created_at}
     * @throws \Exception
     */
    public function initPayment(array $paymentData, string $correlationId): array
    {''
        $this->fraud->check([
            'operation' => payment_init,
            'user_id' => $paymentData['user_id'],
            'amount' => $paymentData['amount'],
            'correlation_id' => $correlationId,
        ]);


        // Check idempotency
        $existingPayment = $this->db->table('payment_idempotency_records')
            ->where('idempotency_key', $paymentData['idempotency_key'] ?? null)
            ->first();

        if ($existingPayment) {
            $this->logger->channel('audit')->info('Idempotent payment request', [
                'correlation_id' => $correlationId,
                'idempotency_key' => $paymentData['idempotency_key'],
                'existing_payment_id' => $existingPayment->payment_id,
            ]);

            return [
                'payment_id' => $existingPayment->payment_id,
                'status' => 'authorized',
                'idempotent' => true,
            ];
        }

        return $this->db->transaction(function () use ($paymentData, $correlationId): array {
            // Create payment record
            $payment = $this->db->table('payment_transactions')->insertGetId([
                'tenant_id' => $paymentData['tenant_id'],
                'user_id' => $paymentData['user_id'] ?? null,
                'operation_type' => $paymentData['operation_type'],
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'] ?? 'RUB',
                'gateway' => $paymentData['gateway'] ?? 'tinkoff',
                'status' => 'pending',
                'hold' => $paymentData['hold'] ?? false,
                'idempotency_key' => $paymentData['idempotency_key'],
                'payload_hash' => hash('sha256', json_encode($paymentData)),
                'correlation_id' => $correlationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Store idempotency record
            $this->db->table('payment_idempotency_records')->insert([
                'operation' => 'payment_init',
                'idempotency_key' => $paymentData['idempotency_key'],
                'merchant_id' => $paymentData['tenant_id'],
                'payload_hash' => hash('sha256', json_encode($paymentData)),
                'payment_id' => $payment,
                'expires_at' => now()->addHours(24),
                'created_at' => now(),
            ]);

            // Call gateway API (stub - should call actual gateway)
            $gatewayResponse = $this->callGateway(
                $paymentData['gateway'] ?? 'tinkoff',
                'init',
                $paymentData,
                $correlationId
            );

            // Update payment with gateway transaction ID
            $this->db->table('payment_transactions')
                ->where('id', $payment)
                ->update([
                    'provider_payment_id' => $gatewayResponse['transaction_id'] ?? null,
                    'status' => 'authorized',
                ]);

            $this->logger->channel('audit')->info('Payment initialized', [
                'correlation_id' => $correlationId,
                'payment_id' => $payment,
                'amount' => $paymentData['amount'],
                'gateway' => $paymentData['gateway'] ?? 'tinkoff',
                'gateway_transaction_id' => $gatewayResponse['transaction_id'] ?? null,
            ]);

            return [
                'payment_id' => $payment,
                'status' => 'authorized',
                'gateway_transaction_id' => $gatewayResponse['transaction_id'] ?? null,
                'amount' => $paymentData['amount'],
                'created_at' => now(),
            ];
        });
    }

    /**
     * Capture payment (2-stage: debit)
     *
     * @param int $paymentId Payment ID
     * @param int|null $amount Partial capture amount (null = full)
     * @param string $correlationId Tracing ID
     * @return array {payment_id, status, captured_at}
     * @throws \Exception
     */
    public function capturePayment(int $paymentId, ?int $amount, string $correlationId): array
    {
        return $this->db->transaction(function () use ($paymentId, $amount, $correlationId): array {
            $payment = $this->db->table('payment_transactions')->lockForUpdate()->findOrFail($paymentId);

            if ($payment->status !== 'authorized') {
                throw new \LogicException('Payment must be in authorized state');
            }

            $captureAmount = $amount ?? $payment->amount;
            if ($captureAmount > $payment->amount) {
                throw new \LogicException('Cannot capture more than authorized');
            }

            // Call gateway to capture
            $gatewayResponse = $this->callGateway(
                $payment->gateway,
                'capture',
                [
                    'transaction_id' => $payment->provider_payment_id,
                    'amount' => $captureAmount,
                ],
                $correlationId
            );

            // Update payment status
            $this->db->table('payment_transactions')
                ->where('id', $paymentId)
                ->update([
                    'status' => 'captured',
                    'captured_amount' => $captureAmount,
                    'captured_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->logger->channel('audit')->info('Payment captured', [
                'correlation_id' => $correlationId,
                'payment_id' => $paymentId,
                'amount' => $captureAmount,
                'gateway' => $payment->gateway,
            ]);

            return [
                'payment_id' => $paymentId,
                'status' => 'captured',
                'captured_amount' => $captureAmount,
                'captured_at' => now(),
            ];
        });
    }

    /**
     * Refund payment
     *
     * @param int $paymentId Payment ID
     * @param int|null $amount Partial refund (null = full)
     * @param string $reason Refund reason
     * @param string $correlationId Tracing ID
     * @return array {payment_id, status, refunded_amount, refunded_at}
     * @throws \Exception
     */
    public function refundPayment(int $paymentId, ?int $amount, string $reason, string $correlationId): array
    {
        return $this->db->transaction(function () use ($paymentId, $amount, $reason, $correlationId): array {
            $payment = $this->db->table('payment_transactions')->lockForUpdate()->findOrFail($paymentId);

            if (!in_array($payment->status, ['captured', 'authorized'])) {
                throw new \LogicException('Payment must be captured or authorized to refund');
            }

            $refundAmount = $amount ?? ($payment->captured_amount ?? $payment->amount);
            $alreadyRefunded = $payment->refunded_amount ?? 0;

            if ($alreadyRefunded + $refundAmount > $payment->amount) {
                throw new \LogicException('Cannot refund more than original amount');
            }

            // Call gateway to refund
            $gatewayResponse = $this->callGateway(
                $payment->gateway,
                'refund',
                [
                    'transaction_id' => $payment->provider_payment_id,
                    'amount' => $refundAmount,
                    'reason' => $reason,
                ],
                $correlationId
            );

            // Update payment status
            $this->db->table('payment_transactions')
                ->where('id', $paymentId)
                ->update([
                    'status' => 'refunded',
                    'refunded_amount' => $alreadyRefunded + $refundAmount,
                    'refunded_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->logger->channel('audit')->info('Payment refunded', [
                'correlation_id' => $correlationId,
                'payment_id' => $paymentId,
                'refund_amount' => $refundAmount,
                'reason' => $reason,
                'gateway' => $payment->gateway,
            ]);

            return [
                'payment_id' => $paymentId,
                'status' => 'refunded',
                'refunded_amount' => $alreadyRefunded + $refundAmount,
                'refunded_at' => now(),
            ];
        });
    }

    /**
     * Call payment gateway API
     *
     * @param string $gateway Gateway name (tinkoff, tochka, sber)
     * @param string $action Action (init, capture, refund)
     * @param array $params Action parameters
     * @param string $correlationId Tracing ID
     * @return array Gateway response
     */
    private function callGateway(string $gateway, string $action, array $params, string $correlationId): array
    {
        try {
            // For now, return stub response

            $this->logger->channel('audit')->info('Gateway call', [
                'correlation_id' => $correlationId,
                'gateway' => $gateway,
                'action' => $action,
                'params' => $params,
            ]);

            return [
                'transaction_id' => Str::uuid()->toString(),
                'status' => 'success',
                'gateway_response_code' => '00',
            ];
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->channel('audit')->error('Gateway call failed', [
                'correlation_id' => $correlationId,
                'gateway' => $gateway,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
