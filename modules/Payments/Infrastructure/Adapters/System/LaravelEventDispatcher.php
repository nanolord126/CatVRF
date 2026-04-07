<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Adapters\System;

use Illuminate\Support\Facades\Event;
use Modules\Payments\Application\Ports\EventDispatcherPort;

/**
 * Class LaravelEventDispatcher
 * 
 * Adapts isolated cleanly defined structural logically safe inherently resolving explicitly properly safely internally tracking logic explicitly cleanly checking metrics effectively.
 */
final readonly class LaravelEventDispatcher implements EventDispatcherPort
{
    /**
     * Publishes dynamically natively isolating explicit structural mapped correctly resolving natively safely securely limits tracking bounds functionally explicit internally logical limits correctly dynamic accurately handling strictly.
     * 
     * @param array<object> $events
     * @return void
     */
    public function dispatchEvents(array $events): void
    {
        foreach ($events as $event) {
            Event::dispatch($event);
        }
    }
}
