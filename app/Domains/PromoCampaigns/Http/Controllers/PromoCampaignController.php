<?php

declare(strict_types=1);

namespace App\Domains\PromoCampaigns\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Domains\PromoCampaigns\Http\Requests\ApplyPromoRequest;
use App\Domains\PromoCampaigns\Services\PromoCampaignService;

/**
 * Атомарный и безусловно безопасный HTTP контроллер модуля маркетинговых скидок.
 *
 * Категорически отвечает за обработку POST-запросов интеграции промокодов
 * из корзины (Checkout). Делегирует сложную DTO-логику в PromoCampaignService.
 */
final class PromoCampaignController extends Controller
{
    /**
     * Инициализирует контроллер с обязательным внедрением тяжелого сервиса.
     *
     * @param PromoCampaignService $promoService Защищенный внедряемый сервис.
     */
    public function __construct(
        private readonly PromoCampaignService $promoService
    ) {

    }

    /**
     * Обрабатывает POST-запрос на фактическое применение и списание бюджета промокода.
     * Обязательно защищается rate-limit (throttle:10,1) на уровне Route.
     *
     * @param ApplyPromoRequest $request Строго валидированный FormRequest-пакет.
     * @return JsonResponse Абсолютно стандартизированный ответ HTTP с итоговой ценой.
     */
    public function apply(ApplyPromoRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $userId = (int) $request->user()?->id;
        if ($userId === 0) {
            abort(401, 'Необходимо категорически авторизоваться для применения скидочных купонов.');
        }

        // Исключительно безопасный вызов бизнес-логики с pessimistic lock внутри.
        $discountResult = $this->promoService->applyPromo(
            code: (string) $validated['code'],
            tenantId: (int) $validated['tenant_id'],
            userId: $userId,
            orderId: (int) $validated['order_id'],
            cartSubtotal: (int) $validated['cart_subtotal'],
            correlationId: (string) $validated['correlation_id']
        );

        if (!$discountResult->success) {
            return new \Illuminate\Http\JsonResponse([
                'success' => false,
                'message' => $discountResult->message,
                'correlation_id' => $validated['correlation_id'],
            ], 400);
        }

        return new \Illuminate\Http\JsonResponse([
            'success' => true,
            'original_amount' => $discountResult->originalAmount,
            'discount_amount' => $discountResult->discountAmount,
            'final_amount' => $discountResult->finalAmount,
            'promo_use_id' => $discountResult->promoUseId,
            'message' => $discountResult->message,
            'correlation_id' => $validated['correlation_id'],
        ]);
    }
}
