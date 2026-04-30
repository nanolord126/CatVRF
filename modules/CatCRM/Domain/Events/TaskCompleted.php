<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Events;

use Modules\CatCRM\Domain\Entities\Task;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * TaskCompleted — Событие завершения задачи
 */
final class TaskCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly Task $task
    ) {}
}
