<?php declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Services\Payment\Gateways\TinkoffGateway;
use App\Services\Payment\Gateways\TochkaGateway;
use App\Services\Payment\Gateways\SberGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PaymentGatewayService
{
    public function __construct(
        private readonly TinkoffGateway $tinkoff,
        private readonly TochkaGateway $tochka,
        private readonly SberGateway $sber,
    ) {}

    public function initPayment(array $data, int|string $tenantId, string $correlationId): PaymentTransaction
    {
        return DB::transaction(function () use ($data, $tenantId, $correlationId) {
            Log::channel('audit')->info('Initializing payment', [
                'correlation_id' => $correlationId,
                'amount' => $data['amount'],
                'provider' => $data['provider'] ?? 'tinkoff',
            ]);

            $idempotencyKey = $data['idempotency_key'] ?? Str::uuid()->toString();
            $transaction = PaymentTransaction::create([
                'tenant_id' => $tenantId,
                'idempotency_key' => $idempotencyKey,
                'provider' => $data['provider'] ?? 'tinkoff',
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'RUB',
                'status' => 'pending',
                'correlation_id' => $correlationId,
            ]);

            return $transaction;
        });
    }

    public function capture(PaymentTransaction $transaction, string $correlationId): bool
    {
        return DB::transaction(function () use ($transaction, $correlationId) {
            Log::channel('audit')->info('Capturing payment', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
            ]);

            $result = match ($transaction->provider) {
                'tinkoff' => $this->tinkoff->capture($transaction),
                'tochka' => $this->tochka->capture($transaction),
                'sber' => $this->sber->capture($transaction),
                default => false,
            };

            if ($result) {
                $transaction->update(['status' => 'captured', 'captured_at' => now()]);
            }

            return $result;
        });
    }

    public function refund(PaymentTransaction $transaction, int $amount, string $correlationId): bool
    {
        return DB::transaction(function () use ($transaction, $amount, $correlationId) {
            Log::channel('audit')->info('Refunding payment', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'amount' => $amount,
            ]);

            $result = match ($transaction->provider) {
                'tinkoff' => $this->tinkoff->refund($transaction, $amount),
                'tochka' => $this->tochka->refund($transaction, $amount),
                'sber' => $this->sber->refund($transaction, $amount),
                default => false,
            };

            if ($result) {
                $transaction->update(['status' => 'refunded', 'refunded_at' => now()]);
            }

            return $result;
        });
    }
}
