<?php

declare(strict_types=1);

namespace App\Domains\Referral\Http\Controllers;

use Symfony\Component\HttpKernel\Exception\HttpException;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Domains\Referral\Http\Requests\GenerateReferralLinkRequest;
use App\Domains\Referral\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Категорический публичный контроллер реферальной системы (ReferralSystem).
 *
 * Безусловно делегирует валидированные данные в ReferralService, обеспечивая
 * строгую HTTP обертку для сложной логики вознаграждений и учета (DTO to Array).
 */
final class ReferralController extends Controller
{
    /**
     * Конструктор с обязательным внедрением зависимостей (Dependency Injection).
     *
     * @param  ReferralService  $referralService  Абсолютно главный сервис рефералки.
     */
    public function __construct(
        private readonly ReferralService $referralService
    ) {}

    /**
     * Генерирует уникальную ссылку для действующего авторизованного пользователя.
     *
     * @param  GenerateReferralLinkRequest  $request  Строго валидированный запрос.
     * @return JsonResponse Отвечает консистентным JSON объектом.
     */
    public function generateLink(GenerateReferralLinkRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = (int) $request->user()?->id;

        if ($userId === 0) {
            throw new HttpException(401'Категорически требуются права авторизованного пользователя');
        }

        $link = $this->referralService->generateReferralLink(
            referrerId: $userId,
            type: (string) $validated['type'],
            correlationId: (string) $validated['correlation_id']
        );

        return new JsonResponse([
            'success' => true,
            'referral_link' => $link,
            'correlation_id' => $validated['correlation_id'],
        ]);
    }

    /**
     * Исключительно быстро запрашивает актуальную кэшированную статистику реферера.
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = (int) $request->user()?->id;
        $correlationId = $request->input('correlation_id') ?? Str::uuid()->toString();

        if ($userId === 0) {
            throw new HttpException(401'Категорически требуются права авторизованного пользователя для просмотра статистики');
        }

        $statsDto = $this->referralService->getReferralStats($userId, $correlationId);

        return new JsonResponse([
            'success' => true,
            'data' => $statsDto->toArray(),
            'correlation_id' => $correlationId,
        ]);
    }
}
