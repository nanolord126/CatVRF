<?php

declare(strict_types=1);

namespace Modules\PromoCampaign\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\PromoCampaign\Application\Services\PromoCampaignService;
use Modules\PromoCampaign\Presentation\Http\Requests\ApplyPromoRequest;

/**
 * Исключительно изолированный HTTP контроллер Presentation слоя для оркестрации применения скидок.
 *
 * Категорически гарантирует прием валидного запроса от роутера, передачу данных в application service,
 * и финальное формирование строго типизированного JSON ответа с обязательным возвратом correlation_id.
 */
final class PromoCampaignController extends Controller
{
    /**
     * Инициализирует и внедряет ключевой сервис применения акций.
     *
     * @param PromoCampaignService $promoService Защищенный внедряемый сервис PromoCampaignService.
     */
    public function __construct(
        private readonly PromoCampaignService $promoService
    ) {
    }

    /**
     * Обрабатывает атомарный HTTP-запрос на применение переданного клиентом промокода.
     *
     * @param ApplyPromoRequest $request Строго валидированный FormRequest-пакет данных ввода.
     * @return JsonResponse Абсолютно стандартизированный ответ HTTP с финансовым результатом.
     */
    public function apply(ApplyPromoRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $userId = (int) $request->user()?->id;

        // В случае гостевого применения можно выбрасывать UnauthorizedHttpException.
        // Для MVP допускаем условный 0, но в production это должно проходить strict auth middleware.
        if ($userId === 0) {
            abort(401, 'Необходима строгая авторизация для применения промокода.');
        }

        $result = $this->promoService->applyPromo(
            code: (string) $validated['code'],
            tenantId: (int) $validated['tenant_id'],
            userId: $userId,
            orderAmountKopecks: (int) $validated['order_amount_kopecks'],
            correlationId: (string) $validated['correlation_id']
        );

        if (!$result->success) {
            return response()->json([
                'success' => false,
                'message' => $result->errorMessage,
                'correlation_id' => $validated['correlation_id'],
            ], 400); // 400 Bad Request для логических бизнес-отказов
        }

        return response()->json([
            'success' => true,
            'discount_kopecks' => $result->discountKopecks,
            'correlation_id' => $validated['correlation_id'],
        ]);
    }
}
