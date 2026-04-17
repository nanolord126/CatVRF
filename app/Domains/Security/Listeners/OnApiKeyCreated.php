<?php declare(strict_types=1);

namespace App\Domains\Security\Listeners;

use App\Domains\Security\Events\ApiKeyCreated;
use Illuminate\Support\Facades\Log;

final class OnApiKeyCreated
{
    public function handle(ApiKeyCreated $event): void
    {
        Log::channel('security')->info('API key created', [
            'key_id' => $event->apiKey->key_id,
            'tenant_id' => $event->apiKey->tenant_id,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
