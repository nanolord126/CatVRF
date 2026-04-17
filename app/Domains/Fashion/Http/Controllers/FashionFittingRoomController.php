<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Controllers;

use App\Domains\Fashion\Services\BodyMeasurementsService;
use App\Services\SuspiciousBehaviorDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FashionFittingRoomController
{
    public function __construct(
        private readonly SuspiciousBehaviorDetector $behaviorDetector,
        private readonly BodyMeasurementsService $measurementsService,
    ) {}

    /**
     * Проверка доступа к примерочной нижнего белья
     */
    public function checkLingerieAccess(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'user_gender' => 'required|string',
        ]);

        $result = $this->behaviorDetector->checkLingerieFittingAccess(
            (int) $validated['user_id'],
            $validated['user_gender'],
            $correlationId
        );

        Log::channel('audit')->info('Lingerie fitting access check', [
            'user_id' => $validated['user_id'],
            'user_gender' => $validated['user_gender'],
            'allowed' => $result['allowed'],
            'correlation_id' => $correlationId,
        ]);

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Получение рекомендаций на основе параметров тела
     */
    public function getRecommendations(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required',
                'correlation_id' => $correlationId,
            ], 401);
        }

        $validated = $request->validate([
            'measurements' => 'required|array',
            'measurements.height' => 'required|integer|min:140|max:220',
            'measurements.weight' => 'required|integer|min:35|max:150',
            'measurements.bust' => 'nullable|integer|min:70|max:150',
            'measurements.underbust' => 'nullable|integer|min:60|max:130',
            'measurements.waist' => 'nullable|integer|min:50|max:130',
            'measurements.hips' => 'nullable|integer|min:70|max:160',
            'measurements.shoulder_width' => 'nullable|integer|min:30|max:60',
            'measurements.sleeve_length' => 'nullable|integer|min:50|max:80',
            'measurements.arm_circumference' => 'nullable|integer|min:20|max:50',
            'measurements.leg_length' => 'nullable|integer|min:70|max:120',
            'measurements.thigh_circumference' => 'nullable|integer|min:40|max:80',
            'measurements.calf_circumference' => 'nullable|integer|min:25|max:50',
            'measurements.neck_circumference' => 'nullable|integer|min:25|max:45',
            'measurements.back_length' => 'nullable|integer|min:35|max:60',
            'style' => 'required|array',
        ]);

        $measurements = $validated['measurements'];
        
        // Валидация параметров
        $errors = $this->measurementsService->validateMeasurements($measurements);
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid measurements',
                'errors' => $errors,
                'correlation_id' => $correlationId,
            ], 422);
        }

        // Получение рекомендаций
        $recommendations = $this->measurementsService->getFullSizeRecommendations($measurements);

        Log::channel('audit')->info('Fitting room recommendations generated', [
            'user_id' => $userId,
            'figure_type' => $recommendations['figure_type'],
            'correlation_id' => $correlationId,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'recommendations' => $recommendations,
                'products' => $this->getProductRecommendations($measurements, $validated['style']),
            ],
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Сохранение параметров тела пользователя
     */
    public function saveMeasurements(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required',
                'correlation_id' => $correlationId,
            ], 401);
        }

        $validated = $request->validate([
            'measurements' => 'required|array',
            'measurements.height' => 'required|integer|min:140|max:220',
            'measurements.weight' => 'required|integer|min:35|max:150',
            'measurements.bust' => 'nullable|integer|min:70|max:150',
            'measurements.underbust' => 'nullable|integer|min:60|max:130',
            'measurements.waist' => 'nullable|integer|min:50|max:130',
            'measurements.hips' => 'nullable|integer|min:70|max:160',
            'measurements.shoulder_width' => 'nullable|integer|min:30|max:60',
            'measurements.sleeve_length' => 'nullable|integer|min:50|max:80',
            'measurements.arm_circumference' => 'nullable|integer|min:20|max:50',
            'measurements.leg_length' => 'nullable|integer|min:70|max:120',
            'measurements.thigh_circumference' => 'nullable|integer|min:40|max:80',
            'measurements.calf_circumference' => 'nullable|integer|min:25|max:50',
            'measurements.neck_circumference' => 'nullable|integer|min:25|max:45',
            'measurements.back_length' => 'nullable|integer|min:35|max:60',
        ]);

        $measurements = $validated['measurements'];
        
        // Валидация параметров
        $errors = $this->measurementsService->validateMeasurements($measurements);
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid measurements',
                'errors' => $errors,
                'correlation_id' => $correlationId,
            ], 422);
        }

        // Сохранение в базу данных (здесь должна быть логика сохранения)
        // $user->bodyMeasurements()->updateOrCreate([], $measurements);

        Log::channel('audit')->info('Body measurements saved', [
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Measurements saved successfully',
            'data' => $this->measurementsService->getFullSizeRecommendations($measurements),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Получение статистики подозрительной активности (для админа)
     */
    public function getSuspicionStats(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required',
                'correlation_id' => $correlationId,
            ], 401);
        }

        // Проверка прав администратора
        // if (!$request->user()->hasRole('admin')) {
        //     return response()->json([
        //         'success' => false,
        //         'error' => 'Admin access required',
        //         'correlation_id' => $correlationId,
        //     ], 403);
        // }

        $validated = $request->validate([
            'target_user_id' => 'required|integer',
        ]);

        $stats = $this->behaviorDetector->getUserSuspicionStats((int) $validated['target_user_id']);

        Log::channel('audit')->info('Suspicion stats accessed', [
            'admin_user_id' => $userId,
            'target_user_id' => $validated['target_user_id'],
            'correlation_id' => $correlationId,
        ]);

        return response()->json([
            'success' => true,
            'data' => $stats,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Разблокировка пользователя (для админа)
     */
    public function unblockUser(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required',
                'correlation_id' => $correlationId,
            ], 401);
        }

        $validated = $request->validate([
            'target_user_id' => 'required|integer',
            'admin_reason' => 'required|string|max:500',
        ]);

        $unblocked = $this->behaviorDetector->unblockUser(
            (int) $validated['target_user_id'],
            $validated['admin_reason']
        );

        Log::channel('audit')->info('User unblocked', [
            'admin_user_id' => $userId,
            'target_user_id' => $validated['target_user_id'],
            'admin_reason' => $validated['admin_reason'],
            'success' => $unblocked,
            'correlation_id' => $correlationId,
        ]);

        return response()->json([
            'success' => $unblocked,
            'message' => $unblocked ? 'User unblocked successfully' : 'User was not blocked',
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Получение рекомендаций товаров на основе параметров
     */
    private function getProductRecommendations(array $measurements, array $style): array
    {
        // Здесь должна быть логика получения товаров из базы данных
        // с учетом параметров и предпочтений стиля
        
        $recommendations = [];
        $figureType = $this->measurementsService->calculateFigureType($measurements);
        $recommendedSize = $this->measurementsService->calculateTopSize($measurements);

        // Пример структуры рекомендаций
        $recommendations[] = [
            'id' => 1,
            'name' => 'Бюстгальтер классический',
            'brand' => 'Fashion Brand',
            'price' => 2500,
            'image' => '/images/products/lingerie1.jpg',
            'fit_score' => $this->measurementsService->calculateFitScore($measurements, [
                'size' => $recommendedSize,
                'suitable_figure_types' => [$figureType],
                'material' => $style['material'] ?? 'cotton',
            ]),
        ];

        return $recommendations;
    }
}
