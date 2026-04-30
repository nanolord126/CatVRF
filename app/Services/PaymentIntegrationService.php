<?php

declare(strict_types=1);

namespace App\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Psr\Log\LoggerInterface;

use Illuminate\Http\Request;
use App\Models\PaymentTransaction;
use App\Services\Security\IdempotencyService;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use App\Domains\Wallet\Enums\BalanceTransactionType;

final readonly class PaymentIntegrationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly WalletService $wallet,
        private readonly FraudControlService $fraud,
        private readonly IdempotencyService $idempotency,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Инициация платежа (Hold или Capture)
     */
    public function processPayment(Order $order, array $params = []): array
    {
        $this->logger->channel('audit')->$this->logger->info('PaymentIntegrationService: initiating payment', [
            'order_id' => $order->id,
            'correlation_id' => $this->correlationId,
            'type' => $order->type,
        ]);

        return $this->db->transaction(function () use ($order, $params) {
            // 1. Idempotency Check
            $idempotencyKey = $params['idempotency_key'] ?? Str::uuid()->toString();
            $this->idempotency->check($idempotencyKey, $order->toArray());

            // 2. Fraud Control (ML Scoring)
            $this->fraud->check((int) $this->guard->id(), 'payment_init', $this->request->ip());

            // 3. Резерв 20 минут (включается для корзины и AI-конструкторов)
            if ($this->shouldReserve($order->type)) {
                $this->extendReservation($order);
            }

            // 4. Определение стратегии: Hold vs Capture
            $requestHold = $this->determineHoldStrategy($order->type);

            // 5. Вызов Wallet & Gateway
            $payment = PaymentTransaction::create([
                'uuid' => Str::uuid(),
                'tenant_id' => $order->tenant_id,
                'order_id' => $order->id,
                'amount' => $order->amount,
                'status' => 'pending',
                'is_hold' => $requestHold,
                'idempotency_key' => $idempotencyKey,
                'correlation_id' => $this->correlationId(),
            ]);

            $this->logger->channel('audit')->$this->logger->info('PaymentIntegrationService: payment recorded', [
                'payment_id' => $payment->id,
                'is_hold' => $requestHold,
            ]);

            return [
                'payment_uuid' => $payment->uuid,
                'is_hold' => $requestHold,
                'status' => 'initiated',
            ];
        });
    }

    /**
     * Подтверждение платежа (Capture) - после выполнения услуги
     */
    public function capture(string $paymentUuid): bool
    {
        return $this->db->transaction(function () use ($paymentUuid) {
            $payment = PaymentTransaction::where('uuid', $paymentUuid)->lockForUpdate()->firstOrFail();

            if ($payment->status !== 'authorized') {
                throw new \LogicException("Cannot capture payment in status: {$payment->status}");
            }

            // Списание из холда в кошелек продавца (минус комиссия 14%)
            $this->wallet->credit($payment->tenant_id, $payment->amount, BalanceTransactionType::PAYOUT, $correlationId, null, null, [
                'payment_uuid' => $paymentUuid,
            ]);

            return true;
        });
    }

    /**
     * Обработка Форс-мажора (Возврат + 10% компенсация от платформы)
     */
    public function handleForceMajeure(string $paymentUuid, string $reason): bool
    {
        return $this->db->transaction(function () use ($paymentUuid, $reason) {
            $payment = PaymentTransaction::where('uuid', $paymentUuid)->lockForUpdate()->firstOrFail();

            // 1. Полный возврат клиенту
            $correlationId = Str::uuid()->toString();
            $this->wallet->credit($payment->user_id, $payment->amount, BalanceTransactionType::REFUND, $correlationId, null, null, [
                'payment_uuid' => $paymentUuid,
                'reason' => $reason,
            ]);

            // 2. 10% компенсация от платформы
            $compensation = (int) ($payment->amount * 0.10);
            $this->wallet->credit($payment->user_id, $compensation, BalanceTransactionType::BONUS, $correlationId, null, null, [
                'payment_uuid' => $paymentUuid,
                'type' => 'force_majeure_compensation',
            ]);

            $payment->update(['status' => 'refunded', 'correlation_id' => $correlationId]);

            return true;
        });
    }

    private function correlationId(): string
    {
        return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
    }
}
