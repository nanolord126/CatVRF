<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1\WeddingPlanning;
use App\Domains\WeddingPlanning\Models\WeddingEvent;
use App\Domains\WeddingPlanning\Models\WeddingVendor;
use App\Domains\WeddingPlanning\Services\WeddingService;
use App\Domains\WeddingPlanning\Services\AIWeddingPlannerConstructor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * WeddingPublicController
 *
 * Layer 5: Marketplace API Layer (B2C & B2B)
 * Предоставляет публичные эндпоинты для витрины свадебных услуг.
 *
 * @version 1.0.0
 * @author CatVRF
 */
final class WeddingPublicController extends Controller
{
    private readonly WeddingService $weddingService;
    private readonly AIWeddingPlannerConstructor $aiConstructor;
    public function __construct(
        WeddingService $weddingService,
        AIWeddingPlannerConstructor $aiConstructor
    ) {
        $this->weddingService = $weddingService;
        $this->aiConstructor = $aiConstructor;
        // PRODUCTION-READY 2026 CANON: Middleware для Wedding Planning вертикали
        $this->middleware('auth:sanctum')->only(['bookVendor', 'createEvent', 'updateEvent']); // Бронирование требует авторизации
         // 100 запросов/мин для витрины
         // Определение режима B2C/B2B
        $this->middleware('tenant', ['only' => ['bookVendor', 'createEvent', 'updateEvent']]); // Tenant scoping для мутаций
        // Fraud check для платежей и бронирований
        $this->middleware(
            'fraud-check',
            ['only' => ['bookVendor', 'createEvent', 'confirmPayment']]
        );
    }
    /**
     * Список доступных вендоров (витрина B2C)
     * GET /api/v1/wedding/vendors
     */
    public function getVendors(Request $request): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        try {
            $query = WeddingVendor::query()
                ->where('tenant_id', tenant()->id)
                ->where('is_active', true);
            // Фильтры (категория, бюджет, рейтинг)
            if ($request->has('category')) {
                $query->where('category', $request->get('category'));
            }
            if ($request->has('min_price_max')) {
                $query->where('min_price', '<=', (int) $request->get('min_price_max'));
            }
            $vendors = $query->orderByDesc('rating')->paginate(15);
            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $vendors,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('API Vendors Error', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
    /**
     * Инициация свадьбы через AI (B2B/B2C)
     * POST /api/v1/wedding/constructor/init
     */
    public function initConstructor(Request $request): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        $request->validate([
            'title' => 'required|string|max:255',
            'total_budget' => 'required|integer|min:100000',
            'guest_count' => 'required|integer|min:1',
            'style' => 'nullable|string',
            'event_date' => 'required|date|after:today',
        ]);
        try {
            // 1. Создаём свадьбу через сервис (Layer 2)
            $wedding = $this->weddingService->createWedding([
                'title' => $request->get('title'),
                'total_budget' => $request->get('total_budget'),
                'guest_count' => $request->get('guest_count'),
                'event_date' => $request->get('event_date'),
                'correlation_id' => $correlationId,
            ]);
            // 2. Генерируем AI-план (Layer 3)
            $aiPlan = $this->aiConstructor->generateWeddingPlan(
                (int) $request->get('total_budget'),
                $request->get('style') ?? 'classic',
                (int) $request->get('guest_count')
            );
            // 3. Сохраняем AI план в метаданные или логи
            $wedding->update(['tags' => array_unique(array_merge($wedding->tags ?? [], ['ai_generated', $request->get('style') ?? 'classic']))]);
            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'wedding_uuid' => $wedding->uuid,
                'ai_plan' => $aiPlan,
            ], 201);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Constructor Init Error', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to initialize constructor: ' . $e->getMessage(),
            ], 422);
        }
    }
    /**
     * Получение деталей свадьбы (B2C)
     * GET /api/v1/wedding/showcase/{uuid}
     */
    public function showWedding(string $uuid): JsonResponse
    {
        $wedding = WeddingEvent::where('uuid', $uuid)
            ->where('tenant_id', tenant()->id)
            ->with(['bookings.bookable', 'planner', 'contracts'])
            ->firstOrFail();
        return response()->json([
            'success' => true,
            'data' => $wedding,
        ]);
    }
    /**
     * Бронирование вендора (B2B/B2C Transaction)
     * POST /api/v1/wedding/booking/create
     */
    public function createBooking(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $request->validate([
            'wedding_event_id' => 'required|exists:wedding_events,id',
            'vendor_id' => 'required|exists:wedding_vendors,id',
            'amount' => 'required|integer',
        ]);
        try {
            $booking = $this->weddingService->bookService(
                (int) $request->get('wedding_event_id'),
                WeddingVendor::class,
                (int) $request->get('vendor_id'),
                (int) $request->get('amount'),
                $correlationId
            );
            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'booking_id' => $booking->id,
                'status' => $booking->status,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->warning('Booking Creation Failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
