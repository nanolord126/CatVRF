<?php declare(strict_types=1);

namespace App\Domains\Electronics\Http\Controllers;

use App\Domains\Electronics\DTOs\SerialNumberValidationDto;
use App\Domains\Electronics\DTOs\ReturnFraudDetectionDto;
use App\Domains\Electronics\Services\SerialNumberValidationService;
use App\Domains\Electronics\Services\ReturnFraudDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final readonly class FraudDetectionController
{
    public function __construct(
        private SerialNumberValidationService $serialValidation,
        private ReturnFraudDetectionService $returnFraudDetection,
    ) {
    }

    public function validateSerialNumber(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:electronics_products,id',
            'serial_number' => 'required|string|max:100',
            'order_id' => 'nullable|integer|exists:orders,id',
            'purchase_date' => 'nullable|date',
            'proof_of_purchase_url' => 'nullable|url|max:500',
            'idempotency_key' => 'nullable|string|max:255',
        ]);

        $userId = Auth::id();
        $correlationId = (string) Str::uuid();
        $idempotencyKey = $request->input('idempotency_key');

        if ($idempotencyKey) {
            $cachedResponse = $this->getSerialIdempotencyCache($idempotencyKey);
            if ($cachedResponse !== null) {
                return response()->json($cachedResponse);
            }
        }

        $dto = SerialNumberValidationDto::fromRequest(
            $request->all(),
            $userId,
            $correlationId
        );

        $result = $this->serialValidation->validateSerialNumber($dto);

        if ($idempotencyKey) {
            $this->setSerialIdempotencyCache($idempotencyKey, $result->toArray());
        }

        return response()->json($result->toArray());
    }

    public function detectReturnFraud(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'product_id' => 'required|integer|exists:electronics_products,id',
            'serial_number' => 'required|string|max:100',
            'return_reason' => 'required|string|max:500',
            'condition' => 'required|string|max:50|in:new,like_new,good,fair,poor,damaged',
            'device_metadata' => 'nullable|array',
            'device_metadata.imei' => 'nullable|string|max:50',
            'device_metadata.battery_health' => 'nullable|integer|min:0|max:100',
            'device_metadata.screen_condition' => 'nullable|string|max:50',
            'device_metadata.activation_date' => 'nullable|date',
            'user_behavior' => 'nullable|array',
            'user_behavior.time_on_site_minutes' => 'nullable|integer|min:0',
            'user_behavior.page_views_before_purchase' => 'nullable|integer|min:0',
            'user_behavior.cart_abandonment_rate' => 'nullable|numeric|min:0|max:1',
            'idempotency_key' => 'nullable|string|max:255',
        ]);

        $userId = Auth::id();
        $correlationId = (string) Str::uuid();
        $idempotencyKey = $request->input('idempotency_key');

        if ($idempotencyKey) {
            $cachedResponse = $this->getReturnIdempotencyCache($idempotencyKey);
            if ($cachedResponse !== null) {
                return response()->json($cachedResponse);
            }
        }

        $dto = ReturnFraudDetectionDto::fromRequest(
            $request->all(),
            $userId,
            $correlationId
        );

        $result = $this->returnFraudDetection->detectReturnFraud($dto);

        if ($idempotencyKey) {
            $this->setReturnIdempotencyCache($idempotencyKey, $result->toArray());
        }

        return response()->json($result->toArray());
    }

    public function getFraudStatistics(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = tenant()->id;

        $stats = [
            'total_serial_validations' => \Illuminate\Support\Facades\DB::table('electronics_serial_validations')
                ->where('tenant_id', $tenantId)
                ->count(),
            'fraudulent_serials' => \Illuminate\Support\Facades\DB::table('electronics_serial_validations')
                ->where('tenant_id', $tenantId)
                ->where('is_fraudulent', true)
                ->count(),
            'total_return_detections' => \Illuminate\Support\Facades\DB::table('electronics_return_fraud_detections')
                ->where('tenant_id', $tenantId)
                ->count(),
            'fraudulent_returns' => \Illuminate\Support\Facades\DB::table('electronics_return_fraud_detections')
                ->where('tenant_id', $tenantId)
                ->where('is_fraudulent', true)
                ->count(),
            'high_risk_returns_7d' => \Illuminate\Support\Facades\DB::table('electronics_return_fraud_detections')
                ->where('tenant_id', $tenantId)
                ->where('risk_level', 'high')
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
            'avg_fraud_probability' => \Illuminate\Support\Facades\DB::table('electronics_return_fraud_detections')
                ->where('tenant_id', $tenantId)
                ->where('created_at', '>=', now()->subDays(30))
                ->avg('fraud_probability') ?? 0.0,
        ];

        return response()->json($stats);
    }

    private function getSerialIdempotencyCache(string $key): ?array
    {
        return \Illuminate\Support\Facades\Cache::get("idempotency:electronics_serial:{$key}");
    }

    private function setSerialIdempotencyCache(string $key, array $data): void
    {
        \Illuminate\Support\Facades\Cache::put(
            "idempotency:electronics_serial:{$key}",
            $data,
            now()->addHours(24)
        );
    }

    private function getReturnIdempotencyCache(string $key): ?array
    {
        return \Illuminate\Support\Facades\Cache::get("idempotency:electronics_return:{$key}");
    }

    private function setReturnIdempotencyCache(string $key, array $data): void
    {
        \Illuminate\Support\Facades\Cache::put(
            "idempotency:electronics_return:{$key}",
            $data,
            now()->addHours(12)
        );
    }
}
