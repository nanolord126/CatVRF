<?php declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Services\Payment\Gateways\TinkoffGateway;
use App\Services\Payment\Gateways\TochkaGateway;
use App\Services\Payment\Gateways\SberGateway;
use App\Services\Fraud\FraudControlService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * PaymentGatewayService
 *
 * Обрабатывает инициацию, захват и возврат платежей через различные платёжные системы
 * (Tinkoff, Tochka, Sberbank). Все операции атомарны (DB::transaction), отслеживаются
 * по correlation_id и защищены от мошенничества (FraudControlService::check).
 *
 * @final
 */
final class PaymentGatewayService
{
    public function __construct(
        private readonly TinkoffGateway $tinkoff,
        private readonly TochkaGateway $tochka,
        private readonly SberGateway $sber,
        private readonly ConnectionInterface $db,
        private readonly LogManager $log,
        private readonly FraudControlService $fraud,
    ) {}

    /**
     * Инициировать платёж (создать PaymentTransaction и отправить на шлюз)
     *
     * @param array $data Данные платежа: amount, provider, currency, idempotency_key
     * @param int|string $tenantId
     * @param string $correlationId
     * @return PaymentTransaction
     * @throws \App\Exceptions\FraudException
     * @throws \Throwable
     */
    public function initPayment(array $data, int|string $tenantId, string $correlationId): PaymentTransaction
    {
        // 1. FRAUD CHECK ПЕРЕД ТРАНЗАКЦИЕЙ
        $this->fraud->check([
            'operation_type' => 'payment_init',
            'amount' => $data['amount'],
            'provider' => $data['provider'] ?? 'tinkoff',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'correlation_id' => $correlationId,
        ]);

        try {
            return DB::transaction(function () use ($data, $tenantId, $correlationId) {
                // 2. AUDIT LOG В НАЧАЛЕ ТРАНЗАКЦИИ
                Log::channel('audit')->info('Payment initialization started', [
                    'correlation_id' => $correlationId,
                    'amount' => $data['amount'],
                    'provider' => $data['provider'] ?? 'tinkoff',
                    'currency' => $data['currency'] ?? 'RUB',
                    'tenant_id' => $tenantId,
                ]);

                // 3. СОЗДАТЬ ТРАНЗАКЦИЮ С ИДЕМПОТЕНТНОСТЬЮ
                $idempotencyKey = $data['idempotency_key'] ?? Str::uuid()->toString();
                $transaction = PaymentTransaction::create([
                    'tenant_id' => $tenantId,
                    'idempotency_key' => $idempotencyKey,
                    'provider' => $data['provider'] ?? 'tinkoff',
                    'amount' => $data['amount'],
                    'currency' => $data['currency'] ?? 'RUB',
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                    'user_id' => auth()->id(),
                ]);

                Log::channel('audit')->info('Payment transaction created', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                    'idempotency_key' => $idempotencyKey,
                ]);

                return $transaction;
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Payment initialization failed', [
                'correlation_id' => $correlationId,
                'amount' => $data['amount'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Захватить (списать) платёж
     *
     * @param PaymentTransaction $transaction
     * @param string $correlationId
     * @return bool
     * @throws \App\Exceptions\FraudException
     * @throws \Throwable
     */
    public function capture(PaymentTransaction $transaction, string $correlationId): bool
    {
        // 1. FRAUD CHECK ПЕРЕД ТРАНЗАКЦИЕЙ
        $this->fraud->check([
            'operation_type' => 'payment_capture',
            'amount' => $transaction->amount,
            'user_id' => $transaction->user_id,
            'ip_address' => request()->ip(),
            'correlation_id' => $correlationId,
        ]);

        try {
            return DB::transaction(function () use ($transaction, $correlationId) {
                // 2. AUDIT LOG В НАЧАЛЕ ТРАНЗАКЦИИ
                Log::channel('audit')->info('Payment capture started', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'user_id' => $transaction->user_id,
                    'provider' => $transaction->provider,
                ]);

                // 3. ВЫПОЛНИТЬ ЗАХВАТ ЧЕРЕЗ ШЛЮЗ
                $result = match ($transaction->provider) {
                    'tinkoff' => $this->tinkoff->capture($transaction, $correlationId),
                    'tochka' => $this->tochka->capture($transaction, $correlationId),
                    'sber' => $this->sber->capture($transaction, $correlationId),
                    default => throw new \InvalidArgumentException("Unknown payment provider: {$transaction->provider}"),
                };

                // 4. ОБНОВИТЬ СТАТУС
                if ($result) {
                    $transaction->update([
                        'status' => 'captured',
                        'captured_at' => now(),
                        'correlation_id' => $correlationId,
                    ]);

                    Log::channel('audit')->info('Payment captured successfully', [
                        'correlation_id' => $correlationId,
                        'payment_id' => $transaction->id,
                        'gateway' => $transaction->provider,
                    ]);
                }

                return $result;
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Payment capture failed', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Возвратить платёж (рефанд)
     *
     * @param PaymentTransaction $transaction
     * @param int $amount Сумма в копейках
     * @param string $correlationId
     * @return bool
     * @throws \App\Exceptions\FraudException
     * @throws \Throwable
     */
    public function refund(PaymentTransaction $transaction, int $amount, string $correlationId): bool
    {
        // 1. FRAUD CHECK ПЕРЕД ТРАНЗАКЦИЕЙ
        $this->fraud->check([
            'operation_type' => 'payment_refund',
            'amount' => $amount,
            'user_id' => $transaction->user_id,
            'ip_address' => request()->ip(),
            'correlation_id' => $correlationId,
        ]);

        try {
            return DB::transaction(function () use ($transaction, $amount, $correlationId) {
                // 2. AUDIT LOG В НАЧАЛЕ ТРАНЗАКЦИИ
                Log::channel('audit')->info('Payment refund initiated', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                    'refund_amount' => $amount,
                    'original_amount' => $transaction->amount,
                    'user_id' => $transaction->user_id,
                    'provider' => $transaction->provider,
                ]);

                // 3. ВЫПОЛНИТЬ ВОЗВРАТ ЧЕРЕЗ ШЛЮЗ
                $result = match ($transaction->provider) {
                    'tinkoff' => $this->tinkoff->refund($transaction, $amount, $correlationId),
                    'tochka' => $this->tochka->refund($transaction, $amount, $correlationId),
                    'sber' => $this->sber->refund($transaction, $amount, $correlationId),
                    default => throw new \InvalidArgumentException("Unknown payment provider: {$transaction->provider}"),
                };

                // 4. ОБНОВИТЬ СТАТУС И СУММЫ
                if ($result) {
                    $transaction->update([
                        'status' => 'refunded',
                        'refunded_at' => now(),
                        'refunded_amount' => ($transaction->refunded_amount ?? 0) + $amount,
                        'correlation_id' => $correlationId,
                    ]);

                    Log::channel('audit')->info('Payment refunded successfully', [
                        'correlation_id' => $correlationId,
                        'payment_id' => $transaction->id,
                        'refunded_amount' => $amount,
                        'gateway' => $transaction->provider,
                    ]);
                }

                return $result;
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Payment refund failed', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'refund_amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
