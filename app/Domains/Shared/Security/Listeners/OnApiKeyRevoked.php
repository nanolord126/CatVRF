<?php

declare(strict_types=1);

namespace App\Domains\Security\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Security\Events\ApiKeyRevoked;
use Illuminate\Log\LogManager;

final class OnApiKeyRevoked
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    public function handle(ApiKeyRevoked $event): void
    {
        $this->log->channel('security')->$this->logger->info('API key revoked', [
            'key_id' => $event->apiKey->key_id,
            'tenant_id' => $event->apiKey->tenant_id,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
