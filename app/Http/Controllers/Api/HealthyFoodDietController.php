<?php
declare(strict_types=1);
namespace App\Http\Controllers\API;
use App\Domains\Food\HealthyFood\Models\DietPlan;
use App\Domains\Food\HealthyFood\Models\MealSubscription;
use App\Domains\Food\HealthyFood\Services\HealthyFoodService;
use App\Http\Requests\HealthyFood\StoreDietPlanRequest;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
final class HealthyFoodDietController extends BaseApiController
{
    public function __construct(
        private readonly HealthyFoodService $service,
        private readonly FraudControlService $fraudControlService,
    ) {}
    public function index(): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;
            $diets = DietPlan::where('tenant_id', $tenantId)
                ->paginate(20);
            return $this->successResponse($diets);
        } catch (\Exception $e) {
            Log::channel('audit')->error('HealthyFood diets list error', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch diet plans', 500);
        }
    }
    public function store(StoreDietPlanRequest $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'healthyfood_diet_store', 0, $request->ip(), null, $correlationId);
        try {
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;
            $clientId = auth()->id() ?? 0;
            $diet = $this->service->createDietPlan(
                clientId: $request->integer('client_id'),
                dietType: $request->string('diet_type'),
                durationDays: $request->integer('duration_days'),
                dailyCalories: $request->integer('daily_calories'),
                tenantId: $tenantId,
                correlationId: $correlationId,
            );
            Log::channel('audit')->info('HealthyFood diet plan created', [
                'diet_id' => $diet->id,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);
            return $this->successResponse($diet, 'Diet plan created successfully', 201);
        } catch (\Exception $e) {
            Log::channel('audit')->error('HealthyFood diet creation failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to create diet plan: ' . $e->getMessage(), 400);
        }
    }
    public function subscribe(int $dietId): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;
            $clientId = auth()->id() ?? 0;
            $diet = DietPlan::where('tenant_id', $tenantId)->findOrFail($dietId);
            $subscription = $this->service->subscribe(
                dietPlanId: $dietId,
                clientId: $clientId,
                tenantId: $tenantId,
                correlationId: $correlationId,
            );
            Log::channel('audit')->info('HealthyFood subscription created', [
                'subscription_id' => $subscription->id,
                'diet_id' => $dietId,
                'correlation_id' => $correlationId,
            ]);
            return $this->successResponse($subscription, 'Subscription created', 201);
        } catch (\Exception $e) {
            Log::channel('audit')->error('HealthyFood subscription failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to subscribe: ' . $e->getMessage(), 400);
        }
    }
}
