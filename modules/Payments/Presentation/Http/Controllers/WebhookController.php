<?php

declare(strict_types=1);

namespace Modules\Payments\Presentation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Payments\Application\UseCases\HandleWebhook\HandleWebhookCommand;
use Modules\Payments\Jobs\ProcessWebhookJob;

/**
 * Контроллер webhook'ов от платёжных шлюзов.
 * Только INTERNAL endpoint — не должен быть доступен публично без IP whitelist.
 */
final class WebhookController extends Controller
{
    /**
     * POST /api/internal/payments/webhook/tinkoff
     */
    public function tinkoff(Request $request): Response
    {
        $correlationId = (string) Str::uuid();
        $payload       = $request->all();

        Log::channel('audit')->info('webhook.tinkoff.received', [
            'correlation_id' => $correlationId,
            'ip'             => $request->ip(),
            'order_id'       => $payload['OrderId'] ?? null,
        ]);

        // Отправить в очередь для асинхронной обработки
        ProcessWebhookJob::dispatch(
            new HandleWebhookCommand(
                gatewayCode:   'tinkoff',
                payload:       $payload,
                correlationId: $correlationId,
            )
        )->onQueue('payments-webhooks');

        // Tinkoff ожидает HTTP 200 с "OK"
        return response('OK', 200);
    }
}
