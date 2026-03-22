<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use App\Models\PromoCampaign;
use App\Services\Marketing\PromoCampaignService;
use App\Services\Security\RateLimiterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PromoController extends Controller
{
    public function __construct(
        private readonly PromoCampaignService $promoCampaignService,
        private readonly FraudControlService $fraudControlService,
        private readonly RateLimiterService $rateLimiterService,
    ) {}

    /**
     * Применить промо-код к заказу.
     * POST /api/v1/promo/apply
     */
    public function apply(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $rateLimitPassed = $this->rateLimiterService->checkPromoApply(
                tenantId: (int) $request->input('tenant_id', 0),
                userId: $request->user()?->id ?? 0,
                correlationId: $correlationId,
            );

            if (!$rateLimitPassed) {
                Log::channel('fraud_alert')->warning('Promo apply rate limit exceeded', [
                    'correlation_id' => $correlationId,
                    'user_id' => $request->user()?->id,
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Слишком много попыток. Попробуйте позже.',
                    'correlation_id' => $correlationId,
                ], 429);
            }

            $fraudResult = $this->fraudControlService->check(
                userId: $request->user()?->id ?? 0,
                operationType: 'promo_apply',
                amount: (int) $request->input('amount', 0),
                ipAddress: $request->ip(),
                deviceFingerprint: $request->header('X-Device-Fingerprint'),
                correlationId: $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('Promo apply blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id' => $request->user()?->id,
                    'score' => $fraudResult['score'],
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Операция заблокирована системой безопасности.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            $request->validate([
                'code'      => 'required|string|max:50',
                'amount'    => 'required|integer|min:1',
                'tenant_id' => 'required|integer',
            ]);

            Log::channel('audit')->info('Promo apply attempt', [
                'correlation_id' => $correlationId,
                'user_id'    => $request->user()?->id,
                'tenant_id'  => $request->input('tenant_id'),
                'code'       => $request->input('code'),
                'amount'     => $request->input('amount'),
            ]);

            $result = $this->promoCampaignService->applyPromo(
                code: $request->input('code'),
                tenantId: (int) $request->input('tenant_id'),
                userId: $request->user()?->id ?? 0,
                amount: (int) $request->input('amount'),
            );

            Log::channel('audit')->info('Promo apply result', [
                'correlation_id' => $correlationId,
                'success'        => $result['success'] ?? false,
                'discount'       => $result['discount'] ?? 0,
            ]);

            if (!($result['success'] ?? false)) {
                return response()->json([
                    'success'        => false,
                    'error'          => $result['error'] ?? 'Промо-код недействителен.',
                    'correlation_id' => $correlationId,
                ], 422);
            }

            return response()->json([
                'success'        => true,
                'discount'       => $result['discount'],
                'final_amount'   => $result['final_amount'] ?? ((int) $request->input('amount') - $result['discount']),
                'correlation_id' => $correlationId,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success'        => false,
                'errors'         => $e->errors(),
                'correlation_id' => $correlationId,
            ], 422);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Promo apply failed', [
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Внутренняя ошибка. Попробуйте позже.',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Список активных кампаний для тенанта.
     * GET /api/v1/promo
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $request->validate(['tenant_id' => 'required|integer']);

            $query = PromoCampaign::where('tenant_id', (int) $request->input('tenant_id'))
                ->where('status', 'active')
                ->where('start_at', '<=', now())
                ->where('end_at', '>=', now());

            if ($request->input('vertical')) {
                $query->whereJsonContains('applicable_verticals', $request->input('vertical'));
            }

            $campaigns = $query->get(['id', 'code', 'name', 'type', 'end_at']);

            return response()->json([
                'success'        => true,
                'data'           => $campaigns,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Promo index failed', [
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Внутренняя ошибка.',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
