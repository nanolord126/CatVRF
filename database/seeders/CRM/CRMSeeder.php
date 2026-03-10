<?php

namespace Database\Seeders\CRM;

use Illuminate\Database\Seeder;
use App\Models\CRM\Pipeline;
use App\Models\CRM\Stage;
use App\Models\CRM\Deal;
use App\Models\CRM\Task;
use App\Models\CRM\Project;
use App\Models\CRM\Robot;

class CRMSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 'default_tenant';

        $pipeline = Pipeline::create([
            'name' => 'Продажи B2B',
            'is_default' => true,
            'tenant_id' => $tenantId,
        ]);

        $stages = [
            ['name' => 'Первичный контакт', 'color' => '#3498db', 'sort_order' => 1],
            ['name' => 'Квалификация', 'color' => '#9b59b6', 'sort_order' => 2],
            ['name' => 'Предложение', 'color' => '#f1c40f', 'sort_order' => 3],
            ['name' => 'Переговоры', 'color' => '#e67e22', 'sort_order' => 4],
            ['name' => 'Договор', 'color' => '#2ecc71', 'sort_order' => 5, 'is_win' => true],
            ['name' => 'Отказ', 'color' => '#e74c3c', 'sort_order' => 6, 'is_loss' => true],
        ];

        foreach ($stages as $stageData) {
            $pipeline->stages()->create($stageData);
        }

        Deal::create([
            'name' => 'Крупная поставка оборудования',
            'pipeline_id' => $pipeline->id,
            'stage_id' => $pipeline->stages->where('sort_order', 1)->first()->id,
            'amount' => 1500000,
            'tenant_id' => $tenantId,
        ]);

        Project::create([
            'name' => 'Внедрение CatVRF ERP',
            'status' => 'active',
            'tenant_id' => $tenantId,
        ]);

        Robot::create([
            'name' => 'Авто-задача на новом Лиде',
            'trigger_type' => 'deal_created',
            'trigger_config' => ['any' => true],
            'action_type' => 'create_task',
            'action_config' => ['title' => 'Связаться с клиентом в течение часа'],
            'tenant_id' => $tenantId,
        ]);
    }
}
