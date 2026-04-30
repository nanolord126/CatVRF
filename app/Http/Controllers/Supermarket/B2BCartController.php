<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supermarket;

use App\Domains\Supermarket\Models\B2BCompany;
use App\Domains\Supermarket\Services\B2BService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final readonly class B2BCartController
{
    public function __construct(
        private readonly B2BService $b2bService,
    ) {}

    /**
     * Добавить товар в корзину.
     */
    public function add(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1|max:10000',
            'warehouse_id' => 'nullable|integer',
        ]);

        $company = $request->attributes->get('b2b_company');
        if (!$company) {
            throw new \RuntimeException('B2B company not found in request');
        }

        $product = \App\Domains\Supermarket\Models\Product::findOrFail($validated['product_id']);

        // Проверяем доступность
        if (!$product->is_active) {
            return response()->json([
                'error' => 'Product not available',
                'message' => 'This product is not available for purchase',
            ], 400);
        }

        $wholesalePrice = $this->b2bService->getWholesalePrice(
            product: $product,
            quantity: $validated['quantity'],
            company: $company,
        );

        // Сохраняем в корзину (используем существующую таблицу cart_items)
        DB::table('cart_items')->updateOrInsert(
            [
                'user_id' => $request->user()->id,
                'tenant_id' => $company->user_id,
                'product_id' => $validated['product_id'],
                'reservation_expires_at' => '>',
            ],
            [
                'quantity' => DB::raw('quantity + ' . $validated['quantity']),
                'attributes' => json_encode([
                    'wholesale_price' => $wholesalePrice,
                    'warehouse_id' => $validated['warehouse_id'] ?? 1,
                ]),
                'reservation_expires_at' => now()->addMinutes(20),
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Product added to cart',
            'wholesale_price' => $wholesalePrice,
            'total' => $wholesalePrice * $validated['quantity'],
        ]);
    }

    /**
     * Получить содержимое корзины.
     */
    public function index(Request $request): JsonResponse
    {
        $company = $request->attributes->get('b2b_company');
        if (!$company) {
            throw new \RuntimeException('B2B company not found in request');
        }

        $cartItems = DB::table('cart_items')
            ->where('user_id', $request->user()->id)
            ->where('tenant_id', $company->user_id)
            ->where('reservation_expires_at', '>', now())
            ->get();

        $items = [];
        $totalQuantity = 0;
        $totalAmount = 0;

        foreach ($cartItems as $item) {
            $product = DB::table('products')->where('id', $item->product_id)->first();
            if (!$product) {
                continue;
            }

            $attributes = json_decode($item->attributes ?? '{}', true);
            $wholesalePrice = $attributes['wholesale_price'] ?? $product->price;
            $itemTotal = $wholesalePrice * $item->quantity;

            $items[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $product->name,
                'quantity' => $item->quantity,
                'price' => $wholesalePrice,
                'total' => $itemTotal,
                'warehouse_id' => $attributes['warehouse_id'] ?? 1,
            ];

            $totalQuantity += $item->quantity;
            $totalAmount += $itemTotal;
        }

        return response()->json([
            'items' => $items,
            'total_quantity' => $totalQuantity,
            'total_amount' => $totalAmount,
            'reservation_expires_at' => $cartItems->max('reservation_expires_at'),
        ]);
    }

    /**
     * Удалить товар из корзины.
     */
    public function remove(Request $request, int $itemId): JsonResponse
    {
        $company = $request->attributes->get('b2b_company');
        if (!$company) {
            throw new \RuntimeException('B2B company not found in request');
        }

        $deleted = DB::table('cart_items')
            ->where('id', $itemId)
            ->where('user_id', $request->user()->id)
            ->where('tenant_id', $company->user_id)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'error' => 'Cart item not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Item removed from cart',
        ]);
    }
}
