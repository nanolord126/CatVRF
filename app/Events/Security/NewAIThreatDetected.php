<?php

declare(strict_types=1);

namespace App\Events\Security;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * New AI Threat Detected Event
 * 
 * Dispatched when new critical AI threats are detected from BDU FSTEC.
 * Triggers notifications to super-admins and automatic risk score updates.
 */
final class NewAIThreatDetected
{
    use Dispatchable;

    public function __construct(
        public readonly Collection $threats,
        public readonly array $actions,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [];
    }
}
