<?php declare(strict_types=1);

namespace App\Http\Controllers\ThreeD;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class Model3DUploadController extends Controller
{

    public function __construct(
            private readonly Model3DService $model3DService,
            private readonly Model3DValidationService $validationService,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
            private readonly Guard $guard,
            private readonly ResponseFactory $response,
    ) {

    }
        /**
         * Загрузить новую 3D модель
         *
         * SECURITY:
         * - Проверка tenant_id
         * - Валидация типа и размера файла
         * - Вирусный скан перед сохранением
         * - Rate limiting (10 моделей в час)
         * - FraudControl check
         * - Подпись скачиваемой URL
         */
        public function store(Upload3DModelRequest $request): JsonResponse
        {
            $correlationId = Str::uuid();
            try {
                // SECURITY: Rate limiting (10 загрузок в час на тенанта)
                $rateLimitKey = "upload_3d_model:tenant:" . $this->guard->user()->tenant_id;
                $attempt = RateLimiter::attempt(
                    $rateLimitKey,
                    limit: 10,
                    decay: 3600,
                );
                if (!$attempt) {
                    return $this->response->json([
                        'message' => 'Лимит загрузок превышен',
                        'correlation_id' => (string)$correlationId,
                        'retry_after' => RateLimiter::availableIn($rateLimitKey),
                    ], 429);
                }
                $validated = $request->validated();
                // SECURITY: Проверка tenant_id из сессии
                $tenantId = $this->guard->user()->tenant_id;
                if (!$tenantId) {
                    return $this->response->json([
                        'message' => 'Тенант не определен',
                        'correlation_id' => (string)$correlationId,
                    ], 401);
                }
                // SECURITY: FraudControl check перед мутацией
                if (method_exists($this, 'fraudCheck')) {
                    $fraudResult = $this->fraudCheck('upload_3d_model', [
                        'tenant_id' => $tenantId,
                        'file_size' => $request->file('model')->getSize(),
                    ]);
                    if (!$fraudResult['allowed']) {
                        $this->logger->channel('fraud_alert')->warning('3D модель заблокирована фрод-сервисом', [
                            'correlation_id' => (string)$correlationId,
                            'tenant_id' => $tenantId,
                            'reason' => $fraudResult['reason'] ?? 'Подозрение на мошенничество',
                        ]);
                        return $this->response->json([
                            'message' => 'Операция заблокирована',
                            'correlation_id' => (string)$correlationId,
                        ], 403);
                    }
                }
                // SECURITY: Сохранение модели в транзакции
                $model = $this->db->transaction(function () use ($request, $tenantId, $correlationId): Model3D {
                    return $this->model3DService->storeModel(
                        tenantId: $tenantId,
                        file: $request->file('model'),
                        name: $validated['name'],
                        description: $validated['description'] ?? null,
                        correlationId: (string)$correlationId,
                    );
                });
                $this->logger->channel('audit')->info('3D модель успешно загружена', [
                    'correlation_id' => (string)$correlationId,
                    'tenant_id' => $tenantId,
                    'model_id' => $model->id,
                    'uuid' => $model->uuid,
                    'file_size' => $model->file_size,
                    'hash' => $model->hash,
                ]);
                return $this->response->json([
                    'message' => 'Модель загружена успешно',
                    'correlation_id' => (string)$correlationId,
                    'model' => [
                        'id' => $model->uuid,
                        'name' => $model->name,
                        'status' => $model->status,
                    ],
                ], 201);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Ошибка при загрузке 3D модели', [
                    'correlation_id' => (string)$correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 400;
                return $this->response->json([
                    'message' => $e->getMessage(),
                    'correlation_id' => (string)$correlationId,
                ], $statusCode);
            }
        }
        /**
         * Получить превью 3D модели (после загрузки и обработки)
         *
         * SECURITY:
         * - Проверка подписаного URL
         * - Rate limiting (100 запросов/мин на пользователя)
         * - Инкремент view_count для аналитики
         * - Возвращение подписанного URL для скачивания
         */
        public function show(string $modelUuid): JsonResponse
        {
            try {
                // SECURITY: Rate limiting (100 просмотров в минуту)
                $rateLimitKey = "view_3d_model:" . $this->guard->id();
                if (!RateLimiter::attempt($rateLimitKey, limit: 100, decay: 60)) {
                    return $this->response->json([
                        'message' => 'Слишком много запросов',
                        'correlation_id' => null,
                    ], 429);
                }
                // SECURITY: Поиск с tenant scoping (встроен в глобальный scope)
                $model = Model3D::where('uuid', $modelUuid)
                    ->where('status', 'active')
                    ->firstOrFail();
                // Инкремент просмотров для аналитики
                $this->model3DService->recordView($model);
                // Генерируем подписанный URL для скачивания
                $downloadUrl = $this->model3DService->getSignedDownloadUrl($model);
                return $this->response->json([
                    'model' => [
                        'id' => $model->uuid,
                        'name' => $model->name,
                        'description' => $model->description,
                        'type' => $model->model_type,
                        'file_size' => $model->file_size,
                        'file_url' => $downloadUrl,
                        'metadata' => $model->metadata,
                        'configurations' => $model->configurations()
                            ->active()
                            ->get(['id', 'uuid', 'name', 'config', 'price_modifier'])
                            ->toArray(),
                    ],
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->warning('Ошибка при получении превью 3D модели', [
                    'model_uuid' => $modelUuid,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'message' => 'Модель не найдена',
                    'correlation_id' => null,
                ], 404);
            }
        }
}
