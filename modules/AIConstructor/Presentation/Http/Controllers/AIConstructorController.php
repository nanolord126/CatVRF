<?php

declare(strict_types=1);

namespace Modules\AIConstructor\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\AIConstructor\Application\Services\AIConstructorService;
use Modules\AIConstructor\Presentation\Http\Requests\GenerateAIRequest;

/**
 * Атомарный HTTP контроллер Presentation слоя для инициирования процессов AI-конструирования.
 *
 * Категорически гарантирует прием безопасного физического файла, делегирование ресурсоемкого
 * процесса в Application Service и возврат строго типизированного успешного ответа (DTO-to-Array).
 */
final class AIConstructorController extends Controller
{
    /**
     * Инициализирует контроллер с обязательным внедрением тяжелого сервиса-оркестратора.
     *
     * @param AIConstructorService $aiConstructorService Защищенный внедряемый сервис генерации.
     */
    public function __construct(
        private readonly AIConstructorService $aiConstructorService
    ) {
    }

    /**
     * Обрабатывает POST-запрос на анализ фотографии и синтез бизнес-ответа от LLM.
     *
     * @param GenerateAIRequest $request Строго валидированный FormRequest-пакет с файлом и параметрами.
     * @return JsonResponse Абсолютно стандартизированный ответ HTTP с полезной нагрузкой нейросети.
     */
    public function generate(GenerateAIRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $userId = (int) $request->user()?->id;
        if ($userId === 0) {
            abort(401, 'Необходимо категорически авторизоваться для запуска AI-конструктора.');
        }

        /** @var \Illuminate\Http\UploadedFile $photo */
        $photo = $validated['photo'];

        // Исключительно синхронный вызов, возвращающий консистентный DTO.
        // В реальном high-load сценарии этот код выносится в Job, а клиенту отдается JobID для Long-polling или WebSockets.
        $resultDto = $this->aiConstructorService->generateFromPhoto(
            photo: $photo,
            vertical: (string) $validated['vertical'],
            type: (string) $validated['type'],
            tenantId: (int) $validated['tenant_id'],
            userId: $userId,
            correlationId: (string) $validated['correlation_id']
        );

        return response()->json([
            'success' => true,
            'vertical' => $resultDto->vertical,
            'type' => $resultDto->type,
            'payload' => $resultDto->payload,
            'suggestions' => $resultDto->suggestions,
            'confidence_score' => $resultDto->confidence_score,
            'correlation_id' => $resultDto->correlation_id,
        ]);
    }
}
