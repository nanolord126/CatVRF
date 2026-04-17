<?php declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use App\Domains\Beauty\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class VideoCallInitiatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public string $webrtcRoomId,
        public string $correlationId,
    ) {}

    public function getAppointmentId(): int
    {
        return $this->appointment->id;
    }

    public function getUserId(): int
    {
        return $this->appointment->user_id;
    }

    public function getMasterId(): int
    {
        return $this->appointment->master_id;
    }

    public function getWebrtcRoomId(): string
    {
        return $this->webrtcRoomId;
    }

    public function getExpiresAt(): ?string
    {
        return $this->appointment->metadata['video_call_expires_at'] ?? null;
    }
}
