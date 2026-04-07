<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Ritual;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class RitualApiController extends Controller
{

    /**
         * Конструктор с инъекцией сервиса (DI).
         */
        public function __construct(
            private RitualCoreService $ritualService,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}
        /**
         * Список заказов текущего пользователя (Tenant + User Scoping).
         */
        public function index(): JsonResponse
        {
            $correlation_id = (string) Str::uuid();
            try {
                $orders = FuneralOrder::where('client_id', $this->guard->id())
                    ->latest()
                    ->paginate(20);
                $this->logger->channel('audit')->info('Ritual orders list requested', [
                    'user_id' => $this->guard->id(),
                    'correlation_id' => $correlation_id,
                ]);
                return $this->response->json([
                    'success' => true,
                    'data' => $orders,
                    'correlation_id' => $correlation_id,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to list ritual orders', [
                    'user_id' => $this->guard->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlation_id,
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => 'Не удалось получить список заказов',
                    'correlation_id' => $correlation_id,
                ], 500);
            }
        }
        /**
         * Создание нового ритуального заказа через API.
         */
        public function store(CreateFuneralOrderRequest $request): JsonResponse
        {
            $correlation_id = (string) Str::uuid();
            try {
                // Валидированные данные (FormRequest)
                $data = $request->validated();
                // Фиксируем ID авторизованного клиента
                $data['client_id'] = $this->guard->id();
                $data['correlation_id'] ??= $correlation_id;
                // Вызов сервиса ($this->db->transaction внутри)
                $order = $this->ritualService->createFuneralOrder($data, $correlation_id);
                $this->logger->channel('audit')->info('Ritual order created via API', [
                    'order_uuid' => $order->uuid,
                    'client_id' => $order->client_id,
                    'correlation_id' => $correlation_id,
                ]);
                return $this->response->json([
                    'success' => true,
                    'data' => $order,
                    'correlation_id' => $correlation_id,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to create ritual order via API', [
                    'user_id' => $this->guard->id(),
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlation_id,
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => 'Ошибка при оформлении заказа: ' . $e->getMessage(),
                    'correlation_id' => $correlation_id,
                ], 400);
            }
        }
        /**
         * Детализация конкретного заказа (Show).
         */
        public function show(string $uuid): JsonResponse
        {
            $correlation_id = (string) Str::uuid();
            try {
                // Поиск с проверкой прав доступа (Tenant Global Scope активен)
                $order = FuneralOrder::where('uuid', $uuid)
                    ->where('client_id', $this->guard->id())
                    ->firstOrFail();
                $this->logger->channel('audit')->info('Ritual order detail viewed', [
                    'order_uuid' => $uuid,
                    'correlation_id' => $correlation_id,
                ]);
                return $this->response->json([
                    'success' => true,
                    'data' => $order,
                    'correlation_id' => $correlation_id,
                ]);
            } catch (\Throwable $e) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Заказ не найден или доступ запрещен',
                    'correlation_id' => $correlation_id,
                ], 404);
            }
        }
}
