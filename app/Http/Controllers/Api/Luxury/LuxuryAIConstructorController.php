<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\Luxury;
use App\Http\Controllers\Controller;
use App\Domains\Luxury\DTO\LuxuryAIAnalysisRequestDTO;
use App\Services\AI\LuxuryAIConstructorService;
use App\Services\FraudControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
/**
 * LuxuryAIConstructorController
 *
 * Layer 9: API Entry Point
 * Предоставляет эндпоинты для VIP-клиентов для использования AI-консультанта.
 *
 * @version 1.0.0
 * @author CatVRF
 */
final class LuxuryAIConstructorController extends Controller
{
    /**
     * POST /api/v1/luxury/ai-curate
     *
     * Генерирует элитную подборку на основе предпочтений клиента.
     */
    public function curate(Request $request): \Illuminate\Http\JsonResponse
    {
        $correlationId = (string) Str::uuid();
        // 1. Валидация входных данных (Канон: FormRequest или validate())
        $validated = $request->validate([
            'client_uuid' => 'required|uuid|exists:luxury_clients,uuid',
            'analysis_type' => 'required|string|in:style_match,investment_watch,gift_curate',
            'context_data' => 'nullable|array',
        ]);
        try {
            // 2. Fraud Check (Канон: Обязательно перед мутациями или тяжелыми операциями)
            FraudControlService::check([
                'user_id' => $request->user()?->id,
                'type' => 'luxury_ai_generation',
                'correlation_id' => $correlationId,
            ]);
            // 3. Создание DTO (Layer 5)
            $dto = new LuxuryAIAnalysisRequestDTO(
                clientUuid: $validated['client_uuid'],
                analysisType: $validated['analysis_type'],
                contextData: $validated['context_data'] ?? []
            );
            // 4. Оркестрация через сервис (Layer 2)
            $service = new LuxuryAIConstructorService(
                recommendationService: app(\App\Services\RecommendationService::class),
                inventoryService: app(\App\Services\InventoryManagementService::class),
                correlationId: $correlationId
            );
            $result = $service->generateCuration($dto);
            Log::channel('audit')->info('Luxury API: Successful curation generation', [
                'client' => $validated['client_uuid'],
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'success' => true,
                'data' => $result,
                'meta' => [
                    'correlation_id' => $correlationId,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->error('Luxury API Error', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Произошла ошибка при работе с AI-консультантом. Пожалуйста, обратитесь к вашему VIP-консьержу.',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
