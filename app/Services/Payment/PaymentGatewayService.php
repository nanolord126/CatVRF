<?php declare(strict_types=1);

namespace App\Services\Payment;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Http\Request;
use App\Models\PaymentTransaction;
use App\Services\Payment\Gateways\SberGateway;
use App\Services\Payment\Gateways\TinkoffGateway;
use App\Services\Payment\Gateways\TochkaGateway;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;

/**
 * PaymentGatewayService - Isolated payment gateway operations.
 *
 * CRITICAL: Gateway calls are NEVER wrapped in DB::transaction to prevent
 * connection holding during timeouts. Uses circuit breaker for resilience.
 *
 * @final
 */
final class PaymentGatewayService
{
    private const CIRCUIT_BREAKER_THRESHOLD = 5; // Failures before opening
    private const CIRCUIT_BREAKER_TIMEOUT = 60; // Seconds before trying again

    public function __construct(
        private readonly Request $request,
        private readonly TinkoffGateway $tinkoff,
        private readonly TochkaGateway $tochka,
        private readonly SberGateway $sber,
        private readonly LogManager $logger,
        private readonly Guard $guard,
        private readonly RedisFactory $redis,
        private readonly LoggerInterface $log,
    ) {}

    /**
     * Инициировать платёж через шлюз (без DB::transaction).
     *
     * CRITICAL: Gateway call is NOT wrapped in DB transaction to prevent
     * connection holding during timeouts. Caller must handle transaction.
     *
     * @param array $data Данные платежа: amount, provider, currency
     * @param string $correlationId
     * @return array Gateway response
     * @throws \RuntimeException If circuit breaker is open or gateway fails
     */
    public function initiatePayment(array $data, string $correlationId): array
    {
        $provider = $data['provider'] ?? 'tinkoff';
        
        $this->checkCircuitBreaker($provider);

        $this->logger->channel('audit')->info('Payment gateway initiation started', [
            'correlation_id' => $correlationId,
            'provider' => $provider,
            'amount' => $data['amount'],
        ]);

        $startTime = microtime(true);

        try {
            $response = match ($provider) {
                'tinkoff' => $this->tinkoff->initiate($data, $correlationId),
                'tochka' => $this->tochka->initiate($data, $correlationId),
                'sber' => $this->sber->initiate($data, $correlationId),
                default => throw new \InvalidArgumentException("Unsupported provider: {$provider}"),
            };

            $latencyMs = (microtime(true) - $startTime) * 1000;

            $this->recordSuccess($provider, $latencyMs);

            $this->logger->channel('audit')->info('Payment gateway initiation successful', [
                'correlation_id' => $correlationId,
                'provider' => $provider,
                'latency_ms' => $latencyMs,
            ]);

            return $response;
        } catch (\Throwable $e) {
            $latencyMs = (microtime(true) - $startTime) * 1000;

            $this->recordFailure($provider, $latencyMs);

            $this->logger->channel('audit')->error('Payment gateway initiation failed', [
                'correlation_id' => $correlationId,
                'provider' => $provider,
                'error' => $e->getMessage(),
                'latency_ms' => $latencyMs,
            ]);

            throw new \RuntimeException(
                sprintf('Gateway %s failed: %s', $provider, $e->getMessage()),
                previous: $e,
            );
        }
    }

    /**
     * Захватить (списать) платёж через шлюз (без DB::transaction).
     *
     * CRITICAL: Gateway call is NOT wrapped in DB transaction.
     *
     * @param PaymentTransaction $transaction
     * @param string $correlationId
     * @return array Gateway response
     * @throws \RuntimeException If circuit breaker is open or gateway fails
     */
    public function capture(PaymentTransaction $transaction, string $correlationId): array
    {
        $provider = $transaction->provider;
        
        $this->checkCircuitBreaker($provider);

        $this->logger->channel('audit')->info('Payment gateway capture started', [
            'correlation_id' => $correlationId,
            'payment_id' => $transaction->id,
            'provider' => $provider,
        ]);

        $startTime = microtime(true);

        try {
            $response = match ($provider) {
                'tinkoff' => $this->tinkoff->capture($transaction, $correlationId),
                'tochka' => $this->tochka->capture($transaction, $correlationId),
                'sber' => $this->sber->capture($transaction, $correlationId),
                default => throw new \InvalidArgumentException("Unsupported provider: {$provider}"),
            };

            $latencyMs = (microtime(true) - $startTime) * 1000;

            $this->recordSuccess($provider, $latencyMs);

            $this->logger->channel('audit')->info('Payment gateway capture successful', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'provider' => $provider,
                'latency_ms' => $latencyMs,
            ]);

            return $response;
        } catch (\Throwable $e) {
            $latencyMs = (microtime(true) - $startTime) * 1000;

            $this->recordFailure($provider, $latencyMs);

            $this->logger->channel('audit')->error('Payment gateway capture failed', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
                'latency_ms' => $latencyMs,
            ]);

            throw new \RuntimeException(
                sprintf('Capture failed for provider %s: %s', $provider, $e->getMessage()),
                previous: $e,
            );
        }
    }

    /**
     * Возвратить платёж через шлюз (без DB::transaction).
     *
     * CRITICAL: Gateway call is NOT wrapped in DB transaction.
     *
     * @param PaymentTransaction $transaction
     * @param int $amount Сумма в копейках
     * @param string $reason Причина возврата
     * @param string $correlationId
     * @return array Gateway response
     * @throws \RuntimeException If circuit breaker is open or gateway fails
     */
    public function refund(PaymentTransaction $transaction, int $amount, string $reason, string $correlationId): array
    {
        $provider = $transaction->provider;
        
        $this->checkCircuitBreaker($provider);

        $this->logger->channel('audit')->info('Payment gateway refund started', [
            'correlation_id' => $correlationId,
            'payment_id' => $transaction->id,
            'provider' => $provider,
            'refund_amount' => $amount,
        ]);

        $startTime = microtime(true);

        try {
            $response = match ($provider) {
                'tinkoff' => $this->tinkoff->refund($transaction, $amount, $reason, $correlationId),
                'tochka' => $this->tochka->refund($transaction, $amount, $reason, $correlationId),
                'sber' => $this->sber->refund($transaction, $amount, $reason, $correlationId),
                default => throw new \InvalidArgumentException("Unsupported provider: {$provider}"),
            };

            $latencyMs = (microtime(true) - $startTime) * 1000;

            $this->recordSuccess($provider, $latencyMs);

            $this->logger->channel('audit')->info('Payment gateway refund successful', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'provider' => $provider,
                'refund_amount' => $amount,
                'latency_ms' => $latencyMs,
            ]);

            return $response;
        } catch (\Throwable $e) {
            $latencyMs = (microtime(true) - $startTime) * 1000;

            $this->recordFailure($provider, $latencyMs);

            $this->logger->channel('audit')->error('Payment gateway refund failed', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
                'latency_ms' => $latencyMs,
            ]);

            throw new \RuntimeException(
                sprintf('Refund failed for provider %s: %s', $provider, $e->getMessage()),
                previous: $e,
            );
        }
    }

    /**
     * Get payment status from gateway.
     *
     * @param PaymentTransaction $transaction
     * @param string $correlationId
     * @return array Gateway response
     */
    public function getStatus(PaymentTransaction $transaction, string $correlationId): array
    {
        $provider = $transaction->provider;

        try {
            $response = match ($provider) {
                'tinkoff' => $this->tinkoff->getStatus($transaction, $correlationId),
                'tochka' => $this->tochka->getStatus($transaction, $correlationId),
                'sber' => $this->sber->getStatus($transaction, $correlationId),
                default => throw new \InvalidArgumentException("Unsupported provider: {$provider}"),
            };

            return $response;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Payment gateway status check failed', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // ─── Circuit Breaker ────────────────────────────────────────────────

    /**
     * Check if circuit breaker is open for a provider.
     *
     * @throws \RuntimeException If circuit is open
     */
    private function checkCircuitBreaker(string $provider): void
    {
        $key = $this->circuitBreakerKey($provider);
        $state = $this->redis->connection()->get($key);

        if ($state === 'open') {
            throw new \RuntimeException(
                sprintf('Circuit breaker open for provider %s - gateway unavailable', $provider),
            );
        }
    }

    /**
     * Record successful gateway call.
     */
    private function recordSuccess(string $provider, float $latencyMs): void
    {
        $key = $this->failureCountKey($provider);
        $this->redis->connection()->del($key);

        // Record metrics for Prometheus
        $this->log->info('payment_gateway_success', [
            'provider' => $provider,
            'latency_ms' => $latencyMs,
        ]);
    }

    /**
     * Record failed gateway call and open circuit if threshold reached.
     */
    private function recordFailure(string $provider, float $latencyMs): void
    {
        $key = $this->failureCountKey($provider);
        $count = $this->redis->connection()->incr($key);

        if ($count === 1) {
            $this->redis->connection()->expire($key, self::CIRCUIT_BREAKER_TIMEOUT);
        }

        if ($count >= self::CIRCUIT_BREAKER_THRESHOLD) {
            $circuitKey = $this->circuitBreakerKey($provider);
            $this->redis->connection()->setex($circuitKey, self::CIRCUIT_BREAKER_TIMEOUT, 'open');

            $this->logger->warning('Circuit breaker opened', [
                'provider' => $provider,
                'failure_count' => $count,
            ]);
        }

        // Record metrics for Prometheus
        $this->log->warning('payment_gateway_failure', [
            'provider' => $provider,
            'latency_ms' => $latencyMs,
            'failure_count' => $count,
        ]);
    }

    private function failureCountKey(string $provider): string
    {
        return "payment:circuit:{$provider}:failures";
    }

    private function circuitBreakerKey(string $provider): string
    {
        return "payment:circuit:{$provider}:state";
    }
}
