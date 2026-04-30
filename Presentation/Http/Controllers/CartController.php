<?php

declare(strict_types=1);

namespace Modules\Cart\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Cart\Application\Services\CartService;
use Modules\Cart\Domain\DTOs\AddItemDto;

final class CartController
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function addItem(Request $request): JsonResponse
    {
        try {
            $dto = AddItemDto::fromRequest($request->validated());

            $item = $this->cartService->addItem(
                $dto,
                $request->header('X-Correlation-ID') ?? uniqid(),
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'item_id' => $item->id,
                    'cart_id' => $item->cartId,
                    'product_id' => $item->productId,
                    'quantity' => $item->quantity,
                    'total' => $item->getTotal(),
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    public function refreshPrices(Request $request, int $cartId): JsonResponse
    {
        try {
            $request->validate([
                'prices' => 'required|array',
                'prices.*' => 'integer|min:0',
            ]);

            $this->cartService->refreshPrices(
                $cartId,
                $request->input('prices'),
                $request->header('X-Correlation-ID') ?? uniqid(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Prices refreshed',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    public function removeItem(Request $request, int $cartId, int $productId): JsonResponse
    {
        try {
            $this->cartService->removeItem(
                $cartId,
                $productId,
                $request->header('X-Correlation-ID') ?? uniqid(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Item removed',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    public function clear(Request $request, int $cartId): JsonResponse
    {
        try {
            $request->validate([
                'reason' => 'required|string',
            ]);

            $this->cartService->clear(
                $cartId,
                $request->input('reason'),
                $request->header('X-Correlation-ID') ?? uniqid(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Cart cleared',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    public function getUserCarts(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()?->id ?? (int) $request->input('user_id');
            $carts = $this->cartService->getUserCarts($userId);

            return response()->json([
                'success' => true,
                'data' => $carts,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    public function getTotal(Request $request, int $cartId): JsonResponse
    {
        try {
            $total = $this->cartService->getTotal($cartId);

            return response()->json([
                'success' => true,
                'data' => [
                    'cart_id' => $cartId,
                    'total' => $total,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }
}
