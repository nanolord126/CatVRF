<?php

declare(strict_types=1);

namespace Modules\Payments\Application\Ports;

/**
 * Port: Диспетчеризация доменных событий (outgoing).
 * Изолирует UseCases от event() / EventDispatcher.
 */
interface EventDispatcherPort
{
    /**
     * Диспатчить список доменных событий.
     *
     * @param  list<object>  $events
     */
    public function dispatchAll(array $events): void;

    /** Диспатчить одно событие */
    public function dispatch(object $event): void;
}
