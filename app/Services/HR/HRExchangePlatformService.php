<?php

namespace App\Services\HR;

use App\Models\HR\HRExchangeTask;
use App\Models\HR\HRExchangeResponse;
use App\Models\User;
use App\Services\Common\Security\AIAnomalyDetector;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Stancl\Tenancy\Facades\Tenancy;

class HRExchangePlatformService
{
    protected AIAnomalyDetector $fraudDetector;

    public function __construct(AIAnomalyDetector $fraudDetector)
    {
        $this->fraudDetector = $fraudDetector;
    }

    /**
     * Создать запрос на бирже (Аренда персонала)
     */
    public function createTask(array $data)
    {
        // Проверка фрода при публикации вакансии/смены (Bulk Creation Protection)
        $tenant = Tenancy::tenant();
        $riskScore = $this->fraudDetector->analyze($tenant, auth()->id(), 'hr_exchange_task_create', [
            'reward_amount' => $data['reward_amount'],
            'title' => $data['title'],
        ]);

        if ($riskScore >= 75) {
            throw new \Exception("HR Task publishing blocked by AI Fraud Control (Risk Score: $riskScore).");
        }

        return HRExchangeTask::create([
            'tenant_id' => tenant('id'),
            'title' => $data['title'],
            'description' => $data['description'],
            'category' => $data['category'],
            'reward_amount' => $data['reward_amount'],
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'],
            'slots_available' => $data['slots_available'] ?? 1,
            'status' => 'OPEN'
        ]);
    }

    /**
     * Подать заявку на работу (Аренда/Обмен)
     */
    public function respondToTask(HRExchangeTask $task, User $employee)
    {
        // 1. Проверяем, достаточно ли слотов
        if ($task->slots_available <= 0 || $task->status !== 'OPEN') {
            throw new \Exception("На эту смену больше нет свободных мест.");
        }

        // 2. Создаем отклик
        return DB::transaction(function () use ($task, $employee) {
            $response = HRExchangeResponse::create([
                'hr_exchange_task_id' => $task->id,
                'employee_id' => $employee->id,
                'current_tenant_id' => tenant('id'),
                'status' => 'PENDING'
            ]);

            // 3. Уведомляем автора заказа (другой тенант)
            // Примечание: через центральную шину уведомлений 2026 года
            
            return $response;
        });
    }

    /**
     * Подтверждение/Финализация и мгновенная выплата зарплаты через Wallet
     */
    public function completeAndPay(HRExchangeResponse $response)
    {
        $task = $response->task;
        $employee = $response->employee;

        return DB::transaction(function () use ($response, $task, $employee) {
            // 1. Смена статусов
            $response->update(['status' => 'FINISHED']);
            
            // 2. Выплата через Wallet (B2B расчет между тенантами)
            // Деньги тенанта (заказчика) ➔ Кошелек сотрудника другого тенанта
            $employee->deposit($task->reward_amount, [
                'type' => 'HR_EXCHANGE_PAYMENT',
                'task_id' => $task->id,
                'description' => "Оплата смены на HR Бирже: {$task->title}"
            ]);

            Notification::make()
                ->title('HR Биржа: Смена оплачена')
                ->body("Сумма {$task->reward_amount} переведена на кошелек сотрудника.")
                ->actions([
                    \Filament\Notifications\Actions\Action::make('review')
                        ->label('Оценить Исполнителя')
                        ->icon('heroicon-o-star')
                        ->button()
                        ->url(fn () => route('filament.tenant.resources.h-r.h-r-exchange-tasks.edit', ['record' => $task->id])),
                ])
                ->success()
                ->send();
            
            return true;
        });
    }
}
