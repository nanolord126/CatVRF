<?php declare(strict_types=1);

namespace App\Domains\HealthyFood\Services;

use App\Domains\HealthyFood\Models\HealthyMeal;
use App\Domains\HealthyFood\Models\DietPlan;
use App\Domains\HealthyFood\Models\MealSubscription;
use App\Domains\HealthyFood\Events\MealOrderCreated;
use App\Domains\HealthyFood\Events\MealDelivered;
use App\Services\FraudControlService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * HealthyFoodService — управление планами питания, подписками и доставкой.
 * КАНОН 2026: DI, DB::transaction, fraud-check, audit-лог, rate-limit.
 */
final class HealthyFoodService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    /**
     * Создать диетический план для клиента.
     */
    public function createDietPlan(
        int    $clientId,
        string $name,
        string $dietType,
        int    $durationDays,
        int    $dailyCalories,
        int    $pricePerDay,
        int    $tenantId,
        ?array $schedule = null,
    ): DietPlan {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'createDietPlan'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createDietPlan', ['domain' => __CLASS__]);

        $correlationId = Str::uuid()->toString();

        $fraudResult = $this->fraudControlService->check(
            userId:        $clientId,
            operationType: 'diet_plan_create',
            amount:        $pricePerDay * $durationDays,
            correlationId: $correlationId,
        );
        if ($fraudResult['decision'] === 'block') {
            throw new RuntimeException('Операция заблокирована системой безопасности.');
        }

        return DB::transaction(function () use (
            $clientId, $name, $dietType, $durationDays, $dailyCalories,
            $pricePerDay, $tenantId, $correlationId, $schedule
        ) {
            $plan = DietPlan::create([
                'tenant_id'      => $tenantId,
                'client_id'      => $clientId,
                'correlation_id' => $correlationId,
                'name'           => $name,
                'diet_type'      => $dietType,
                'duration_days'  => $durationDays,
                'daily_calories' => $dailyCalories,
                'price_per_day'  => $pricePerDay,
                'schedule'       => $schedule,
                'status'         => 'active',
                'starts_at'      => today(),
                'ends_at'        => today()->addDays($durationDays),
            ]);

            Log::channel('audit')->info('HealthyFood: diet plan created', [
                'correlation_id' => $correlationId,
                'plan_id'        => $plan->id,
                'client_id'      => $clientId,
            ]);

            return $plan;
        });
    }

    /**
     * Подписаться на доставку блюд.
     */
    public function subscribe(
        int    $clientId,
        int    $tenantId,
        string $deliveryAddress,
        int    $pricePerDelivery,
        string $frequency = 'weekly',
        ?int   $dietPlanId = null,
    ): MealSubscription {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'subscribe'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL subscribe', ['domain' => __CLASS__]);

        $correlationId = Str::uuid()->toString();
        $key           = "meal_sub:{$tenantId}:{$clientId}";

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw new RuntimeException('Слишком много попыток. Попробуйте позже.');
        }
        RateLimiter::hit($key, 3600);

        $nextDate = match ($frequency) {
            'biweekly' => Carbon::today()->addWeeks(2),
            'monthly'  => Carbon::today()->addMonth(),
            default    => Carbon::today()->addWeek(),
        };

        return DB::transaction(function () use (
            $clientId, $tenantId, $deliveryAddress, $pricePerDelivery,
            $frequency, $dietPlanId, $correlationId, $nextDate
        ) {
            $sub = MealSubscription::create([
                'tenant_id'           => $tenantId,
                'client_id'           => $clientId,
                'diet_plan_id'        => $dietPlanId,
                'correlation_id'      => $correlationId,
                'frequency'           => $frequency,
                'next_delivery_date'  => $nextDate,
                'delivery_address'    => $deliveryAddress,
                'price_per_delivery'  => $pricePerDelivery,
                'status'              => 'active',
            ]);

            Log::channel('audit')->info('HealthyFood: subscription created', [
                'correlation_id' => $correlationId,
                'subscription_id'=> $sub->id,
                'client_id'      => $clientId,
            ]);

            return $sub;
        });
    }

    /**
     * Отметить доставку выполненной.
     */
    public function markDelivered(int $subscriptionId): MealSubscription
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'markDelivered'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL markDelivered', ['domain' => __CLASS__]);

        $correlationId = Str::uuid()->toString();

        return DB::transaction(function () use ($subscriptionId, $correlationId) {
            /** @var MealSubscription $sub */
            $sub = MealSubscription::lockForUpdate()->findOrFail($subscriptionId);

            $next = match ($sub->frequency) {
                'biweekly' => Carbon::today()->addWeeks(2),
                'monthly'  => Carbon::today()->addMonth(),
                default    => Carbon::today()->addWeek(),
            };

            $sub->increment('total_deliveries');
            $sub->update(['next_delivery_date' => $next]);

            event(new MealDelivered($sub, $correlationId));

            Log::channel('audit')->info('HealthyFood: meal delivered', [
                'correlation_id'  => $correlationId,
                'subscription_id' => $sub->id,
                'total_deliveries'=> $sub->total_deliveries,
            ]);

            return $sub->fresh();
        });
    }

    /**
     * Список блюд по типу диеты.
     */
    public function getMealsByDiet(string $dietType, int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getMealsByDiet'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getMealsByDiet', ['domain' => __CLASS__]);

        $correlationId = Str::uuid()->toString();

        Log::channel('audit')->info('HealthyFood: get meals by diet', [
            'correlation_id' => $correlationId,
            'diet_type'      => $dietType,
            'tenant_id'      => $tenantId,
        ]);

        return HealthyMeal::where('tenant_id', $tenantId)
            ->where('diet_type', $dietType)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }
}
