<?php

declare(strict_types=1);

namespace App\Domains\Payment\Controllers;

use App\Domains\Payment\Enums\PaymentProvider;
use App\Domains\Payment\Jobs\ProcessWebhookJob;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * Асинхронный контроллер вебхуков.
 * Не содержит тяжелой логики — только проверка ключа и постановка в очередь
 * для обеспечения пропускной способности 10 000+ запросов в час.
 */
final class WebhookController extends Controller
{
    public function __construct(
        private readonly RedisFactory $redis,
    ) {}

    /**
     * Обработка входящего вебхука от шлюза.
     */
    public function handle(string $provider, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID');
        if (!$correlationId) {
            $correlationId = (string) Str::uuid();
        }

        $paymentProvider = PaymentProvider::tryFrom($provider);
        if (!$paymentProvider) {
            return new JsonResponse(['error' => 'UNKNOWN_PROVIDER'], 400);
        }

        $payload = $request->all();
        $signature = (string) $request->header('X-Webhook-Signature', '');
        
        $connection = $this->redis->connection();
        $idempotencyKey = 'webhook:' . md5(json_encode($payload) . $signature);
        
        $isUnique = $connection->setnx($idempotencyKey, 1);
        if ($isUnique === 0 || $isUnique === false) {
            return new JsonResponse(['status' => 'OK', 'note' => 'DUPLICATE'], 200);
        }
        
        $connection->expire($idempotencyKey, 300);

        ProcessWebhookJob::dispatch(
            $paymentProvider,
            $payload,
            $signature,
            (string) $correlationId,
        )->onQueue('payments-high-priority');

        return new JsonResponse(['status' => 'OK'], 200);
    }
}
