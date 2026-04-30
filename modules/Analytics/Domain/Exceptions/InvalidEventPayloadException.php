<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Exceptions;

final class InvalidEventPayloadException extends AnalyticsException
{
    public function __construct(string $reason)
    {
        parent::__construct("Invalid event payload: {$reason}");
    }
}
