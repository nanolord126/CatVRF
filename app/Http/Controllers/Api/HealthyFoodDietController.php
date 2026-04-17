<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;

final class HealthyFoodDietController extends Controller
{

    public function __construct(
            private readonly HealthyFoodService $service,
            private readonly FraudControlService $fraud,
            private readonly LogManager $logger,
            private readonly Guard $guard,
    ) {}
        public function index(): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $diets = DietPlan::where('tenant_id', $tenantId)
                    ->paginate(20);
                return $this->successResponse($diets);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('HealthyFood diets list error', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to fetch diet plans', 500);
            }
        }
        public function store(StoreDietPlanRequest $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check($this->guard->id() ?? 0, 'healthyfood_diet_store', 0, $request->ip(), null, $correlationId);
            try {
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $clientId = $this->guard->id() ?? 0;
                $diet = $this->service->createDietPlan(
                    clientId: $request->integer('client_id'),
                    dietType: $request->string('diet_type'),
                    durationDays: $request->integer('duration_days'),
                    dailyCalories: $request->integer('daily_calories'),
                    tenantId: $tenantId,
                    correlationId: $correlationId,
                );
                $this->logger->channel('audit')->info('HealthyFood diet plan created', [
                    'diet_id' => $diet->id,
                    'tenant_id' => $tenantId,
                    'correlation_id' => $correlationId,
                ]);
                return $this->successResponse($diet, 'Diet plan created successfully', 201);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('HealthyFood diet creation failed', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to create diet plan: ' . $e->getMessage(), 400);
            }
        }
        public function subscribe(int $dietId): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $clientId = $this->guard->id() ?? 0;
                $diet = DietPlan::where('tenant_id', $tenantId)->findOrFail($dietId);
                $subscription = $this->service->subscribe(
                    dietPlanId: $dietId,
                    clientId: $clientId,
                    tenantId: $tenantId,
                    correlationId: $correlationId,
                );
                $this->logger->channel('audit')->info('HealthyFood subscription created', [
                    'subscription_id' => $subscription->id,
                    'diet_id' => $dietId,
                    'correlation_id' => $correlationId,
                ]);
                return $this->successResponse($subscription, 'Subscription created', 201);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('HealthyFood subscription failed', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to subscribe: ' . $e->getMessage(), 400);
            }
        }
}
