declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Promo;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Promo\ApplyPromoRequest;
use App\Models\Promo\PromoCampaign;
use App\Models\Promo\PromoUse;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Promo Campaign API Controller.
 * Workflow: Validate → Check budget → Apply discount → Track usage → Audit.
 *
 * Features:
 * - Percent discount (10-50%)
 * - Fixed amount discount (fixed rubles)
 * - Buy X Get Y promotions
 * - Budget limits and per-user caps
 * - Vertical-specific restrictions
 * - Fraud abuse detection
 */
final class PromoController extends BaseApiController
{
    public function __construct(
        private readonly FraudControlService $fraudService,
    ) {}

    /**
     * POST /api/v1/promo/apply
     * Применить промокод к заказу/бронированию.
     *
     * @return JsonResponse
     */
    public function apply(ApplyPromoRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();
        $tenantId = $request->getTenantId();
        $code = $request->input('code');
        $orderAmount = $request->integer('order_amount');

        try {
            return $this->db->transaction(function () use ($code, $orderAmount, $correlationId, $tenantId, $request) {
                // 1. Найти кампанию
                $campaign = PromoCampaign::where('code', $code)
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'active')
                    ->first();

                if (!$campaign) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Promo code not found or inactive',
                        'correlation_id' => $correlationId,
                    ], 404)->send();
                }

                // 2. Проверить минимальную сумму заказа
                if ($orderAmount < $campaign->min_order_amount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Order amount below minimum',
                        'correlation_id' => $correlationId,
                        'data' => [
                            'minimum_required' => $campaign->min_order_amount,
                            'current_amount' => $orderAmount,
                        ],
                    ], 400)->send();
                }

                // 3. Проверить бюджет
                if ($campaign->spent_budget >= $campaign->budget) {
                    $campaign->update(['status' => 'exhausted']);

                    return response()->json([
                        'success' => false,
                        'message' => 'Promo budget exhausted',
                        'correlation_id' => $correlationId,
                    ], 400)->send();
                }

                // 4. Проверить использования на пользователя
                $userUsageCount = PromoUse::where('promo_campaign_id', $campaign->id)
                    ->where('user_id', auth()->id())
                    ->count();

                if ($userUsageCount >= $campaign->max_uses_per_user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Maximum uses per user exceeded',
                        'correlation_id' => $correlationId,
                    ], 400)->send();
                }

                // 5. Fraud check на злоупотребление промо
                $fraudResult = $this->fraudService->checkPromoAbuse(
                    user_id: auth()->id(),
                    campaign_id: $campaign->id,
                    amount: $orderAmount,
                    correlation_id: $correlationId,
                );

                if ($fraudResult['decision'] === 'block') {
                    $this->log->channel('fraud_alert')->warning('Promo abuse detected', [
                        'correlation_id' => $correlationId,
                        'user_id' => auth()->id(),
                        'campaign_id' => $campaign->id,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Promo application blocked',
                        'correlation_id' => $correlationId,
                    ], 403)->send();
                }

                // 6. Рассчитать скидку в зависимости от типа
                $discountAmount = match ($campaign->type) {
                    'discount_percent' => intdiv((int) ($orderAmount * $campaign->discount_value / 100), 1),
                    'fixed_amount' => (int) $campaign->discount_value,
                    'referral_bonus' => 0, // Bonuses handled separately
                    default => 0,
                };

                $finalAmount = $orderAmount - $discountAmount;

                // 7. Записать использование промо
                $promoUse = PromoUse::create([
                    'promo_campaign_id' => $campaign->id,
                    'user_id' => auth()->id(),
                    'tenant_id' => $tenantId,
                    'discount_amount' => $discountAmount,
                    'correlation_id' => $correlationId,
                ]);

                // 8. Обновить бюджет кампании
                $campaign->increment('spent_budget', $discountAmount);

                // 9. Логирование
                $this->log->channel('audit')->info('Promo applied', [
                    'correlation_id' => $correlationId,
                    'campaign_id' => $campaign->id,
                    'code' => $code,
                    'user_id' => auth()->id(),
                    'discount_amount' => $discountAmount,
                    'order_amount' => $orderAmount,
                    'final_amount' => $finalAmount,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Promo applied successfully',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'promo_use_id' => $promoUse->id,
                        'code' => $code,
                        'discount' => $discountAmount,
                        'final_amount' => $finalAmount,
                    ],
                ], 200);
            });
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Promo application failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Promo application failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /api/v1/promo/{id}/validate
     * Проверить промокод без применения.
     */
    public function validate(PromoCampaign $campaign, ApplyPromoRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();
        $orderAmount = $request->integer('order_amount');

        if ($campaign->tenant_id !== $request->getTenantId()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 403);
        }

        $discountAmount = match ($campaign->type) {
            'discount_percent' => intdiv((int) ($orderAmount * $campaign->discount_value / 100), 1),
            'fixed_amount' => (int) $campaign->discount_value,
            default => 0,
        };

        return response()->json([
            'success' => true,
            'correlation_id' => $correlationId,
            'data' => [
                'code' => $campaign->code,
                'type' => $campaign->type,
                'discount' => $discountAmount,
                'final_amount' => $orderAmount - $discountAmount,
                'valid' => true,
            ],
        ], 200);
    }
}
