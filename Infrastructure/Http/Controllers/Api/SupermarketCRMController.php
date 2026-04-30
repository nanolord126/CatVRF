<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\CatCRM\Application\Services\SupermarketCRMService;
use Modules\CatCRM\Application\DTOs\CreateCustomerDTO;
use Modules\CatCRM\Application\DTOs\CreateOrderDealDTO;
use Modules\CatCRM\Application\DTOs\CustomerAnalyticsDTO;
use Modules\CatCRM\Application\DTOs\TopCustomerDTO;
use Modules\CatCRM\Domain\Entities\Customer;
use Modules\CatCRM\Domain\Entities\Deal;
use Illuminate\Validation\ValidationException;

/**
 * API Controller for Supermarket CRM
 */
final class SupermarketCRMController extends Controller
{
    public function __construct(
        private readonly SupermarketCRMService $crmService
    ) {}

    /**
     * Get or create customer
     */
    public function getOrCreateCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|integer',
            'type' => 'required|string|in:individual,business',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'inn' => 'nullable|string|max:12',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other',
            'source' => 'nullable|string|max:100',
            'preferences' => 'nullable|array',
            'communication_preferences' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        $customer = $this->crmService->getOrCreateCustomer(
            $validated,
            tenant()->id,
            businessGroup()?->id
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $customer->id,
                'uuid' => $customer->uuid,
                'type' => $customer->type->value,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'loyalty_tier' => $customer->loyalty_tier->value,
                'total_spent' => $customer->total_spent,
                'orders_count' => $customer->orders_count,
                'is_vip' => $customer->is_vip,
            ],
        ]);
    }

    /**
     * Update customer statistics
     */
    public function updateCustomerStatistics(int $customerId): JsonResponse
    {
        $customer = Customer::where('tenant_id', tenant()->id)
            ->where('id', $customerId)
            ->firstOrFail();

        $this->crmService->updateCustomerStatistics($customer);

        return response()->json([
            'success' => true,
            'message' => 'Customer statistics updated successfully',
        ]);
    }

    /**
     * Create order deal
     */
    public function createOrderDeal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:crm_customers,id',
            'order_number' => 'nullable|string|max:100',
            'order_type' => 'required|string|in:one_time,subscription,return',
            'order_status' => 'required|string|max:50',
            'is_age_restricted' => 'required|boolean',
            'age_verification_required' => 'required|boolean',
            'age_verification_status' => 'required|string|max:50',
            'contains_honesty_marks' => 'required|boolean',
            'honesty_marks_count' => 'required|integer|min:0',
            'subscription_id' => 'nullable|integer',
            'subscription_type' => 'nullable|string|max:50',
            'subscription_delivery_day' => 'nullable|string|max:20',
            'subscription_next_delivery' => 'nullable|date',
            'return_id' => 'nullable|integer',
            'return_reason' => 'nullable|string',
            'return_status' => 'nullable|string|max:50',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'required|numeric|min:0',
            'delivery_fee' => 'required|numeric|min:0',
            'service_fee' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'payment_status' => 'required|string|max:50',
            'payment_method' => 'nullable|string|max:50',
            'payment_transaction_id' => 'nullable|string|max:100',
            'delivery_address' => 'nullable|string',
            'delivery_phone' => 'nullable|string|max:20',
            'delivery_name' => 'nullable|string|max:255',
            'delivery_scheduled_at' => 'nullable|date',
            'pickup_scheduled_at' => 'nullable|date',
            'special_requests' => 'nullable|string',
            'allergies' => 'nullable|array',
            'dietary_restrictions' => 'nullable|array',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
            'pipeline_id' => 'nullable|integer|exists:crm_pipelines,id',
            'assigned_to_id' => 'nullable|integer|exists:users,id',
        ]);

        $customer = Customer::where('tenant_id', tenant()->id)
            ->where('id', $validated['customer_id'])
            ->firstOrFail();

        $deal = $this->crmService->createOrderDeal(
            $customer,
            $validated,
            $validated['pipeline_id'] ?? null,
            $validated['assigned_to_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => [
                'deal_id' => $deal->id,
                'deal_uuid' => $deal->uuid,
                'customer_id' => $deal->customer_id,
                'status' => $deal->status->value,
                'value' => $deal->value,
                'created_at' => $deal->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request, int $dealId): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|max:50',
            'reason' => 'nullable|string',
        ]);

        $deal = Deal::where('tenant_id', tenant()->id)
            ->where('id', $dealId)
            ->firstOrFail();

        $this->crmService->updateOrderStatus(
            $deal,
            $validated['status'],
            $validated['reason'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
        ]);
    }

    /**
     * Check age verification
     */
    public function checkAgeVerification(Request $request, int $dealId): JsonResponse
    {
        $deal = Deal::where('tenant_id', tenant()->id)
            ->where('id', $dealId)
            ->firstOrFail();

        $result = $this->crmService->checkAgeVerification($deal, $request->user());

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Record age verification
     */
    public function recordAgeVerification(Request $request, int $dealId): JsonResponse
    {
        $validated = $request->validate([
            'method' => 'required|string|in:passport,selfie,bankid,gosuslugi',
        ]);

        $deal = Deal::where('tenant_id', tenant()->id)
            ->where('id', $dealId)
            ->firstOrFail();

        $this->crmService->recordAgeVerification($deal, $validated['method']);

        return response()->json([
            'success' => true,
            'message' => 'Age verification recorded successfully',
        ]);
    }

    /**
     * Create subscription deal
     */
    public function createSubscriptionDeal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:crm_customers,id',
            'subscription_number' => 'nullable|string|max:100',
            'subscription_type' => 'required|string|in:weekly,bi_weekly,monthly',
            'subscription_delivery_day' => 'required|string|max:20',
            'subscription_next_delivery' => 'required|date',
            // ... other order fields
        ]);

        $customer = Customer::where('tenant_id', tenant()->id)
            ->where('id', $validated['customer_id'])
            ->firstOrFail();

        $deal = $this->crmService->createSubscriptionDeal($customer, $validated);

        return response()->json([
            'success' => true,
            'data' => [
                'deal_id' => $deal->id,
                'deal_uuid' => $deal->uuid,
            ],
        ], 201);
    }

    /**
     * Update subscription delivery
     */
    public function updateSubscriptionDelivery(Request $request, int $dealId): JsonResponse
    {
        $validated = $request->validate([
            'next_delivery' => 'required|date',
        ]);

        $deal = Deal::where('tenant_id', tenant()->id)
            ->where('id', $dealId)
            ->firstOrFail();

        $this->crmService->updateSubscriptionDelivery(
            $deal,
            \Carbon\CarbonImmutable::parse($validated['next_delivery'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Subscription delivery updated successfully',
        ]);
    }

    /**
     * Create return deal
     */
    public function createReturnDeal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:crm_customers,id',
            'return_number' => 'nullable|string|max:100',
            'return_reason' => 'required|string',
            'refund_amount' => 'required|numeric|min:0',
            // ... other order fields
        ]);

        $customer = Customer::where('tenant_id', tenant()->id)
            ->where('id', $validated['customer_id'])
            ->firstOrFail();

        $deal = $this->crmService->createReturnDeal($customer, $validated);

        return response()->json([
            'success' => true,
            'data' => [
                'deal_id' => $deal->id,
                'deal_uuid' => $deal->uuid,
            ],
        ], 201);
    }

    /**
     * Get customer analytics
     */
    public function getCustomerAnalytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $analytics = $this->crmService->getCustomerAnalytics(
            tenant()->id,
            businessGroup()?->id,
            $validated['days'] ?? 30
        );

        $dto = CustomerAnalyticsDTO::fromArray($analytics);

        return response()->json([
            'success' => true,
            'data' => $dto->toArray(),
        ]);
    }

    /**
     * Get top customers
     */
    public function getTopCustomers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $customers = $this->crmService->getTopCustomers(
            tenant()->id,
            businessGroup()?->id,
            $validated['limit'] ?? 10
        );

        $dto = array_map(fn($customer) => TopCustomerDTO::fromArray($customer)->toArray(), $customers);

        return response()->json([
            'success' => true,
            'data' => $dto,
        ]);
    }
}
