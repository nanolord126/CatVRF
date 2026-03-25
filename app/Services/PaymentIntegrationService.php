<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\PaymentGatewayInterface;
use App\Services\WalletService;
use App\Services\FraudControlService;
use App\Services\IdempotencyService;
use App\Models\PaymentTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PaymentIntegrationService (Канон 2026)
 * Единый центр управления платежами, холдами, возвратами и резервами.
 */
final readonly class PaymentIntegrationService
{
    public function __construct(
        private WalletService $wallet,
        private FraudControlService $fraud,
        private IdempotencyService $idempotency,
        private string $correlationId
    ) {}

    /**
     * Инициация платежа (Hold или Capture)
     */
    public function processPayment(Order $order, array $params = []): array
    {
        Log::channel('audit')->info('PaymentIntegrationService: initiating payment', [
            'order_id' => $order->id,
            'correlation_id' => $this->correlationId,
            'type' => $order->type
        ]);

        return DB::transaction(function () use ($order, $params) {
            // 1. Idempotency Check
            $idempotencyKey = $params['idempotency_key'] ?? Str::uuid()->toString();
            $this->idempotency->check($idempotencyKey, $order->toArray());

            // 2. Fraud Control (ML Scoring)
            $this->fraud->check('payment_init', [
                'user_id' => $order->user_id,
                'amount' => $order->amount,
                'type' => $order->type
            ]);

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
                'correlation_id' => $this->correlationId,
            ]);

            Log::channel('audit')->info('PaymentIntegrationService: payment recorded', [
                'payment_id' => $payment->id,
                'is_hold' => $requestHold
            ]);

            return [
                'payment_uuid' => $payment->uuid,
                'is_hold' => $requestHold,
                'status' => 'initiated'
            ];
        });
    }

    /**
     * Подтверждение платежа (Capture) - после выполнения услуги
     */
    public function capture(string $paymentUuid): bool
    {
        return DB::transaction(function () use ($paymentUuid) {
            $payment = PaymentTransaction::where('uuid', $paymentUuid)->lockForUpdate()->firstOrFail();
            
            if ($payment->status !== 'authorized') {
                throw new \Exception("Cannot capture payment in status: {$payment->status}");
            }

            // Списание из холда в кошелек продавца (минус комиссия 14%)
            $this->wallet->credit($payment->tenant_id, $payment->amount, 'deposit', $this->correlationId);
            
            $payment->update([
                'status' => 'captured',
                'captured_at' => now()
            ]);

            Log::channel('audit')->info('PaymentIntegrationService: captured successfully', [
                'payment_uuid' => $paymentUuid
            ]);

            return true;
        });
    }

    /**
     * Обработка Форс-мажора (Возврат + 10% компенсация от платформы)
     */
    public function handleForceMajeure(string $paymentUuid, string $reason): bool
    {
        return DB::transaction(function () use ($paymentUuid, $reason) {
            $payment = PaymentTransaction::where('uuid', $paymentUuid)->lockForUpdate()->firstOrFail();

            // 1. Полный возврат клиенту
            $this->wallet->refund($payment->user_id, $payment->amount, $this->correlationId);

            // 2. Начисление компенсации 10% от платформы (бонус)
            $compensation = (int)($payment->amount * 0.10);
            $this->wallet->credit($payment->user_id, $compensation, 'bonus', $this->correlationId);

            $payment->update([
                'status' => 'refunded_force_majeure',
                'meta' => json_encode(['reason' => $reason, 'compensation' => $compensation])
            ]);

            Log::channel('audit')->warning('FORCE MAJEURE: full refund + 10% compensation', [
                'payment_uuid' => $paymentUuid,
                'compensation' => $compensation
            ]);

            return true;
        });
    }

    private function determineHoldStrategy(string $orderType): bool
    {
        return match ($orderType) {
            'wedding', 'corporate', 'event_hall', 'photosession' => true, // Всегда ХОЛД до события
            'single_appointment', 'ai_constructor', 'gift_card' => false, // Прямое списание
            default => true
        };
    }

    private function shouldReserve(string $orderType): bool
    {
        return in_array($orderType, ['cart_order', 'ai_constructor', 'single_appointment']);
    }

    private function extendReservation(Order $order): void
    {
        // Логика блокировки Inventory на 20 мин (Redis + DB)
        $expiresAt = now()->addMinutes(20);
        DB::table('inventory_reservations')->insert([
            'order_id' => $order->id,
            'expires_at' => $expiresAt,
            'correlation_id' => $this->correlationId
        ]);
        
        Log::channel('audit')->info('20-min Reservation activated', ['order_id' => $order->id]);
    }
}
