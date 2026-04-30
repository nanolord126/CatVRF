<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\CatCRM\Application\Services\ManagerKPIService;
use Modules\CatCRM\Domain\Entities\Task;

/**
 * UpdateKPIOnTaskCompletionJob — Асинхронное обновление KPI при завершении задачи
 */
final class UpdateKPIOnTaskCompletionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly int $taskId
    ) {}

    public function handle(ManagerKPIService $kpiService): void
    {
        $task = Task::find($this->taskId);

        if (!$task) {
            return;
        }

        $kpiService->updateKPIOnTaskCompletion($task);
    }
}
