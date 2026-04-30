<?php

declare(strict_types=1);

namespace App\Shared\Domain\Events;

interface IEventPublisher
{
    public function publish(array $events): void;

    public function publishAsync(array $events): void;
}
