<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Exceptions;

final class BehavioralEventNotFoundException extends AnalyticsException
{
    public function __construct(string $eventId)
    {
        parent::__construct("Behavioral event with ID '{$eventId}' not found");
    }
}
