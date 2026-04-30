<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\CatCRM\Domain\Entities\ManagerKPI;
use Modules\CatCRM\Application\Services\ManagerKPIService;
use Illuminate\Support\Facades\DB;

/**
 * RecalculateKPIScoresCommand — Artisan команда для пересчета KPI score
 * 
 * Production-ready команда для массового пересчета KPI score
 * для всех или конкретных менеджеров.
 */
final class RecalculateKPIScoresCommand extends Command
{
    protected $signature = 'crm:kpi:recalculate 
                            {--manager_id= : ID конкретного менеджера}
                            {--tenant_id= : ID конкретного tenant}
                            {--vertical_id= : ID вертикали}
                            {--period_type= : Тип периода для фильтрации}
                            {--status= : Статус для фильтрации}
                            {--force : Пересчитать даже завершенные периоды}';

    protected $description = 'Пересчитать KPI score для менеджеров';

    public function __construct(
        private readonly ManagerKPIService $kpiService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $managerId = $this->option('manager_id');
        $tenantId = $this->option('tenant_id');
        $verticalId = $this->option('vertical_id');
        $periodType = $this->option('period_type');
        $status = $this->option('status');
        $force = $this->option('force');

        $query = ManagerKPI::query();

        if ($managerId) {
            $query->where('manager_id', $managerId);
        }

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($verticalId) {
            $query->where('vertical_id', $verticalId);
        }

        if ($periodType) {
            $query->where('period_type', $periodType);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if (!$force) {
            $query->whereIn('status', ['pending', 'in_progress']);
        }

        $kpiRecords = $query->get();

        if ($kpiRecords->isEmpty()) {
            $this->warn('No KPI records found matching the criteria.');
            return 0;
        }

        $this->info("Found {$kpiRecords->count()} KPI records to recalculate.");
        $this->newLine();

        $recalculatedCount = 0;
        $failedCount = 0;

        DB::beginTransaction();

        try {
            foreach ($kpiRecords as $kpi) {
                try {
                    $previousScore = $kpi->score;
                    $kpi->calculateScore();

                    $this->line("  Manager {$kpi->manager_id}: {$previousScore} → {$kpi->score}");
                    $recalculatedCount++;
                } catch (\Exception $e) {
                    $this->error("  Failed to recalculate KPI {$kpi->id}: {$e->getMessage()}");
                    $failedCount++;
                }
            }

            DB::commit();

            $this->newLine();
            $this->info("Summary:");
            $this->line("  Recalculated: {$recalculatedCount}");
            $this->line("  Failed: {$failedCount}");
            $this->newLine();
            $this->info('KPI scores recalculated successfully!');

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to recalculate KPI scores: {$e->getMessage()}");
            return 1;
        }
    }
}
