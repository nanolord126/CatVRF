<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services;

use Modules\CatCRM\Domain\Entities\Deal;
use Modules\CatCRM\Domain\Entities\Customer;
use Modules\CatCRM\Domain\Entities\Stage;
use Modules\CatCRM\Domain\Entities\Task;
use Modules\CatCRM\Domain\Enums\DealStatus;
use Modules\CatCRM\Domain\Enums\TaskPriority;
use Modules\CatCRM\Domain\Enums\TaskType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

/**
 * Deal Service — Сервис для управления сделками в CRM
 */
final class DealService
{
    use WithAuditLogging;

    public function __construct(
        private readonly string $correlationId,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Создать новую сделку
     */
    public function createDeal(array $data): Deal
    {
        return DB::transaction(function () use ($data) {
            $deal = Deal::create([
                'tenant_id' => $data['tenant_id'],
                'business_group_id' => $data['business_group_id'] ?? null,
                'pipeline_id' => $data['pipeline_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'value' => $data['value'] ?? 0,
                'currency' => $data['currency'] ?? 'RUB',
                'source' => $data['source'] ?? null,
                'priority' => $data['priority'] ?? 3,
                'expected_close_date' => $data['expected_close_date'] ?? null,
                'assigned_to_id' => $data['assigned_to_id'] ?? null,
                'contact_person' => $data['contact_person'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'marketplace_order_id' => $data['marketplace_order_id'] ?? null,
                'correlation_id' => $this->correlationId,
            ]);

            // Создаем задачу для follow-up если нужно
            if ($data['create_follow_up_task'] ?? false) {
                $this->createFollowUpTask($deal, $data['assigned_to_id'] ?? null);
            }

            return $deal;
        });
    }

    /**
     * Переместить сделку на другой этап
     */
    public function moveDealToStage(Deal $deal, Stage $stage, ?string $reason = null): Deal
    {
        return DB::transaction(function () use ($deal, $stage, $reason) {
            $deal->moveToStage($stage, $reason);
            
            // Создаем автоматическую задачу если нужно
            if ($stage->order > 1 && !$stage->isFinal()) {
                $this->createTaskForStage($deal, $stage);
            }
            
            return $deal->fresh();
        });
    }

    /**
     * Выиграть сделку
     */
    public function winDeal(Deal $deal, ?string $reason = null): Deal
    {
        return DB::transaction(function () use ($deal, $reason) {
            $deal->win($reason);
            
            // Обновляем статистику клиента
            if ($deal->customer_id) {
                Customer::find($deal->customer_id)?->updateStatistics();
            }
            
            return $deal->fresh();
        });
    }

    /**
     * Проиграть сделку
     */
    public function loseDeal(Deal $deal, string $reason): Deal
    {
        return DB::transaction(function () use ($deal, $reason) {
            $deal->lose($reason);
            return $deal->fresh();
        });
    }

    /**
     * Создать сделку из заказа маркетплейса
     */
    public function createDealFromMarketplaceOrder(array $orderData, int $tenantId): Deal
    {
        return DB::transaction(function () use ($orderData, $tenantId) {
            // Находим или создаем клиента
            $customer = $this->findOrCreateCustomer($orderData, $tenantId);
            
            // Получаем или создаем воронку для вертикали
            $pipeline = $this->getPipelineForVertical($orderData['vertical'] ?? 'default', $tenantId);
            
            // Создаем сделку
            $deal = $this->createDeal([
                'tenant_id' => $tenantId,
                'pipeline_id' => $pipeline->id,
                'customer_id' => $customer->id,
                'title' => "Заказ #{$orderData['order_id']}",
                'description' => $orderData['items'] ?? null,
                'value' => $orderData['total'] ?? 0,
                'source' => 'marketplace',
                'marketplace_order_id' => $orderData['order_id'],
                'contact_person' => $customer->getFullName(),
                'contact_phone' => $customer->phone,
                'contact_email' => $customer->email,
            ]);
            
            return $deal;
        });
    }

    /**
     * Получить статистику по воронке
     */
    public function getPipelineStatistics(int $pipelineId): array
    {
        $pipeline = \Modules\CatCRM\Domain\Entities\Pipeline::with(['stages', 'deals'])->find($pipelineId);
        
        if (!$pipeline) {
            return [];
        }
        
        return $pipeline->getStatistics();
    }

    /**
     * Получить просроченные сделки
     */
    public function getOverdueDeals(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return Deal::byTenant($tenantId)
            ->overdue()
            ->with(['customer', 'stage', 'assignedTo'])
            ->get();
    }

    // ========================
    // PRIVATE METHODS
    // ========================

    private function createFollowUpTask(Deal $deal, ?int $assignedToId): Task
    {
        return Task::create([
            'tenant_id' => $deal->tenant_id,
            'business_group_id' => $deal->business_group_id,
            'deal_id' => $deal->id,
            'customer_id' => $deal->customer_id,
            'assigned_to_id' => $assignedToId,
            'title' => 'Follow-up по сделке: ' . $deal->title,
            'type' => TaskType::FollowUp,
            'priority' => TaskPriority::Medium,
            'status' => \Modules\CatCRM\Domain\Enums\TaskStatus::Pending,
            'due_date' => now()->addDays(2),
            'correlation_id' => $this->correlationId,
        ]);
    }

    private function createTaskForStage(Deal $deal, Stage $stage): Task
    {
        return Task::create([
            'tenant_id' => $deal->tenant_id,
            'business_group_id' => $deal->business_group_id,
            'deal_id' => $deal->id,
            'customer_id' => $deal->customer_id,
            'assigned_to_id' => $deal->assigned_to_id,
            'title' => "Действие для этапа: {$stage->name}",
            'description' => $stage->description,
            'type' => TaskType::Custom,
            'priority' => TaskPriority::Medium,
            'status' => \Modules\CatCRM\Domain\Enums\TaskStatus::Pending,
            'due_date' => now()->addHours(24),
            'correlation_id' => $this->correlationId,
        ]);
    }

    private function findOrCreateCustomer(array $orderData, int $tenantId): Customer
    {
        // Ищем клиента по email или телефону
        $customer = Customer::byTenant($tenantId)
            ->where(function ($query) use ($orderData) {
                if (!empty($orderData['email'])) {
                    $query->orWhere('email', $orderData['email']);
                }
                if (!empty($orderData['phone'])) {
                    $query->orWhere('phone', $orderData['phone']);
                }
            })
            ->first();
        
        if ($customer) {
            return $customer;
        }
        
        // Создаем нового клиента
        return Customer::create([
            'tenant_id' => $tenantId,
            'first_name' => $orderData['first_name'] ?? null,
            'last_name' => $orderData['last_name'] ?? null,
            'email' => $orderData['email'] ?? null,
            'phone' => $orderData['phone'] ?? null,
            'source' => 'marketplace',
            'correlation_id' => $this->correlationId,
        ]);
    }

    private function getPipelineForVertical(string $vertical, int $tenantId): \Modules\CatCRM\Domain\Entities\Pipeline
    {
        $pipeline = \Modules\CatCRM\Domain\Entities\Pipeline::byTenant($tenantId)
            ->byVertical($vertical)
            ->first();
        
        if ($pipeline) {
            return $pipeline;
        }
        
        // Создаем воронку из конфига
        $pipelineConfig = config("crm.pipelines.{$vertical}") ?? config('crm.pipelines.default');
        
        $pipeline = \Modules\CatCRM\Domain\Entities\Pipeline::create([
            'tenant_id' => $tenantId,
            'name' => $pipelineConfig['name'],
            'vertical' => $vertical,
            'is_default' => true,
            'correlation_id' => $this->correlationId,
        ]);
        
        // Создаем этапы
        foreach ($pipelineConfig['stages'] as $stageData) {
            Stage::create([
                'pipeline_id' => $pipeline->id,
                'name' => $stageData['name'],
                'order' => $stageData['order'],
                'probability' => $stageData['probability'],
                'is_won_stage' => $stageData['is_won_stage'] ?? false,
                'is_lost_stage' => $stageData['is_lost_stage'] ?? false,
                'correlation_id' => $this->correlationId,
            ]);
        }
        
        return $pipeline->fresh(['stages']);
    }
}
