<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Listeners;

use Modules\CatCRM\Domain\Events\TaskAssigned;
use Modules\CatCRM\Infrastructure\Jobs\UpdateKPIOnTaskAssignmentJob;

/**
 * UpdateKPIOnTaskAssignmentListener — Listener для обновления KPI при назначении задачи
 */
final class UpdateKPIOnTaskAssignmentListener
{
    public function handle(TaskAssigned $event): void
    {
        $task = $event->task;

        if (!$task->kpi_tracked || !$task->assigned_to_id) {
            return;
        }

        UpdateKPIOnTaskAssignmentJob::dispatch($task->id);
    }
}
