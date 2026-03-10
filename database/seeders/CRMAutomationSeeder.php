<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

class CRMAutomationSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\Tenant::all()->each(function ($tenant) {
            tenancy()->initialize($tenant);
            
            // Эталоны стадий воронки (Pipelines)
            $pipelines = [
                ['name' => 'Продажи B2B', 'is_default' => true],
                ['name' => 'Сервис и Поддержка', 'is_default' => false],
            ];

            foreach ($pipelines as $pipe) {
                $pipelineId = DB::table('crm_pipelines')->insertGetId([
                    'name' => $pipe['name'],
                    'is_default' => $pipe['is_default'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Стадии (Stages)
                $stages = [
                    ['name' => 'Первичный контакт', 'color' => '#3b82f6', 'sort' => 10],
                    ['name' => 'Переговоры', 'color' => '#fbbf24', 'sort' => 20],
                    ['name' => 'Принимают решение', 'color' => '#a855f7', 'sort' => 30],
                    ['name' => 'Согласование договора', 'color' => '#ec4899', 'sort' => 40],
                    ['name' => 'Успешно реализовано', 'color' => '#22c55e', 'sort' => 50],
                    ['name' => 'Закрыто и не реализовано', 'color' => '#ef4444', 'sort' => 60],
                ];

                foreach ($stages as $stage) {
                    $stageId = DB::table('crm_stages')->insertGetId(array_merge($stage, [
                        'crm_pipeline_id' => $pipelineId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));

                    // Роботы (Automation Robots)
                    if ($stage['name'] === 'Первичный контакт') {
                        DB::table('crm_robot_rules')->insert([
                            'crm_stage_id' => $stageId,
                            'name' => 'Авто-задача: Связаться с клиентом',
                            'action_type' => 'create_task',
                            'settings' => json_encode([
                                'title' => 'Позвонить новому лиду',
                                'deadline_offset_minutes' => 15,
                            ]),
                            'trigger_event' => 'entry',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            tenancy()->end();
        });
    }
}
