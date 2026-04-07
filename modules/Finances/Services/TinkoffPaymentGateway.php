<?php

declare(strict_types=1);

namespace Modules\Finances\Services;

use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Modules\Finances\Data\PaymentTransactionData;
use Modules\Finances\Enums\PaymentStatus;
use Modules\Finances\Exceptions\PaymentGatewayException;
use Modules\Finances\Interfaces\PaymentGatewayInterface;
use Modules\Finances\Models\PaymentIdempotencyRecord;
use Modules\Finances\Models\PaymentTransaction;

final class TinkoffPaymentGateway implements PaymentGatewayInterface
{
    private readonly string $apiUrl;
    private readonly string $terminalKey;
    private readonly string $secretKey;

    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly WalletService $walletService
    ) {
        $this->apiUrl = config('services.tinkoff.url');
        $this->terminalKey = config('services.tinkoff.terminal_key');
        $this->secretKey = config('services.tinkoff.secret_key');
    }

    public function initPayment(int $tenantId, int $amount, string $orderId, string $correlationId, bool $hold = false, ?array $meta = null): PaymentTransactionData
    {
        $this->fraudControl->check();
        RateLimiter::hit('payment-init:' . $orderId);

        $payload = [
            'TerminalKey' => $this->terminalKey,
            'Amount' => $amount,
            'OrderId' => $orderId,
            'Recurrent' => 'Y',
            'DATA' => $meta,
        ];

        $this->checkIdempotency('init', $orderId, $payload);

        $payload['Token'] = $this->generateToken($payload);

        $response = Http::post("{$this->apiUrl}/Init", $payload);

        if (!$response->successful() || $response->json('Success') !== true) {
            Log::channel('audit')->error('Tinkoff Init failed', [
                'response' => $response->body(),
                'correlation_id' => $correlationId,
            ]);
            throw new PaymentGatewayException('Tinkoff Init failed: ' . $response->json('Message'));
        }

        $data = $response->json();
        $status = $hold ? PaymentStatus::AUTHORIZED : PaymentStatus::PENDING;

        $transaction = PaymentTransaction::create([
            'tenant_id' => $tenantId,
            'uuid' => Str::uuid()->toString(),
            'idempotency_key' => $orderId,
            'provider_code' => 'tinkoff',
            'provider_payment_id' => $data['PaymentId'],
            'status' => $status,
            'amount' => $data['Amount'],
            'hold' => $hold,
            'correlation_id' => $correlationId,
            'meta' => $data,
        ]);

        $this->saveIdempotencyRecord('init', $orderId, $payload, $data, $tenantId);

        return PaymentTransactionData::from($transaction);
    }

    public function capture(string $paymentId, int $amount, string $correlationId): bool
    {
        $this->fraudControl->check();
        $payload = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $paymentId,
            'Amount' => $amount,
        ];
        $payload['Token'] = $this->generateToken($payload);

        $response = Http::post("{$this->apiUrl}/Confirm", $payload);

        if (!$response->successful() || $response->json('Success') !== true) {
            Log::channel('audit')->error('Tinkoff Capture failed', [
                'payment_id' => $paymentId,
                'response' => $response->body(),
                'correlation_id' => $correlationId,
            ]);
            return false;
        }

        DB::transaction(function () use ($paymentId, $correlationId) {
            $transaction = PaymentTransaction::where('provider_payment_id', $paymentId)->firstOrFail();
            $transaction->status = PaymentStatus::CAPTURED;
            $transaction->captured_at = now();
            $transaction->save();

            // Here you would typically call the WalletService to credit the funds
            // For example: $this->walletService->credit(...)
        });

        Log::channel('audit')->info('Tinkoff payment captured.', [
            'payment_id' => $paymentId,
            'correlation_id' => $correlationId,
        ]);

        return true;
    }

    public function refund(string $paymentId, int $amount, string $correlationId): bool
    {
        $this->fraudControl->check();
        $payload = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $paymentId,
            'Amount' => $amount,
        ];
        $payload['Token'] = $this->generateToken($payload);

        $response = Http::post("{$this->apiUrl}/Cancel", $payload);

        if (!$response->successful() || $response->json('Success') !== true) {
            Log::channel('audit')->error('Tinkoff Refund failed', [
                'payment_id' => $paymentId,
                'response' => $response->body(),
                'correlation_id' => $correlationId,
            ]);
            return false;
        }

        DB::transaction(function () use ($paymentId, $correlationId) {
            $transaction = PaymentTransaction::where('provider_payment_id', $paymentId)->firstOrFail();
            $transaction->status = PaymentStatus::REFUNDED;
            $transaction->refunded_at = now();
            $transaction->save();

            // Here you would typically call the WalletService to debit the funds
        });

        Log::channel('audit')->info('Tinkoff payment refunded.', [
            'payment_id' => $paymentId,
            'correlation_id' => $correlationId,
        ]);

        return true;
    }

    public function getStatus(string $paymentId, string $correlationId): PaymentStatus
    {
        $payload = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $paymentId,
        ];
        $payload['Token'] = $this->generateToken($payload);

        $response = Http::post("{$this->apiUrl}/GetState", $payload);

        if (!$response->successful() || $response->json('Success') !== true) {
            Log::channel('audit')->error('Tinkoff GetStatus failed', [
                'payment_id' => $paymentId,
                'response' => $response->body(),
                'correlation_id' => $correlationId,
            ]);
            throw new PaymentGatewayException('Tinkoff GetStatus failed: ' . $response->json('Message'));
        }

        $status = $response->json('Status');
        return PaymentStatus::fromTinkoff($status);
    }

    public function handleWebhook(array $payload): void
    {
        $localToken = $this->generateToken($payload);
        if (!hash_equals($localToken, $payload['Token'])) {
            throw new PaymentGatewayException('Invalid webhook token.');
        }

        $paymentId = $payload['PaymentId'];
        $status = PaymentStatus::fromTinkoff($payload['Status']);
        $correlationId = $payload['OrderId'] ?? Str::uuid()->toString();

        DB::transaction(function () use ($paymentId, $status, $payload, $correlationId) {
            $transaction = PaymentTransaction::where('provider_payment_id', $paymentId)->firstOrFail();

            if ($transaction->status === $status) {
                return; // Status already updated
            }

            $transaction->status = $status;
            $transaction->meta = array_merge($transaction->meta ?? [], ['webhook_payload' => $payload]);

            if ($status === PaymentStatus::CAPTURED) {
                $transaction->captured_at = now();
                // Call WalletService to credit funds
            } elseif ($status === PaymentStatus::REFUNDED) {
                $transaction->refunded_at = now();
                // Call WalletService to handle refund
            }

            $transaction->save();

            Log::channel('audit')->info('Tinkoff webhook handled.', [
                'payment_id' => $paymentId,
                'new_status' => $status->value,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function fiscalize(string $paymentId, array $receiptData, string $correlationId): bool
    {
        // This is a simplified example. Real implementation requires detailed receipt structure.
        $payload = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $paymentId,
            'Receipt' => $receiptData,
        ];
        $payload['Token'] = $this->generateToken($payload);

        $response = Http::post("{$this->apiUrl}/SendClosingReceipt", $payload);

        if (!$response->successful() || $response->json('Success') !== true) {
            Log::channel('audit')->error('Tinkoff Fiscalization failed', [
                'payment_id' => $paymentId,
                'response' => $response->body(),
                'correlation_id' => $correlationId,
            ]);
            return false;
        }

        Log::channel('audit')->info('Tinkoff payment fiscalized.', [
            'payment_id' => $paymentId,
            'correlation_id' => $correlationId,
        ]);

        return true;
    }

    private function generateToken(array $params): string
    {
        $params['Password'] = $this->secretKey;
        unset($params['DATA'], $params['Token'], $params['Receipt']);
        ksort($params);
        $values = implode('', array_values($params));
        return hash('sha256', $values);
    }

    private function checkIdempotency(string $operation, string $key, array $payload): void
    {
        $payloadHash = hash('sha256', json_encode($payload));
        $record = PaymentIdempotencyRecord::where('idempotency_key', $key)
            ->where('operation', $operation)
            ->first();

        if ($record && $record->payload_hash !== $payloadHash) {
            throw new PaymentGatewayException('Idempotency key conflict with different payload.');
        }
    }

    private function saveIdempotencyRecord(string $operation, string $key, array $payload, array $response, int $tenantId): void
    {
        PaymentIdempotencyRecord::updateOrCreate(
            ['operation' => $operation, 'idempotency_key' => $key],
            [
                'merchant_id' => $tenantId,
                'payload_hash' => hash('sha256', json_encode($payload)),
                'response_data' => $response,
                'expires_at' => now()->addDay(),
            ]
        );
    }
}
