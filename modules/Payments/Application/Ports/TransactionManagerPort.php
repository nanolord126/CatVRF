<?php

declare(strict_types=1);

namespace Modules\Payments\Application\Ports;

/**
 * Port: Управление транзакциями БД (outgoing).
 * Изолирует UseCases от DB::transaction().
 */
interface TransactionManagerPort
{
    /**
     * Выполнить callable в транзакции БД.
     * Пробрасывает любое исключение, откатывая транзакцию.
     *
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(callable $callback): mixed;
}
