<?php

declare(strict_types=1);

namespace Modules\Payments\Application\UseCases\HandleWebhook;

/**
 * Command: обработать входящий webhook от платёжного шлюза.
 */
final readonly class HandleWebhookCommand
{
    public function __construct(
        public string $gatewayCode,
        public array  $payload,
        public string $correlationId,
    ) {}
}
