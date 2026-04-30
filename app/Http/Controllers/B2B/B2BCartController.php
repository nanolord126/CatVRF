<?php

declare(strict_types=1);

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Models\BusinessGroup;
use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\Str;

/**
 * B2BCartController — Управление корзиной B2B-заказов.
 *
 * Все методы требуют X-B2B-API-Key (через B2BApiMiddleware).
 * business_group доступен через $request->attributes->get('b2b_business_group').
 */
final class B2BCartController extends Controller
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly ResponseFactory $response,
        private readonly AuditService $audit,
    ) {}

    /** Получить содержимое корзины. */
    public function index(Request $request): JsonResponse
    {
        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $cartItems = $this->db->table('b2b_cart_items')
            ->where('business_group_id', $group->id)
            ->get();

        $total = $cartItems->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        return $this->response->json([
            'success' => true,
            'data' => [
                'items' => $cartItems,
                'total_items' => $cartItems->count(),
                'total_quantity' => $cartItems->sum('quantity'),
                'total_amount' => $total,
            ],
        ]);
    }

    /** Добавить товар в корзину. */
    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1'],
            'warehouse_id' => ['sometimes', 'integer', 'min:1'],
        ]);

        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        // Проверяем наличие товара
        $product = $this->db->table('products')
            ->where('id', $validated['product_id'])
            ->first();

        if (!$product) {
            return $this->response->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        // Получаем оптовую цену
        $tier = $group->b2b_tier ?? 'standard';
        $priceMultiplier = match ($tier) {
            'silver' => 0.90,
            'gold' => 0.85,
            'platinum' => 0.80,
            default => 0.95,
        };

        $unitPrice = (int) round($product->price_kopecks * $priceMultiplier);

        // Проверяем, есть ли товар уже в корзине
        $existing = $this->db->table('b2b_cart_items')
            ->where('business_group_id', $group->id)
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($existing) {
            $this->db->table('b2b_cart_items')
                ->where('id', $existing->id)
                ->increment('quantity', $validated['quantity']);
        } else {
            $this->db->table('b2b_cart_items')->insert([
                'business_group_id' => $group->id,
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'unit_price' => $unitPrice,
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->logAction('b2b_cart_item_added', [
            'business_group_id' => $group->id,
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'correlation_id' => $correlationId,
        ]);

        return $this->response->json([
            'success' => true,
            'message' => 'Item added to cart',
            'correlation_id' => $correlationId,
        ], 201);
    }

    /** Обновить количество товара в корзине. */
    public function updateItem(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        $updated = $this->db->table('b2b_cart_items')
            ->where('id', $id)
            ->where('business_group_id', $group->id)
            ->update([
                'quantity' => $validated['quantity'],
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return $this->response->json(['success' => false, 'message' => 'Cart item not found'], 404);
        }

        $this->logAction('b2b_cart_item_updated', [
            'business_group_id' => $group->id,
            'cart_item_id' => $id,
            'quantity' => $validated['quantity'],
            'correlation_id' => $correlationId,
        ]);

        return $this->response->json([
            'success' => true,
            'correlation_id' => $correlationId,
        ]);
    }

    /** Удалить товар из корзины. */
    public function removeItem(Request $request, int $id): JsonResponse
    {
        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        $deleted = $this->db->table('b2b_cart_items')
            ->where('id', $id)
            ->where('business_group_id', $group->id)
            ->delete();

        if (!$deleted) {
            return $this->response->json(['success' => false, 'message' => 'Cart item not found'], 404);
        }

        $this->logAction('b2b_cart_item_removed', [
            'business_group_id' => $group->id,
            'cart_item_id' => $id,
            'correlation_id' => $correlationId,
        ]);

        return $this->response->json([
            'success' => true,
            'correlation_id' => $correlationId,
        ]);
    }

    /** Очистить корзину. */
    public function clear(Request $request): JsonResponse
    {
        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        $this->db->table('b2b_cart_items')
            ->where('business_group_id', $group->id)
            ->delete();

        $this->logAction('b2b_cart_cleared', [
            'business_group_id' => $group->id,
            'correlation_id' => $correlationId,
        ]);

        return $this->response->json([
            'success' => true,
            'correlation_id' => $correlationId,
        ]);
    }
}
