<?php

declare(strict_types=1);

namespace App\Domains\Common\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * CANON 2026: User Taste ML Analysis - User Interaction Event
 * Событие для сбора данных о взаимодействиях пользователя с товарами/услугами
 * Используется для обновления профиля вкусов
 */
final class UserInteractionEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $correlationId;

    public function __construct(
        public int $userId,
        public int $tenantId,
        public string $interactionType,
        // 'view', 'cart_add', 'cart_remove', 'purchase', 'review', 'rating', 'like', 'wishlist_add'
        public array $data = [],
        // 'product_id', 'vertical', 'category', 'price', 'rating', 'duration_seconds' и т.д.
        public string $ipAddress = '',
        public string $userAgent = '',
    ) {
        $this->correlationId = Str::uuid()->toString();
    }
}
