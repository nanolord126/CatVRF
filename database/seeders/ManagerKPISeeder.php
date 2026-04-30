<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\CatCRM\Domain\Entities\ManagerKPI;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Tenant;

/**
 * ManagerKPISeeder — Seeder для создания KPI периодов менеджеров
 * 
 * Production-ready seeder с автоматическим созданием KPI периодов
 * для всех менеджеров с дефолтными целями.
 */
final class ManagerKPISeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Получаем всех менеджеров (пользователей с ролью manager)
        $managers = User::whereHas('roles', function ($query) {
            $query->where('name', 'manager');
        })->orWhereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        if ($managers->isEmpty()) {
            $this->command->warn('No managers found. Skipping KPI seeding.');
            return;
        }

        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Skipping KPI seeding.');
            return;
        }

        $createdCount = 0;

        foreach ($tenants as $tenant) {
            foreach ($managers as $manager) {
                // Создаем KPI периоды на последние 3 месяца
                for ($i = 0; $i < 3; $i++) {
                    $periodStart = Carbon::now()->subMonths($i)->startOfMonth();
                    $periodEnd = Carbon::now()->subMonths($i)->endOfMonth();

                    // Проверяем, существует ли уже KPI период
                    $existingKPI = ManagerKPI::where('manager_id', $manager->id)
                        ->where('tenant_id', $tenant->id)
                        ->where('period_start', $periodStart)
                        ->first();

                    if ($existingKPI) {
                        continue;
                    }

                    $targets = $this->getDefaultTargets();

                    ManagerKPI::create([
                        'tenant_id' => $tenant->id,
                        'business_group_id' => null,
                        'manager_id' => $manager->id,
                        'vertical_id' => null,
                        'period_type' => 'monthly',
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'targets' => $targets,
                        'actuals' => [],
                        'score' => 0,
                        'status' => $i === 0 ? 'in_progress' : 'completed',
                        'business_type' => 'both',
                        'tasks_assigned' => 0,
                        'tasks_completed' => 0,
                        'tasks_on_time' => 0,
                        'tasks_overdue' => 0,
                        'metadata' => [
                            'created_by' => 'seeder',
                            'auto_generated' => true,
                        ],
                        'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
                    ]);

                    $createdCount++;
                }

                // Создаем текущий активный период
                $currentPeriodStart = Carbon::now()->startOfMonth();
                $currentPeriodEnd = Carbon::now()->endOfMonth();

                $existingCurrentKPI = ManagerKPI::where('manager_id', $manager->id)
                    ->where('tenant_id', $tenant->id)
                    ->where('period_start', $currentPeriodStart)
                    ->first();

                if (!$existingCurrentKPI) {
                    ManagerKPI::create([
                        'tenant_id' => $tenant->id,
                        'business_group_id' => null,
                        'manager_id' => $manager->id,
                        'vertical_id' => null,
                        'period_type' => 'monthly',
                        'period_start' => $currentPeriodStart,
                        'period_end' => $currentPeriodEnd,
                        'targets' => $this->getDefaultTargets(),
                        'actuals' => [],
                        'score' => 0,
                        'status' => 'in_progress',
                        'business_type' => 'both',
                        'tasks_assigned' => 0,
                        'tasks_completed' => 0,
                        'tasks_on_time' => 0,
                        'tasks_overdue' => 0,
                        'metadata' => [
                            'created_by' => 'seeder',
                            'auto_generated' => true,
                        ],
                        'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
                    ]);

                    $createdCount++;
                }
            }
        }

        $this->command->info("Created {$createdCount} KPI periods for {$managers->count()} managers in {$tenants->count()} tenants.");
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
