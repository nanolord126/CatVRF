<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Listeners;

use Modules\CatCRM\Domain\Events\TaskCompleted;
use Modules\CatCRM\Infrastructure\Jobs\UpdateKPIOnTaskCompletionJob;

/**
 * UpdateKPIOnTaskCompletionListener — Listener для обновления KPI при завершении задачи
 */
final class UpdateKPIOnTaskCompletionListener
{
    public function handle(TaskCompleted $event): void
    {
        $task = $event->task;

        if (!$task->kpi_tracked || !$task->assigned_to_id) {
            return;
        }

        UpdateKPIOnTaskCompletionJob::dispatch($task->id);
    }
}
