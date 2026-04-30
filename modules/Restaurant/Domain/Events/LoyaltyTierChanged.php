<?php

declare(strict_types=1);

namespace Modules\Restaurant\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

final class LoyaltyTierChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly ?string $oldTier,
        public readonly ?string $newTier,
        public readonly string $correlationId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'old_tier' => $this->oldTier,
            'new_tier' => $this->newTier,
            'correlation_id' => $this->correlationId,
        ];
    }
}
