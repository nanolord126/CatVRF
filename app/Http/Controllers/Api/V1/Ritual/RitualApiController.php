<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1\Ritual;
use App\Http\Controllers\Controller;
use App\Domains\RitualServices\Ritual\Models\FuneralOrder;
use App\Domains\RitualServices\Ritual\Services\RitualCoreService;
use App\Http\Requests\Api\Ritual\CreateFuneralOrderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * RitualApiController — Production Ready 2026
 * 
 * Контроллер для работы с ритуальными заказами через API.
 * Реализовано по доменному канону 2026: Try/Catch, Audit Log, Correlation ID.
 */
final class RitualApiController extends Controller
{
    /**
     * Конструктор с инъекцией сервиса (DI).
     */
    public function __construct(
        private RitualCoreService $ritualService,
    ) {}
    /**
     * Список заказов текущего пользователя (Tenant + User Scoping).
     */
    public function index(): JsonResponse
    {
        $correlation_id = (string) Str::uuid();
        try {
            $orders = FuneralOrder::where('client_id', auth()->id())
                ->latest()
                ->paginate(20);
            Log::channel('audit')->info('Ritual orders list requested', [
                'user_id' => auth()->id(),
                'correlation_id' => $correlation_id,
            ]);
            return response()->json([
                'success' => true,
                'data' => $orders,
                'correlation_id' => $correlation_id,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to list ritual orders', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlation_id,
            ]);
            return response()->json([
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
            $data['client_id'] = auth()->id();
            $data['correlation_id'] ??= $correlation_id;
            // Вызов сервиса (DB::transaction внутри)
            $order = $this->ritualService->createFuneralOrder($data, $correlation_id);
            Log::channel('audit')->info('Ritual order created via API', [
                'order_uuid' => $order->uuid,
                'client_id' => $order->client_id,
                'correlation_id' => $correlation_id,
            ]);
            return response()->json([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlation_id,
            ], 201);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to create ritual order via API', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'correlation_id' => $correlation_id,
            ]);
            return response()->json([
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
                ->where('client_id', auth()->id())
                ->firstOrFail();
            Log::channel('audit')->info('Ritual order detail viewed', [
                'order_uuid' => $uuid,
                'correlation_id' => $correlation_id,
            ]);
            return response()->json([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlation_id,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Заказ не найден или доступ запрещен',
                'correlation_id' => $correlation_id,
            ], 404);
        }
    }
}
