<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\CatCRM\Domain\Entities\ManagerKPI;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Tenant;

/**
 * CreateManagerKPIPeriodsCommand — Artisan команда для создания KPI периодов
 * 
 * Production-ready команда для автоматического создания KPI периодов
 * для менеджеров. Поддерживает разные типы периодов и вертикали.
 */
final class CreateManagerKPIPeriodsCommand extends Command
{
    protected $signature = 'crm:kpi:create-periods 
                            {--manager_id= : ID конкретного менеджера}
                            {--tenant_id= : ID конкретного tenant}
                            {--vertical_id= : ID вертикали (для всех вертикалей оставить пустым)}
                            {--period_type=monthly : Тип периода (daily, weekly, monthly, quarterly, yearly)}
                            {--months=3 : Количество периодов для создания}
                            {--business_type=both : Тип бизнеса (b2b, b2c, both)}
                            {--force : Пересоздать существующие периоды}';

    protected $description = 'Создать KPI периоды для менеджеров';

    public function handle(): int
    {
        $managerId = $this->option('manager_id');
        $tenantId = $this->option('tenant_id');
        $verticalId = $this->option('vertical_id');
        $periodType = $this->option('period_type');
        $months = (int) $this->option('months');
        $businessType = $this->option('business_type');
        $force = $this->option('force');

        // Валидация
        if (!in_array($periodType, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])) {
            $this->error('Invalid period_type. Must be one of: daily, weekly, monthly, quarterly, yearly');
            return 1;
        }

        if (!in_array($businessType, ['b2b', 'b2c', 'both'])) {
            $this->error('Invalid business_type. Must be one of: b2b, b2c, both');
            return 1;
        }

        // Получаем менеджеров
        $managersQuery = User::query();

        if ($managerId) {
            $managersQuery->where('id', $managerId);
        } else {
            // Получаем всех менеджеров и администраторов
            $managersQuery->whereHas('roles', function ($query) {
                $query->whereIn('name', ['manager', 'admin']);
            });
        }

        $managers = $managersQuery->get();

        if ($managers->isEmpty()) {
            $this->warn('No managers found.');
            return 0;
        }

        // Получаем tenants
        $tenantsQuery = Tenant::query();

        if ($tenantId) {
            $tenantsQuery->where('id', $tenantId);
        }

        $tenants = $tenantsQuery->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return 0;
        }

        $createdCount = 0;
        $skippedCount = 0;

        $this->info("Creating KPI periods for {$managers->count()} managers in {$tenants->count()} tenants...");
        $this->newLine();

        foreach ($tenants as $tenant) {
            $this->info("Processing tenant: {$tenant->name} (ID: {$tenant->id})");

            foreach ($managers as $manager) {
                $this->info("  Creating periods for manager: {$manager->name} (ID: {$manager->id})");

                for ($i = 0; $i < $months; $i++) {
                    [$periodStart, $periodEnd] = $this->calculatePeriodDates($periodType, $i);

                    // Проверяем существование
                    $existingKPI = ManagerKPI::where('manager_id', $manager->id)
                        ->where('tenant_id', $tenant->id)
                        ->where('period_start', $periodStart)
                        ->first();

                    if ($existingKPI && !$force) {
                        $skippedCount++;
                        $this->line("    - Skipped existing period: {$periodStart} to {$periodEnd}");
                        continue;
                    }

                    if ($existingKPI && $force) {
                        $existingKPI->delete();
                        $this->line("    - Deleted existing period: {$periodStart} to {$periodEnd}");
                    }

                    ManagerKPI::create([
                        'tenant_id' => $tenant->id,
                        'business_group_id' => null,
                        'manager_id' => $manager->id,
                        'vertical_id' => $verticalId ?: null,
                        'period_type' => $periodType,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'targets' => $this->getDefaultTargets(),
                        'actuals' => [],
                        'score' => 0,
                        'status' => $i === 0 ? 'in_progress' : 'pending',
                        'business_type' => $businessType,
                        'tasks_assigned' => 0,
                        'tasks_completed' => 0,
                        'tasks_on_time' => 0,
                        'tasks_overdue' => 0,
                        'metadata' => [
                            'created_by' => 'artisan_command',
                            'auto_generated' => true,
                        ],
                        'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
                    ]);

                    $createdCount++;
                    $this->line("    + Created period: {$periodStart} to {$periodEnd}");
                }
            }

            $this->newLine();
        }

        $this->info("Summary:");
        $this->line("  Created: {$createdCount} periods");
        $this->line("  Skipped: {$skippedCount} periods");
        $this->newLine();
        $this->info('KPI periods created successfully!');

        return 0;
    }

    /**
     * Рассчитать даты периода
     */
    private function calculatePeriodDates(string $periodType, int $offset): array
    {
        $now = Carbon::now();

        return match ($periodType) {
            'daily' => [
                $now->subDays($offset)->startOfDay(),
                $now->subDays($offset)->endOfDay(),
            ],
            'weekly' => [
                $now->subWeeks($offset)->startOfWeek(),
                $now->subWeeks($offset)->endOfWeek(),
            ],
            'monthly' => [
                $now->subMonths($offset)->startOfMonth(),
                $now->subMonths($offset)->endOfMonth(),
            ],
            'quarterly' => [
                $now->subQuarters($offset)->startOfQuarter(),
                $now->subQuarters($offset)->endOfQuarter(),
            ],
            'yearly' => [
                $now->subYears($offset)->startOfYear(),
                $now->subYears($offset)->endOfYear(),
            ],
        };
    }

    /**
     * Получить дефолтные цели KPI
     */
    private function getDefaultTargets(): array
    {
        return [
            'tasks_completed' => [
                'value' => 50,
                'weight' => 1.0,
                'description' => 'Количество выполненных задач',
            ],
            'tasks_on_time' => [
                'value' => 45,
                'weight' => 1.2,
                'description' => 'Количество задач выполненных вовремя',
            ],
            'customer_satisfaction' => [
                'value' => 4.5,
                'weight' => 0.8,
                'description' => 'Средняя оценка удовлетворенности клиентов',
            ],
            'response_time' => [
                'value' => 2.0,
                'weight' => 0.5,
                'description' => 'Среднее время ответа в часах',
            ],
        ];
    }
}
