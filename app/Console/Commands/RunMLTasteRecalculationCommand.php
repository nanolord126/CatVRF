<?php

declare(strict_types=1);

/**
 * CANON 2026: ML Taste Analysis - Schedule Configuration
 * Добавить эту строку в app/Console/Kernel.php в методе schedule()
 * 
 * МЕСТО ДЛЯ ДОБАВЛЕНИЯ:
 * 
 * protected function schedule(Schedule $schedule): void
 * {
 *     // ... другие jobs ...
 *     
 *     // CANON 2026: ML Taste Analysis
 *     $schedule->job(new \App\Domains\Common\Jobs\MLRecalculateUserTastesJob())
 *         ->dailyAt('04:30')
 *         ->timezone('UTC')
 *         ->onConnection('default')
 *         ->onQueue('default')
 *         ->withoutOverlapping(timeout: 600)
 *         ->onFailure(function (Throwable $exception) {
 *             \Illuminate\Support\Facades\Log::channel('audit')->critical(
 *                 'MLRecalculateUserTastesJob failed in scheduler',
 *                 ['exception' => $exception->getMessage()]
 *             );
 *         });
 *     
 *     // ... другие jobs ...
 * }
 * 
 * 
 * АЛЬТЕРНАТИВНО: Использовать команду в cron:
 * 
 * 30 4 * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
 */

namespace App\Console\Commands;

use App\Domains\Common\Jobs\MLRecalculateUserTastesJob;
use Illuminate\Console\Command;

final class RunMLTasteRecalculationCommand extends Command
{
    protected $signature = 'taste-ml:recalculate
                          {--force : Пересчитать всех пользователей, даже если недавно обновляли}
                          {--batch=1000 : Размер батча}
                          {--verbose : Выводить подробные логи}';

    protected $description = 'Ручной запуск пересчёта ML-профилей вкусов (обычно запускается ежедневно в scheduler)';

    public function handle(): int
    {
        $this->info('Запуск ML пересчёта профилей вкусов...');

        try {
            MLRecalculateUserTastesJob::dispatch();

            if ($this->option('verbose')) {
                $this->info('✓ Job отправлена в очередь');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('✗ Ошибка: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
