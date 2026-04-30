<?php

declare(strict_types=1);

namespace Modules\Video\Domain\Exceptions;

use Exception;

final class VideoRoomException extends Exception
{
    public static function roomNotFound(string $roomId): self
    {
        return new self("Video room not found: {$roomId}");
    }

    public static function roomNotActive(string $roomId): self
    {
        return new self("Room is not active: {$roomId}");
    }

    public static function participantNotFound(string $participantId): self
    {
        return new self("Participant not found: {$participantId}");
    }

    public static function maxParticipantsExceeded(int $max, int $current): self
    {
        return new self("Maximum participants exceeded: {$current}/{$max}");
    }

    public static function recordingConsentRequired(): self
    {
        return new self("Recording consent is required for this room type");
    }

    public static function tenantIsolationViolation(): self
    {
        return new self("Tenant isolation violation detected");
    }

    public static function livekitConnectionFailed(string $reason): self
    {
        return new self("LiveKit connection failed: {$reason}");
    }

    public static function invalidParticipantRole(string $role): self
    {
        return new self("Invalid participant role: {$role}");
    }
}
