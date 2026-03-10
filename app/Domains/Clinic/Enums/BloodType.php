<?php

namespace App\Domains\Clinic\Enums;

enum BloodType: string
{
    case O_NEGATIVE = 'O-';
    case O_POSITIVE = 'O+';
    case A_NEGATIVE = 'A-';
    case A_POSITIVE = 'A+';
    case B_NEGATIVE = 'B-';
    case B_POSITIVE = 'B+';
    case AB_NEGATIVE = 'AB-';
    case AB_POSITIVE = 'AB+';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return $this->value;
    }

    public function isUniversalDonor(): bool
    {
        return $this === self::O_NEGATIVE;
    }

    public function isUniversalRecipient(): bool
    {
        return $this === self::AB_POSITIVE;
    }
}
