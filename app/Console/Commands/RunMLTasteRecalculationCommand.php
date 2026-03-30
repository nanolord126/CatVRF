<?php declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Common\Jobs\MLRecalculateUserTastesJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class RunMLTasteRecalculationCommand extends Command
{
    protected $signature = 'taste-ml:recalculate
        {--force : Пересчитать всех пользователей, даже если недавно обновляли}
        {--batch=1000 : Размер батча пересчёта}
        {--correlation-id= : Correlation identifier for audit logs}';

    protected $description = 'Ручной запуск пересчёта ML-профилей вкусов (обычно выполняется по расписанию)';

    public function handle(): int
    {
        $correlationId = $this->option('correlation-id') ?: (string) Str::uuid();
        $force = (bool) $this->option('force');
        $batchSize = (int) $this->option('batch');

        $this->info('Запуск ML пересчёта профилей вкусов...');

        try {
            MLRecalculateUserTastesJob::dispatch();

            Log::channel('audit')->info('ML taste recalculation dispatched', [
                'force' => $force,
                'batch' => $batchSize,
                'correlation_id' => $correlationId,
            ]);

            $this->info('✓ Job отправлена в очередь');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('ML taste recalculation dispatch failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            $this->error('✗ Ошибка: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
