<?php declare(strict_types=1);

namespace App\Domains\Security\Listeners;

use App\Domains\Security\Events\ApiKeyRevoked;
use Illuminate\Support\Facades\Log;

final class OnApiKeyRevoked
{
    public function handle(ApiKeyRevoked $event): void
    {
        Log::channel('security')->info('API key revoked', [
            'key_id' => $event->apiKey->key_id,
            'tenant_id' => $event->apiKey->tenant_id,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
