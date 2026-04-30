<?php

declare(strict_types=1);

namespace App\Domains\Security\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Security\Events\ApiKeyCreated;
use Illuminate\Log\LogManager;

final class OnApiKeyCreated
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    public function handle(ApiKeyCreated $event): void
    {
        $this->log->channel('security')->$this->logger->info('API key created', [
            'key_id' => $event->apiKey->key_id,
            'tenant_id' => $event->apiKey->tenant_id,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
