<?php

declare(strict_types=1);

namespace Modules\Restaurant\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use Modules\Restaurant\Models\Order;

final class LoyaltyBonusEarned
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly float $amount,
        public readonly ?Order $order,
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
            'amount' => $this->amount,
            'new_balance' => $this->user->wallet_balance,
            'order_id' => $this->order?->id,
            'correlation_id' => $this->correlationId,
        ];
    }
}
