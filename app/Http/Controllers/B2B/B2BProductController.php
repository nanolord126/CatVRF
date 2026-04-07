<?php declare(strict_types=1);

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * B2BProductController — просмотр каталога товаров по оптовым ценам.
 * Только чтение; создание/изменение товаров — через Tenant Panel.
 */
final class B2BProductController extends Controller
{
    public function __construct(
        private InventoryService $inventory,
        private readonly DatabaseManager $db,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * GET /api/b2b/v1/products
     * Список товаров с оптовыми ценами и остатками.
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString());

        $tenantId = $request->input('b2b_tenant_id');

        $products = $this->db->table('products')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->select([
                'id', 'uuid', 'name', 'description', 'category',
                'price_kopecks', 'wholesale_price_kopecks',
                'moq', 'unit', 'sku',
            ])
            ->paginate(50);

        // Добавляем актуальные остатки
        $items = collect($products->items())->map(function (object $product) use ($tenantId): array {
            $stock = $this->inventory->getAvailableStock((int) $product->id);
            return [
                'id'                      => $product->id,
                'uuid'                    => $product->uuid,
                'name'                    => $product->name,
                'category'                => $product->category,
                'sku'                     => $product->sku,
                'retail_price_kopecks'    => $product->price_kopecks,
                'wholesale_price_kopecks' => $product->wholesale_price_kopecks
                    ?? (int) round($product->price_kopecks * 0.80),
                'moq'                     => $product->moq ?? 1,
                'unit'                    => $product->unit,
                'available_stock'         => $stock,
                'in_stock'                => $stock > 0,
            ];
        });

        return $this->response->json([
            'success'        => true,
            'data'           => $items,
            'meta'           => [
                'total'    => $products->total(),
                'per_page' => $products->perPage(),
                'page'     => $products->currentPage(),
            ],
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * GET /api/b2b/v1/products/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString());
        $tenantId      = $request->input('b2b_tenant_id');

        $product = $this->db->table('products')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return $this->response->json(['success' => false, 'message' => 'Товар не найден.'], 404);
        }

        $stock = $this->inventory->getAvailableStock($id);

        return $this->response->json([
            'success'        => true,
            'data'           => [
                'id'                      => $product->id,
                'uuid'                    => $product->uuid,
                'name'                    => $product->name,
                'description'             => $product->description,
                'category'                => $product->category,
                'sku'                     => $product->sku,
                'retail_price_kopecks'    => $product->price_kopecks,
                'wholesale_price_kopecks' => $product->wholesale_price_kopecks
                    ?? (int) round($product->price_kopecks * 0.80),
                'moq'                     => $product->moq ?? 1,
                'unit'                    => $product->unit,
                'available_stock'         => $stock,
                'in_stock'                => $stock > 0,
            ],
            'correlation_id' => $correlationId,
        ]);
    }
}
