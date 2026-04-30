<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services;

use Modules\CatCRM\Domain\Entities\Customer;
use Modules\CatCRM\Domain\Entities\Tag;
use Modules\CatCRM\Domain\Entities\Segment;
use Modules\CatCRM\Domain\Enums\CustomerType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use App\Traits\WithAnalyticsTracking;

/**
 * Customer Service — Сервис для управления клиентами в CRM
 */
final class CustomerService
{
    use WithAuditLogging;
    use WithAnalyticsTracking;

    public function __construct(
        private readonly string $correlationId,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Создать нового клиента
     */
    public function createCustomer(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $customer = Customer::create([
                'tenant_id' => $data['tenant_id'],
                'business_group_id' => $data['business_group_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'type' => $data['type'] ?? CustomerType::Individual,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'middle_name' => $data['middle_name'] ?? null,
                'company_name' => $data['company_name'] ?? null,
                'inn' => $data['inn'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? null,
                'source' => $data['source'] ?? null,
                'is_vip' => $data['is_vip'] ?? false,
                'notes' => $data['notes'] ?? null,
                'preferences' => $data['preferences'] ?? null,
                'communication_preferences' => $data['communication_preferences'] ?? null,
                'correlation_id' => $this->correlationId,
            ]);

            // Добавляем теги если указаны
            if (!empty($data['tags'])) {
                foreach ($data['tags'] as $tagName) {
                    $tag = $this->findOrCreateTag($customer->tenant_id, $tagName);
                    $customer->addTag($tag);
                }
            }

            return $customer;
        });
    }

    /**
     * Обновить клиента
     */
    public function updateCustomer(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer->fresh();
    }

    /**
     * Обновить статистику клиента
     */
    public function updateCustomerStatistics(Customer $customer): Customer
    {
        $customer->updateStatistics();
        return $customer->fresh();
    }

    /**
     * Добавить тег клиенту
     */
    public function addTagToCustomer(Customer $customer, string $tagName): void
    {
        $tag = $this->findOrCreateTag($customer->tenant_id, $tagName);
        $customer->addTag($tag);
    }

    /**
     * Удалить тег у клиента
     */
    public function removeTagFromCustomer(Customer $customer, string $tagName): void
    {
        $tag = Tag::byTenant($customer->tenant_id)->where('name', $tagName)->first();
        if ($tag) {
            $customer->removeTag($tag);
        }
    }

    /**
     * Получить спящих клиентов
     */
    public function getSleepingCustomers(int $tenantId, int $days = 60): \Illuminate\Database\Eloquent\Collection
    {
        return Customer::byTenant($tenantId)
            ->sleeping($days)
            ->with(['deals', 'tags'])
            ->get();
    }

    /**
     * Получить VIP клиентов
     */
    public function getVipCustomers(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return Customer::byTenant($tenantId)
            ->vip()
            ->with(['deals', 'tags'])
            ->get();
    }

    /**
     * Получить клиентов по сегменту
     */
    public function getCustomersBySegment(int $segmentId): \Illuminate\Database\Eloquent\Collection
    {
        $segment = Segment::with('customers')->find($segmentId);
        return $segment ? $segment->customers : collect();
    }

    /**
     * Создать сегмент клиентов
     */
    public function createSegment(array $data): Segment
    {
        return Segment::create([
            'tenant_id' => $data['tenant_id'],
            'business_group_id' => $data['business_group_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'conditions' => $data['conditions'] ?? null,
            'is_dynamic' => $data['is_dynamic'] ?? true,
            'is_active' => $data['is_active'] ?? true,
            'correlation_id' => $this->correlationId,
        ]);
    }

    /**
     * Рассчитать сегмент (для динамических сегментов)
     */
    public function calculateSegment(Segment $segment): int
    {
        if (!$segment->is_dynamic) {
            return $segment->customers()->count();
        }

        // Здесь должна быть логика расчета сегмента на основе conditions
        // Для упрощения - возвращаем текущее количество
        $customerCount = $segment->customers()->count();
        
        $segment->update([
            'customer_count' => $customerCount,
            'last_calculated_at' => now(),
        ]);

        return $customerCount;
    }

    /**
     * Получить LTV клиента
     */
    public function getCustomerLTV(Customer $customer): array
    {
        return [
            'total_spent' => $customer->total_spent,
            'orders_count' => $customer->orders_count,
            'average_order_value' => $customer->getAverageOrderValue(),
            'loyalty_tier' => $customer->loyalty_tier->label(),
            'loyalty_discount' => $customer->loyalty_tier->discountPercent(),
        ];
    }

    /**
     * Заблокировать клиента
     */
    public function blockCustomer(Customer $customer, string $reason): Customer
    {
        $customer->update([
            'is_blocked' => true,
            'block_reason' => $reason,
        ]);
        return $customer->fresh();
    }

    /**
     * Разблокировать клиента
     */
    public function unblockCustomer(Customer $customer): Customer
    {
        $customer->update([
            'is_blocked' => false,
            'block_reason' => null,
        ]);
        return $customer->fresh();
    }

    // ========================
    // PRIVATE METHODS
    // ========================

    private function findOrCreateTag(int $tenantId, string $tagName): Tag
    {
        $tag = Tag::byTenant($tenantId)->where('name', $tagName)->first();
        
        if ($tag) {
            return $tag;
        }
        
        return Tag::create([
            'tenant_id' => $tenantId,
            'name' => $tagName,
            'correlation_id' => $this->correlationId,
        ]);
    }
}
