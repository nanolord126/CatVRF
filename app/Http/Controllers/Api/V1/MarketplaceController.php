<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class MarketplaceController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function products(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $query = $this->db->table('products')->where('is_active', true);

        if ($request->has('vertical')) {
            $query->where('vertical', $request->get('vertical'));
        }
        if ($request->has('category')) {
            $query->where('category', $request->get('category'));
        }
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->get('search') . '%');
        }

        $products = $query->orderByDesc('created_at')->paginate(20);

        $this->logger->info('Marketplace products listed', ['correlation_id' => $correlationId, 'count' => $products->total()]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => $products->items(),
            'meta' => ['current_page' => $products->currentPage(), 'last_page' => $products->lastPage(), 'total' => $products->total()],
        ]);
    }

    public function categories(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $categories = $this->db->table('categories')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $this->logger->info('Marketplace categories listed', ['correlation_id' => $correlationId, 'count' => $categories->count()]);

        return new JsonResponse(['correlation_id' => $correlationId, 'data' => $categories]);
    }
}
