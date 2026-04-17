<?php declare(strict_types=1);

namespace App\Domains\Security\Events;

use App\Domains\Security\Models\ApiKey;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ApiKeyCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApiKey $apiKey,
        public readonly string $correlationId,
    ) {}
}
