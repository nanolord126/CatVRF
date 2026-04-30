<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Corporate\Enums;

enum CorporateEnrollmentStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
