<?php

declare(strict_types=1);

namespace App\Events\Auth;

use App\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class TenantVerified
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $correlationId = '',
    ) {}
}
