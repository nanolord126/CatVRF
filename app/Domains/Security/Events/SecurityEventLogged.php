<?php declare(strict_types=1);

namespace App\Domains\Security\Events;

use App\Domains\Security\Models\SecurityEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SecurityEventLogged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SecurityEvent $securityEvent,
    ) {}
}
