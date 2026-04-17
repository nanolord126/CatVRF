<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Domains\RealEstate\Models\PropertyViewing;

final class ViewingConfirmedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly PropertyViewing $viewing,
        public readonly string $correlationId
    ) {}

    public function getPropertyId(): int
    {
        return $this->viewing->property_id;
    }

    public function getUserId(): int
    {
        return $this->viewing->user_id;
    }

    public function getAgentId(): ?int
    {
        return $this->viewing->agent_id;
    }

    public function getScheduledAt(): string
    {
        return $this->viewing->scheduled_at->toIso8601String();
    }

    public function getWebRTCRoomId(): ?string
    {
        return $this->viewing->webrtc_room_id;
    }
}
