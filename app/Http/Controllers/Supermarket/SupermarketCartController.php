<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supermarket;

use App\Domains\Supermarket\Services\SupermarketService;
use App\Traits\WithAuditLogging;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final readonly class SupermarketCartController
{
    use WithAuditLogging;

    public function __construct(
        private SupermarketService $supermarketService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = $request->user()?->tenant_id;

        $cart = $this->supermarketService->getCart($userId, $tenantId);

        $this->logAction('cart_viewed', [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'items_count' => count($cart['items'] ?? []),
        ]);

        return response()->json($cart);
    }

    public function add(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'attributes' => 'sometimes|array',
        ]);

        $userId = Auth::id();
        $tenantId = $request->user()?->tenant_id;

        $cart = $this->supermarketService->addToCart(
            $userId,
            $tenantId,
            $validated['product_id'],
            $validated['quantity'],
            $validated['attributes'] ?? []
        );

        $this->logAction('item_added_to_cart', [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
        ]);

        return response()->json($cart);
    }

    public function update(Request $request, string $itemId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $userId = Auth::id();
        $tenantId = $request->user()?->tenant_id;

        $cart = $this->supermarketService->updateCartItem(
            $userId,
            $tenantId,
            $itemId,
            $validated['quantity']
        );

        $this->logAction('cart_item_updated', [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'item_id' => $itemId,
            'quantity' => $validated['quantity'],
        ]);

        return response()->json($cart);
    }

    public function remove(Request $request, string $itemId): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = $request->user()?->tenant_id;

        $cart = $this->supermarketService->removeFromCart(
            $userId,
            $tenantId,
            $itemId
        );

        $this->logAction('item_removed_from_cart', [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'item_id' => $itemId,
        ]);

        return response()->json($cart);
    }

    public function clear(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = $request->user()?->tenant_id;

        $this->supermarketService->clearCart($userId, $tenantId);

        $this->logAction('cart_cleared', [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
        ]);

        return response()->json(['success' => true]);
    }
}
