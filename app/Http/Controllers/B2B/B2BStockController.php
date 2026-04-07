<?php declare(strict_types=1);

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Illuminate\Support\Str;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * B2BStockController — актуальные остатки по складам для B2B-клиента.
 */
final class B2BStockController extends Controller
{
    public function __construct(
        private InventoryService $inventory,
        private readonly DatabaseManager $db,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * GET /api/b2b/v1/stock
     * Остатки всех товаров по складам tenant'а.
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $tenantId      = $request->input('b2b_tenant_id');
        $warehouseId   = $request->query('warehouse_id');

        $query = $this->db->table('inventories as inv')
            ->join('products as p', 'p.id', '=', 'inv.product_id')
            ->join('warehouses as w', 'w.id', '=', 'inv.warehouse_id')
            ->where('w.tenant_id', $tenantId)
            ->select([
                'w.id as warehouse_id',
                'w.name as warehouse_name',
                'p.id as product_id',
                'p.name as product_name',
                'p.sku',
                'inv.quantity',
                'inv.reserved',
                $this->db->raw('(inv.quantity - inv.reserved) as available'),
                'p.wholesale_price_kopecks',
                'p.price_kopecks',
                'p.moq',
            ]);

        if ($warehouseId) {
            $query->where('inv.warehouse_id', (int) $warehouseId);
        }

        $rows = $query->orderBy('w.id')->orderBy('p.name')->paginate(100);

        $items = collect($rows->items())->map(fn(object $r): array => [
            'warehouse_id'            => $r->warehouse_id,
            'warehouse_name'          => $r->warehouse_name,
            'product_id'              => $r->product_id,
            'product_name'            => $r->product_name,
            'sku'                     => $r->sku,
            'quantity'                => $r->quantity,
            'reserved'                => $r->reserved,
            'available'               => max(0, $r->available),
            'wholesale_price_kopecks' => $r->wholesale_price_kopecks
                ?? (int) round($r->price_kopecks * 0.80),
            'moq'                     => $r->moq ?? 1,
            'in_stock'                => $r->available > 0,
        ]);

        return $this->response->json([
            'success'        => true,
            'data'           => $items,
            'meta'           => [
                'total'    => $rows->total(),
                'per_page' => $rows->perPage(),
                'page'     => $rows->currentPage(),
            ],
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * GET /api/b2b/v1/stock/{productId}
     * Остатки конкретного товара по всем складам.
     */
    public function show(Request $request, int $productId): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $tenantId      = $request->input('b2b_tenant_id');

        $rows = $this->db->table('inventories as inv')
            ->join('warehouses as w', 'w.id', '=', 'inv.warehouse_id')
            ->where('w.tenant_id', $tenantId)
            ->where('inv.product_id', $productId)
            ->select([
                'w.id as warehouse_id',
                'w.name as warehouse_name',
                'inv.quantity',
                'inv.reserved',
                $this->db->raw('(inv.quantity - inv.reserved) as available'),
            ])
            ->get();

        if ($rows->isEmpty()) {
            return $this->response->json(['success' => false, 'message' => 'Товар не найден.'], 404);
        }

        return $this->response->json([
            'success'        => true,
            'data'           => $rows->map(fn(object $r): array => [
                'warehouse_id'   => $r->warehouse_id,
                'warehouse_name' => $r->warehouse_name,
                'quantity'       => $r->quantity,
                'reserved'       => $r->reserved,
                'available'      => max(0, $r->available),
            ]),
            'total_available'=> (int) $rows->sum(fn(object $r) => max(0, $r->available)),
            'correlation_id' => $correlationId,
        ]);
    }
}
