<?php

declare(strict_types=1);

namespace Modules\Video\Domain\ValueObjects;

enum RoomType: string
{
    case CONSULTATION = 'consultation';
    case GROOMING_DEMO = 'grooming_demo';
    case MASTERCLASS = 'masterclass';
    case SURGERY = 'surgery';
    case GROUP_CALL = 'group_call';

    public function isOneToOne(): bool
    {
        return $this === self::CONSULTATION;
    }

    public function isBroadcast(): bool
    {
        return in_array($this, [self::GROOMING_DEMO, self::MASTERCLASS, self::SURGERY], true);
    }

    public function isGroupCall(): bool
    {
        return $this === self::GROUP_CALL;
    }

    public function requiresRecordingConsent(): bool
    {
        return in_array($this, [self::CONSULTATION, self::SURGERY], true);
    }

    public function getMaxParticipants(): int
    {
        return match ($this) {
            self::CONSULTATION => 2,
            self::GROOMING_DEMO => 100,
            self::MASTERCLASS => 500,
            self::SURGERY => 10,
            self::GROUP_CALL => 10,
        };
    }
}
