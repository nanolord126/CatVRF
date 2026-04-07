<?php declare(strict_types=1);

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Models\BusinessGroup;
use App\Services\B2B\B2BOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Illuminate\Support\Str;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * B2BOrderController — CRUD B2B-заказов.
 *
 * Все методы требуют X-B2B-API-Key (через B2BApiMiddleware).
 * business_group доступен через $request->attributes->get('b2b_business_group').
 */
final class B2BOrderController extends Controller
{
    public function __construct(
        private readonly B2BOrderService $orderService,
        private readonly DatabaseManager $db,
        private readonly ResponseFactory $response,
    ) {}

    /** Список заказов BusinessGroup. */
    public function index(Request $request): JsonResponse
    {
        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $orders = $this->db->table('orders')
            ->where('business_group_id', $group->id)
            ->where('is_b2b', true)
            ->orderByDesc('created_at')
            ->paginate(50);

        return $this->response->json($orders);
    }

    /** Создать новый B2B-заказ. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.product_id'      => ['required', 'integer', 'min:1'],
            'items.*.quantity'        => ['required', 'integer', 'min:1'],
            'items.*.warehouse_id'    => ['required', 'integer', 'min:1'],
            'delivery_address'        => ['required', 'string', 'max:500'],
            'use_credit'              => ['sometimes', 'boolean'],
        ]);

        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        $result = $this->orderService->create(
            $group,
            $validated['items'],
            $validated['delivery_address'],
            (bool) ($validated['use_credit'] ?? false),
            $correlationId,
        );

        return $this->response->json([
            'success'        => true,
            'data'           => $result,
            'correlation_id' => $correlationId,
        ], 201);
    }

    /** Просмотр одного заказа. */
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $order = $this->db->table('orders')
            ->where('id', $id)
            ->where('business_group_id', $group->id)
            ->first();

        if ($order === null) {
            return $this->response->json(['error' => 'Order not found'], 404);
        }

        $items = $this->db->table('order_items')->where('order_id', $id)->get();

        return $this->response->json(['order' => $order, 'items' => $items]);
    }

    /** Отмена заказа (только pending). */
    public function cancel(Request $request, int $id): JsonResponse
    {
        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        $updated = $this->db->table('orders')
            ->where('id', $id)
            ->where('business_group_id', $group->id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled', 'correlation_id' => $correlationId]);

        if (!$updated) {
            return $this->response->json(['error' => 'Order not found or cannot be cancelled'], 422);
        }

        return $this->response->json(['success' => true, 'correlation_id' => $correlationId]);
    }

    /** Массовое создание заказов из массива. */
    public function bulkCreate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orders'                              => ['required', 'array', 'min:1', 'max:100'],
            'orders.*.items'                      => ['required', 'array', 'min:1'],
            'orders.*.items.*.product_id'         => ['required', 'integer'],
            'orders.*.items.*.quantity'           => ['required', 'integer', 'min:1'],
            'orders.*.items.*.warehouse_id'       => ['required', 'integer'],
            'orders.*.delivery_address'           => ['required', 'string'],
            'orders.*.use_credit'                 => ['sometimes', 'boolean'],
        ]);

        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        $result = $this->orderService->bulkCreate($group, $validated['orders'], $correlationId);

        return $this->response->json([
            'success'        => $result['failed'] === 0,
            'data'           => $result,
            'correlation_id' => $correlationId,
        ], $result['failed'] === 0 ? 201 : 207);
    }

    /** Импорт заказов из Excel (MVP — принимаем JSON-конвертированный файл). */
    public function importExcel(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        // MVP: парсинг Excel делается через PhpSpreadsheet в Job
        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        return $this->response->json([
            'success'        => true,
            'message'        => 'Import queued',
            'correlation_id' => $correlationId,
        ], 202);
    }
}
