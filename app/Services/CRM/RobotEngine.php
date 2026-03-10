<?php

namespace App\Services\CRM;

use App\Models\CRM\Robot;
use App\Models\CRM\Deal;
use App\Models\CRM\Task;
use App\Models\CRM\AuditLog;
use Illuminate\Support\Facades\Http;

class RobotEngine
{
    public function trigger(string $type, $model, array $context = [])
    {
        $robots = Robot::where('trigger_type', $type)
            ->where('is_active', true)
            ->where('tenant_id', $model->tenant_id)
            ->get();

        foreach ($robots as $robot) {
            $this->execute($robot, $model, $context);
        }
    }

    protected function execute(Robot $robot, $model, array $context)
    {
        match($robot->action_type) {
            'create_task' => $this->createTask($robot->action_config, $model),
            'send_webhook' => $this->sendWebhook($robot->action_config, $model),
            'change_stage' => $this->changeStage($robot->action_config, $model),
            'ai_chat' => $this->aiCommunication($robot->action_config, $model),
            default => null
        };
    }

    protected function createTask(array $config, $model)
    {
        Task::create([
            'title' => $config['title'] ?? 'Автоматическая задача',
            'responsible_id' => $config['responsible_id'] ?? $model->user_id,
            'tenant_id' => $model->tenant_id,
        ]);
    }
}
