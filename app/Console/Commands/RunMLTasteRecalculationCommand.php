<?php declare(strict_types=1);

namespace App\Console\Commands;


use Psr\Log\LoggerInterface;
use App\Domains\Common\Jobs\MLRecalculateUserTastesJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class RunMLTasteRecalculationCommand
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Console\Commands
 */
final class RunMLTasteRecalculationCommand extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

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

            $this->logger->info('ML taste recalculation dispatched', [
                'force' => $force,
                'batch' => $batchSize,
                'correlation_id' => $correlationId,
            ]);

            $this->info('✓ Job отправлена в очередь');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->logger->error('ML taste recalculation dispatch failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            $this->error('✗ Ошибка: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
