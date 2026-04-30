<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use App\Services\CustomerAnonymizationService;
use Modules\CatCRM\Domain\Entities\Customer;
use Modules\CatCRM\Domain\Entities\Deal;
use Modules\CatCRM\Domain\Entities\Interaction;
use Modules\CatCRM\Domain\Entities\Segment;
use Modules\CatCRM\Domain\Entities\Tag;
use Modules\CatCRM\Domain\Verticals\Supermarket\SupermarketOrder;
use Modules\CatCRM\Domain\Enums\CustomerType;
use Modules\CatCRM\Domain\Enums\DealStatus;
use Modules\CatCRM\Domain\Enums\InteractionType;
use Modules\CatCRM\Domain\Enums\LoyaltyTier;
use Modules\Supermarket\Application\Services\SubscriptionService;
use Modules\Supermarket\Application\Services\ReturnService;
use Modules\Supermarket\Application\Services\AgeVerificationService;
use Modules\Supermarket\Application\Services\HonestyMarkService;
use Modules\Supermarket\Application\Services\BatchDistributionMLService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;

/**
 * SupermarketCRMService — CRM сервис для вертикали Супермаркет
 * 
 * Управляет клиентскими данными, сделками, сегментацией и аналитикой
 * специально для супермаркетов (подписки, возвраты, возрастные ограничения,
 * честные знаки, лояльность).
 */
final class SupermarketCRMService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;
    private readonly SubscriptionService $subscriptionService;
    private readonly ReturnService $returnService;
    private readonly AgeVerificationService $ageVerificationService;
    private readonly HonestyMarkService $honestyMarkService;
    private readonly CustomerAnonymizationService $anonymization;

    public function __construct(
        FraudControlService $fraudControl,
        SubscriptionService $subscriptionService,
        ReturnService $returnService,
        CustomerAnonymizationService $anonymization,
        AgeVerificationService $ageVerificationService,
        HonestyMarkService $honestyMarkService
    ) {
        $this->fraudControl = $fraudControl;
        $this->subscriptionService = $subscriptionService;
        $this->returnService = $returnService;
        $this->ageVerificationService = $ageVerificationService;
        $this->honestyMarkService = $honestyMarkService;
        $this->anonymization = $anonymization;
        $this->anonymization = $anonymization;
    }

    // ========================
    // CUSTOMER MANAGEMENT
    // ========================

    /**
     * Создать или найти клиента супермаркета
     */
    public function getOrCreateCustomer(array $customerData, int $tenantId, ?int $businessGroupId = null): Customer
    {
        return $this->withSpan(
            'supermarket_crm.get_or_create_customer',
            function () use ($customerData, $tenantId, $businessGroupId) {
                // Fraud check
                if ($customerData['user_id'] ?? 0 > 0) {
                    $this->fraudControl->check($customerData['user_id'], 'crm_customer_create', 0);
                }

                // Ищем клиента по email или телефону
                $customer = Customer::where('tenant_id', $tenantId)
                    ->where('business_group_id', $businessGroupId)
                    ->where(function ($query) use ($customerData) {
                        if (!empty($customerData['email'])) {
                            $query->orWhere('email', $customerData['email']);
                        }
                        if (!empty($customerData['phone'])) {
                            $query->orWhere('phone', $customerData['phone']);
                        }
                    })
                    ->first();

                if ($customer) {
                    // Обновляем данные если нужно
                    $customer->update(array_filter($customerData, fn($v) => $v !== null));
                    return $customer;
                }

                // Создаем нового клиента
                $customer = Customer::create([
                    'tenant_id' => $tenantId,
                    'business_group_id' => $businessGroupId,
                    'user_id' => $customerData['user_id'] ?? null,
                    'type' => CustomerType::from($customerData['type'] ?? 'individual'),
                    'first_name' => $customerData['first_name'] ?? null,
                    'last_name' => $customerData['last_name'] ?? null,
                    'middle_name' => $customerData['middle_name'] ?? null,
                    'company_name' => $customerData['company_name'] ?? null,
                    'inn' => $customerData['inn'] ?? null,
                    'email' => $customerData['email'] ?? null,
                    'phone' => $customerData['phone'] ?? null,
                    'address' => $customerData['address'] ?? null,
                    'city' => $customerData['city'] ?? null,
                    'country' => $customerData['country'] ?? null,
                    'postal_code' => $customerData['postal_code'] ?? null,
                    'birth_date' => $customerData['birth_date'] ?? null,
                    'gender' => $customerData['gender'] ?? null,
                    'source' => $customerData['source'] ?? 'supermarket',
                    'loyalty_tier' => LoyaltyTier::Bronze,
                    'total_spent' => 0,
                    'orders_count' => 0,
                    'preferences' => $customerData['preferences'] ?? [],
                    'communication_preferences' => $customerData['communication_preferences'] ?? [],
                    'metadata' => $customerData['metadata'] ?? [],
                ]);

                $this->logCreated('crm_customer', $customer->id, [
                    'vertical' => 'supermarket',
                ], null, $tenantId);

                return $customer;
            },
            $this->getStandardAttributes('supermarket', 'crm_get_or_create_customer'),
        );
    }

    /**
     * Обновить статистику клиента (LTV, заказы, сегментация)
     */
    public function updateCustomerStatistics(Customer $customer): bool
    {
        return $this->withSpan(
            'supermarket_crm.update_customer_statistics',
            function () use ($customer) {
                $deals = Deal::where('customer_id', $customer->id)
                    ->where('status', DealStatus::Won)
                    ->get();

                $totalSpent = $deals->sum('value');
                $ordersCount = $deals->count();
                $lastOrderAt = $deals->max('actual_close_date');

                // Получаем данные о подписках
                $activeSubscriptions = SupermarketOrder::where('customer_id', $customer->id)
                    ->subscription()
                    ->whereIn('order_status', ['active', 'confirmed'])
                    ->count();

                // Получаем данные о возвратах
                $returnsCount = SupermarketOrder::where('customer_id', $customer->id)
                    ->return()
                    ->count();

                $customer->update([
                    'total_spent' => $totalSpent,
                    'orders_count' => $ordersCount,
                    'last_order_at' => $lastOrderAt,
                    'last_interaction_at' => now(),
                    'metadata' => array_merge($customer->metadata ?? [], [
                        'active_subscriptions' => $activeSubscriptions,
                        'returns_count' => $returnsCount,
                        'vertical' => 'supermarket',
                    ]),
                ]);

                // Обновляем loyalty tier
                $this->updateCustomerLoyaltyTier($customer);

                // Автоматическая сегментация
                $this->autoSegmentCustomer($customer);

                return true;
            },
            $this->getStandardAttributes('supermarket', 'crm_update_customer_statistics'),
        );
    }

    /**
     * Обновить loyalty tier клиента
     */
    private function updateCustomerLoyaltyTier(Customer $customer): void
    {
        $tiers = config('crm.loyalty_tiers', [
            'Bronze' => ['min_spent' => 0],
            'Silver' => ['min_spent' => 10000],
            'Gold' => ['min_spent' => 50000],
            'Platinum' => ['min_spent' => 100000],
        ]);

        $currentTier = LoyaltyTier::Bronze;

        foreach ($tiers as $tierName => $tierConfig) {
            if ($customer->total_spent >= $tierConfig['min_spent']) {
                $currentTier = LoyaltyTier::from($tierName);
            }
        }

        $customer->loyalty_tier = $currentTier;
        $customer->save();
    }

    /**
     * Автоматическая сегментация клиента
     */
    private function autoSegmentCustomer(Customer $customer): void
    {
        $segments = [];

        // Сегмент по активности
        if ($customer->isSleeping(60)) {
            $segments[] = 'sleeping_customers';
        } elseif ($customer->orders_count >= 10) {
            $segments[] = 'regular_customers';
        } elseif ($customer->orders_count >= 1) {
            $segments[] = 'new_customers';
        }

        // Сегмент по подпискам
        $activeSubscriptions = $customer->metadata['active_subscriptions'] ?? 0;
        if ($activeSubscriptions > 0) {
            $segments[] = 'subscription_users';
        }

        // Сегмент по возвратам
        $returnsCount = $customer->metadata['returns_count'] ?? 0;
        if ($returnsCount > 3) {
            $segments[] = 'high_return_rate';
        }

        // Сегмент по LTV
        if ($customer->total_spent >= 100000) {
            $segments[] = 'high_value_customers';
            $customer->is_vip = true;
        } elseif ($customer->total_spent >= 50000) {
            $segments[] = 'medium_value_customers';
        }

        $customer->save();

        // Применяем сегменты
        foreach ($segments as $segmentName) {
            $segment = Segment::where('name', $segmentName)->first();
            if ($segment) {
                $customer->segments()->syncWithoutDetaching([$segment->id]);
            }
        }
    }

    // ========================
    // ORDER/DEAL MANAGEMENT
    // ========================

    /**
     * Создать CRM сделку для заказа супермаркета
     */
    public function createOrderDeal(
        Customer $customer,
        array $orderData,
        ?int $pipelineId = null,
        ?int $assignedToId = null
    ): Deal {
        return $this->withSpan(
            'supermarket_crm.create_order_deal',
            function () use ($customer, $orderData, $pipelineId, $assignedToId) {
                // Fraud check
                $this->fraudControl->check($customer->user_id ?? 0, 'crm_deal_create', (int) (($orderData['total_amount'] ?? 0) * 100));

                $deal = Deal::create([
                    'tenant_id' => $customer->tenant_id,
                    'business_group_id' => $customer->business_group_id,
                    'pipeline_id' => $pipelineId,
                    'customer_id' => $customer->id,
                    'title' => $orderData['title'] ?? 'Заказ #' . ($orderData['order_number'] ?? ''),
                    'description' => $orderData['description'] ?? null,
                    'value' => (int) (($orderData['total_amount'] ?? 0) * 100), // в копейках
                    'currency' => 'RUB',
                    'status' => DealStatus::New,
                    'source' => $orderData['source'] ?? 'supermarket',
                    'priority' => $orderData['priority'] ?? 3,
                    'expected_close_date' => $orderData['delivery_scheduled_at'] ?? now()->addDays(1),
                    'assigned_to_id' => $assignedToId,
                    'metadata' => array_merge($orderData['metadata'] ?? [], [
                        'vertical' => 'supermarket',
                        'order_type' => $orderData['order_type'] ?? 'one_time',
                    ]),
                ]);

                // Создаем вертикаль-специфичную запись
                SupermarketOrder::create([
                    'tenant_id' => $customer->tenant_id,
                    'business_group_id' => $customer->business_group_id,
                    'deal_id' => $deal->id,
                    'customer_id' => $customer->id,
                    'supermarket_id' => $orderData['supermarket_id'] ?? null,
                    'order_number' => $orderData['order_number'] ?? null,
                    'order_type' => $orderData['order_type'] ?? 'one_time',
                    'order_status' => $orderData['order_status'] ?? 'pending',
                    'is_age_restricted' => $orderData['is_age_restricted'] ?? false,
                    'age_verification_required' => $orderData['age_verification_required'] ?? false,
                    'age_verification_status' => $orderData['age_verification_status'] ?? 'not_required',
                    'contains_honesty_marks' => $orderData['contains_honesty_marks'] ?? false,
                    'honesty_marks_count' => $orderData['honesty_marks_count'] ?? 0,
                    'subscription_id' => $orderData['subscription_id'] ?? null,
                    'subscription_type' => $orderData['subscription_type'] ?? null,
                    'subscription_delivery_day' => $orderData['subscription_delivery_day'] ?? null,
                    'subscription_next_delivery' => $orderData['subscription_next_delivery'] ?? null,
                    'return_id' => $orderData['return_id'] ?? null,
                    'return_reason' => $orderData['return_reason'] ?? null,
                    'return_status' => $orderData['return_status'] ?? null,
                    'subtotal' => $orderData['subtotal'] ?? 0,
                    'discount_amount' => $orderData['discount_amount'] ?? 0,
                    'delivery_fee' => $orderData['delivery_fee'] ?? 0,
                    'service_fee' => $orderData['service_fee'] ?? 0,
                    'tax_amount' => $orderData['tax_amount'] ?? 0,
                    'total_amount' => $orderData['total_amount'] ?? 0,
                    'payment_status' => $orderData['payment_status'] ?? 'pending',
                    'payment_method' => $orderData['payment_method'] ?? null,
                    'delivery_address' => $orderData['delivery_address'] ?? null,
                    'delivery_phone' => $orderData['delivery_phone'] ?? null,
                    'delivery_name' => $orderData['delivery_name'] ?? null,
                    'delivery_scheduled_at' => $orderData['delivery_scheduled_at'] ?? null,
                    'pickup_scheduled_at' => $orderData['pickup_scheduled_at'] ?? null,
                    'special_requests' => $orderData['special_requests'] ?? null,
                    'allergies' => $orderData['allergies'] ?? [],
                    'dietary_restrictions' => $orderData['dietary_restrictions'] ?? [],
                    'notes' => $orderData['notes'] ?? null,
                    'metadata' => $orderData['metadata'] ?? [],
                ]);

                $this->logCreated('crm_deal', $deal->id, [
                    'customer_id' => $customer->id,
                    'vertical' => 'supermarket',
                    'order_type' => $orderData['order_type'] ?? 'one_time',
                ], null, $customer->tenant_id);

                // Логируем взаимодействие
                Interaction::create([
                    'customer_id' => $customer->id,
                    'deal_id' => $deal->id,
                    'type' => InteractionType::Order,
                    'direction' => 'inbound',
                    'summary' => 'Создан заказ в супермаркете',
                    'metadata' => [
                        'order_type' => $orderData['order_type'] ?? 'one_time',
                        'amount' => $orderData['total_amount'] ?? 0,
                    ],
                ]);

                return $deal;
            },
            $this->getStandardAttributes('supermarket', 'crm_create_order_deal'),
        );
    }

    /**
     * Обновить статус заказа в CRM
     */
    public function updateOrderStatus(Deal $deal, string $status, ?string $reason = null): bool
    {
        return $this->withSpan(
            'supermarket_crm.update_order_status',
            function () use ($deal, $status, $reason) {
                $supermarketOrder = SupermarketOrder::where('deal_id', $deal->id)->first();
                
                if ($supermarketOrder) {
                    $supermarketOrder->order_status = $status;
                    if ($reason) {
                        $supermarketOrder->cancellation_reason = $reason;
                    }
                    $supermarketOrder->save();
                }

                // Если заказ завершен успешно
                if (in_array($status, ['completed', 'delivered', 'picked_up'])) {
                    $deal->win($reason ?? 'Заказ выполнен');
                    
                    // Обновляем статистику клиента
                    $this->updateCustomerStatistics($deal->customer);
                }

                // Если заказ отменен
                if ($status === 'cancelled') {
                    $deal->lose($reason ?? 'Заказ отменен');
                }

                // Логируем взаимодействие
                Interaction::create([
                    'customer_id' => $deal->customer_id,
                    'deal_id' => $deal->id,
                    'type' => InteractionType::Note,
                    'direction' => 'system',
                    'summary' => "Статус заказа изменен на: {$status}",
                    'metadata' => [
                        'old_status' => $deal->status,
                        'new_status' => $status,
                        'reason' => $reason,
                    ],
                ]);

                return true;
            },
            $this->getStandardAttributes('supermarket', 'crm_update_order_status'),
        );
    }

    // ========================
    // AGE VERIFICATION INTEGRATION
    // ========================

    /**
     * Проверить возраст верификацию для заказа
     */
    public function checkAgeVerification(Deal $deal, \App\Models\User $user): array
    {
        return $this->withSpan(
            'supermarket_crm.check_age_verification',
            function () use ($deal, $user) {
                $supermarketOrder = SupermarketOrder::where('deal_id', $deal->id)->first();
                
                if (!$supermarketOrder || !$supermarketOrder->requiresAgeVerification()) {
                    return ['required' => false];
                }

                $verificationStatus = $this->ageVerificationService->checkUserVerificationStatus($user);

                if ($verificationStatus['is_verified'] && !$verificationStatus['is_expired']) {
                    $supermarketOrder->verifyAge($verificationStatus['method']);
                    return ['required' => false, 'verified' => true, 'method' => $verificationStatus['method']];
                }

                $supermarketOrder->age_verification_status = 'pending';
                $supermarketOrder->save();

                return [
                    'required' => true,
                    'verified' => false,
                    'methods' => ['passport', 'selfie', 'bankid', 'gosuslugi'],
                ];
            },
            $this->getStandardAttributes('supermarket', 'crm_check_age_verification'),
        );
    }

    /**
     * Записать успешную верификацию возраста
     */
    public function recordAgeVerification(Deal $deal, string $method): bool
    {
        $supermarketOrder = SupermarketOrder::where('deal_id', $deal->id)->first();
        
        if ($supermarketOrder) {
            $supermarketOrder->verifyAge($method);
            
            // Логируем взаимодействие
            Interaction::create([
                'customer_id' => $deal->customer_id,
                'deal_id' => $deal->id,
                'type' => InteractionType::Note,
                'direction' => 'system',
                'summary' => "Возрастная верификация пройдена: {$method}",
                'metadata' => [
                    'verification_method' => $method,
                ],
            ]);

            return true;
        }

        return false;
    }

    // ========================
    // SUBSCRIPTION MANAGEMENT
    // ========================

    /**
     * Создать CRM запись для подписки
     */
    public function createSubscriptionDeal(Customer $customer, array $subscriptionData): Deal
    {
        return $this->withSpan(
            'supermarket_crm.create_subscription_deal',
            function () use ($customer, $subscriptionData) {
                // Fraud check
                $this->fraudControl->check($customer->user_id ?? 0, 'crm_subscription_create', (int) (($subscriptionData['amount'] ?? 0) * 100));

                $deal = $this->createOrderDeal($customer, array_merge($subscriptionData, [
                    'order_type' => 'subscription',
                    'title' => 'Подписка #' . ($subscriptionData['subscription_number'] ?? ''),
                    'description' => 'Регулярная доставка продуктов',
                ]));

                // Добавляем тег подписчика
                $subscriptionTag = Tag::firstOrCreate(['name' => 'subscription_user']);
                $customer->addTag($subscriptionTag);

                return $deal;
            },
            $this->getStandardAttributes('supermarket', 'crm_create_subscription_deal'),
        );
    }

    /**
     * Обновить следующую доставку подписки
     */
    public function updateSubscriptionDelivery(Deal $deal, CarbonImmutable $nextDelivery): bool
    {
        $supermarketOrder = SupermarketOrder::where('deal_id', $deal->id)->first();
        
        if ($supermarketOrder && $supermarketOrder->isSubscription()) {
            $supermarketOrder->subscription_next_delivery = $nextDelivery;
            $supermarketOrder->save();
            return true;
        }

        return false;
    }

    // ========================
    // RETURN MANAGEMENT
    // ========================

    /**
     * Создать CRM запись для возврата
     */
    public function createReturnDeal(Customer $customer, array $returnData): Deal
    {
        return $this->withSpan(
            'supermarket_crm.create_return_deal',
            function () use ($customer, $returnData) {
                // Fraud check
                $this->fraudControl->check($customer->user_id ?? 0, 'crm_return_create', (int) (($returnData['refund_amount'] ?? 0) * 100));

                $deal = $this->createOrderDeal($customer, array_merge($returnData, [
                    'order_type' => 'return',
                    'title' => 'Возврат #' . ($returnData['return_number'] ?? ''),
                    'description' => $returnData['return_reason'] ?? '',
                    'value' => -(int) (($returnData['refund_amount'] ?? 0) * 100), // отрицательное значение
                ]));

                // Логируем взаимодействие
                Interaction::create([
                    'customer_id' => $customer->id,
                    'deal_id' => $deal->id,
                    'type' => InteractionType::Note,
                    'direction' => 'inbound',
                    'summary' => 'Создан запрос на возврат',
                    'metadata' => [
                        'return_reason' => $returnData['return_reason'] ?? '',
                        'refund_amount' => $returnData['refund_amount'] ?? 0,
                    ],
                ]);

                return $deal;
            },
            $this->getStandardAttributes('supermarket', 'crm_create_return_deal'),
        );
    }

    // ========================
    // ANALYTICS & REPORTING
    // ========================

    /**
     * Получить аналитику по клиентам супермаркета
     */
    public function getCustomerAnalytics(int $tenantId, ?int $businessGroupId = null, int $days = 30): array
    {
        return $this->withSpan(
            'supermarket_crm.get_customer_analytics',
            function () use ($tenantId, $businessGroupId, $days) {
                $query = Customer::where('tenant_id', $tenantId)
                    ->where('business_group_id', $businessGroupId);

                $totalCustomers = $query->count();
                $newCustomers = $query->where('created_at', '>=', now()->subDays($days))->count();
                $activeCustomers = $query->where('last_interaction_at', '>=', now()->subDays($days))->count();
                $sleepingCustomers = $query->sleeping($days)->count();
                $vipCustomers = $query->vip()->count();

                $totalRevenue = Deal::where('tenant_id', $tenantId)
                    ->where('business_group_id', $businessGroupId)
                    ->where('status', DealStatus::Won)
                    ->where('actual_close_date', '>=', now()->subDays($days))
                    ->sum('value');

                $subscriptionUsers = SupermarketOrder::where('tenant_id', $tenantId)
                    ->where('business_group_id', $businessGroupId)
                    ->subscription()
                    ->whereIn('order_status', ['active', 'confirmed'])
                    ->distinct('customer_id')
                    ->count();

                $returnsCount = SupermarketOrder::where('tenant_id', $tenantId)
                    ->where('business_group_id', $businessGroupId)
                    ->return()
                    ->where('created_at', '>=', now()->subDays($days))
                    ->count();

                return [
                    'total_customers' => $totalCustomers,
                    'new_customers' => $newCustomers,
                    'active_customers' => $activeCustomers,
                    'sleeping_customers' => $sleepingCustomers,
                    'vip_customers' => $vipCustomers,
                    'total_revenue' => $totalRevenue / 100, // в рублях
                    'subscription_users' => $subscriptionUsers,
                    'returns_count' => $returnsCount,
                    'period_days' => $days,
                ];
            },
            $this->getStandardAttributes('supermarket', 'crm_get_customer_analytics')
        );
    }

    /**
     * Получить топ клиентов по LTV
     */
    public function getTopCustomers(int $tenantId, ?int $businessGroupId = null, int $limit = 10): array
    {
        return Customer::where('tenant_id', $tenantId)
            ->where('business_group_id', $businessGroupId)
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get()
            ->map(fn($customer) => [
                'id' => $customer->id,
                'name' => $customer->getDisplayName(),
                'email' => $customer->email,
                'phone' => $customer->phone,
                'total_spent' => $customer->total_spent,
                'orders_count' => $customer->orders_count,
                'loyalty_tier' => $customer->loyalty_tier->value,
                'last_order_at' => $customer->last_order_at?->toIso8601String(),
            ])
            ->toArray();
    }

    /**
     * Получить аналитику по диетическим предпочтениям (включая халяль)
     */
    public function getDietaryAnalytics(int $tenantId, ?int $businessGroupId = null, int $days = 30): array
    {
        return $this->withSpan(
            'supermarket_crm.get_dietary_analytics',
            function () use ($tenantId, $businessGroupId, $days) {
                $query = SupermarketOrder::where('tenant_id', $tenantId)
                    ->where('business_group_id', $businessGroupId)
                    ->where('created_at', '>=', now()->subDays($days));

                $totalOrders = $query->count();
                
                $halalOrders = (clone $query)->whereJsonContains('dietary_restrictions', ['halal'])->count();
                $kosherOrders = (clone $query)->whereJsonContains('dietary_restrictions', ['kosher'])->count();
                $veganOrders = (clone $query)->whereJsonContains('dietary_restrictions', ['vegan'])->count();
                $vegetarianOrders = (clone $query)->whereJsonContains('dietary_restrictions', ['vegetarian'])->count();
                $glutenFreeOrders = (clone $query)->whereJsonContains('dietary_restrictions', ['gluten_free'])->count();
                $lactoseFreeOrders = (clone $query)->whereJsonContains('dietary_restrictions', ['lactose_free'])->count();

                return [
                    'total_orders' => $totalOrders,
                    'halal_orders' => $halalOrders,
                    'halal_percentage' => $totalOrders > 0 ? round($halalOrders / $totalOrders * 100, 2) : 0,
                    'kosher_orders' => $kosherOrders,
                    'kosher_percentage' => $totalOrders > 0 ? round($kosherOrders / $totalOrders * 100, 2) : 0,
                    'vegan_orders' => $veganOrders,
                    'vegan_percentage' => $totalOrders > 0 ? round($veganOrders / $totalOrders * 100, 2) : 0,
                    'vegetarian_orders' => $vegetarianOrders,
                    'vegetarian_percentage' => $totalOrders > 0 ? round($vegetarianOrders / $totalOrders * 100, 2) : 0,
                    'gluten_free_orders' => $glutenFreeOrders,
                    'gluten_free_percentage' => $totalOrders > 0 ? round($glutenFreeOrders / $totalOrders * 100, 2) : 0,
                    'lactose_free_orders' => $lactoseFreeOrders,
                    'lactose_free_percentage' => $totalOrders > 0 ? round($lactoseFreeOrders / $totalOrders * 100, 2) : 0,
                    'period_days' => $days,
                ];
            },
            $this->getStandardAttributes('supermarket', 'crm_get_dietary_analytics')
        );
    }

    /**
     * Анализ спроса по продуктам и категориям
     */
    public function getDemandAnalysis(int $tenantId, ?int $businessGroupId = null, int $days = 30): array
    {
        return $this->withSpan(
            'supermarket_crm.get_demand_analysis',
            function () use ($tenantId, $businessGroupId, $days) {
                // TODO: Implement actual demand analysis from order data
                // For now, return mock data structure
                
                $startDate = now()->subDays($days);
                $endDate = now();
                
                return [
                    'period' => [
                        'start' => $startDate->toIso8601String(),
                        'end' => $endDate->toIso8601String(),
                        'days' => $days,
                    ],
                    'total_demand' => [
                        'orders' => 1200,
                        'items' => 5000,
                        'revenue' => 1500000,
                    ],
                    'top_products' => [
                        [
                            'product_id' => 1,
                            'name' => 'Молоко 3.2%',
                            'category' => 'Молочные',
                            'sold_quantity' => 500,
                            'revenue' => 50000,
                            'avg_daily_demand' => 16.7,
                            'trend' => 'increasing',
                            'trend_percentage' => 12.5,
                        ],
                        [
                            'product_id' => 2,
                            'name' => 'Хлеб белый',
                            'category' => 'Выпечка',
                            'sold_quantity' => 400,
                            'revenue' => 20000,
                            'avg_daily_demand' => 13.3,
                            'trend' => 'stable',
                            'trend_percentage' => 2.1,
                        ],
                        [
                            'product_id' => 3,
                            'name' => 'Яйца 10шт',
                            'category' => 'Яйца',
                            'sold_quantity' => 350,
                            'revenue' => 35000,
                            'avg_daily_demand' => 11.7,
                            'trend' => 'increasing',
                            'trend_percentage' => 8.3,
                        ],
                    ],
                    'category_demand' => [
                        [
                            'category' => 'Молочные',
                            'sold_quantity' => 1500,
                            'revenue' => 150000,
                            'percentage' => 30,
                            'trend' => 'increasing',
                        ],
                        [
                            'category' => 'Выпечка',
                            'sold_quantity' => 1200,
                            'revenue' => 60000,
                            'percentage' => 24,
                            'trend' => 'stable',
                        ],
                        [
                            'category' => 'Овощи',
                            'sold_quantity' => 1000,
                            'revenue' => 80000,
                            'percentage' => 20,
                            'trend' => 'increasing',
                        ],
                    ],
                    'seasonal_trends' => [
                        'current_month' => now()->month,
                        'seasonal_factor' => 1.1,
                        'seasonality' => 'high_demand',
                        'next_month_prediction' => 'increasing',
                    ],
                    'demand_forecast' => [
                        'next_7_days' => 420,
                        'next_14_days' => 840,
                        'next_30_days' => 1800,
                        'confidence' => 0.85,
                    ],
                ];
            },
            $this->getStandardAttributes('supermarket', 'crm_get_demand_analysis')
        );
    }

    /**
     * Статистика по точкам продаж
     */
    public function getStoreStatistics(int $tenantId, ?int $businessGroupId = null, int $days = 30): array
    {
        return $this->withSpan(
            'supermarket_crm.get_store_statistics',
            function () use ($tenantId, $businessGroupId, $days) {
                // TODO: Implement actual store statistics
                return [
                    'total_stores' => 5,
                    'active_stores' => 5,
                    'top_performing_stores' => [
                        [
                            'store_id' => 1,
                            'name' => 'Точка #1 (Центр)',
                            'orders' => 300,
                            'revenue' => 450000,
                            'avg_order_value' => 1500,
                            'customers' => 250,
                        ],
                        [
                            'store_id' => 2,
                            'name' => 'Точка #2 (Север)',
                            'orders' => 250,
                            'revenue' => 375000,
                            'avg_order_value' => 1500,
                            'customers' => 210,
                        ],
                    ],
                    'delivery_vs_pickup' => [
                        'courier' => [
                            'count' => 800,
                            'percentage' => 66.7,
                            'avg_time' => 45,
                        ],
                        'pickup' => [
                            'count' => 400,
                            'percentage' => 33.3,
                            'avg_wait_time' => 15,
                        ],
                    ],
                    'peak_hours' => [
                        'morning' => ['08:00', '10:00'],
                        'evening' => ['17:00', '19:00'],
                    ],
                ];
            },
            $this->getStandardAttributes('supermarket', 'crm_get_store_statistics')
        );
    }
}
        );
    }
}
            $factor >= 0.85 => 'low_demand',
            default => 'very_low_demand',
        };
    }

    /**
     * Статистика по точкам продаж
     */
    public function getStoreStatistics(int $tenantId, ?int $businessGroupId = null, int $days = 30): array
    {
        return $this->withSpan(
            'supermarket_crm.get_store_statistics',
            function () use ($tenantId, $businessGroupId, $days) {
                // TODO: Implement actual store statistics
                return [
                    'total_stores' => 5,
                    'active_stores' => 5,
                    'top_performing_stores' => [
                        [
                            'store_id' => 1,
                            'name' => 'Точка #1 (Центр)',
                            'orders' => 300,
                            'revenue' => 450000,
                            'avg_order_value' => 1500,
                            'customers' => 250,
                        ],
                        [
                            'store_id' => 2,
                            'name' => 'Точка #2 (Север)',
                            'orders' => 250,
                            'revenue' => 375000,
                            'avg_order_value' => 1500,
                            'customers' => 210,
                        ],
                    ],
                    'delivery_vs_pickup' => [
                        'courier' => [
                            'count' => 800,
                            'percentage' => 66.7,
                            'avg_time' => 45,
                        ],
                        'pickup' => [
                            'count' => 400,
                            'percentage' => 33.3,
                            'avg_wait_time' => 15,
                        ],
                    ],
                    'peak_hours' => [
                        'morning' => ['08:00', '10:00'],
                        'evening' => ['17:00', '19:00'],
                    ],
                ];
            },
            $this->getStandardAttributes('supermarket', 'crm_get_store_statistics')
        );
    }
}
