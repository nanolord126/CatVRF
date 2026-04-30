<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supermarket;

use App\Domains\Supermarket\DTOs\B2BOrderData;
use App\Domains\Supermarket\Services\B2BService;
use App\Domains\Supermarket\Services\SupermarketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final readonly class B2BCheckoutController
{
    public function __construct(
        private readonly B2BService $b2bService,
        private readonly SupermarketService $supermarketService,
    ) {}

    /**
     * Оформить B2B заказ.
     */
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|integer|min:0',
            'items.*.warehouse_id' => 'nullable|integer',
            'seller_address' => 'required|string',
            'buyer_address' => 'required|string',
            'delivery_slot' => 'required|string',
            'sub_vertical' => 'nullable|string',
            'warehouse_id' => 'nullable|integer',
            'require_invoice' => 'boolean',
            'require_upd' => 'boolean',
            'require_contract' => 'boolean',
        ]);

        $company = $request->attributes->get('b2b_company');
        if (!$company) {
            throw new \RuntimeException('B2B company not found in request');
        }

        $orderData = B2BOrderData::fromArray([
            'user_id' => $request->user()->id,
            'company_id' => $company->id,
            'tenant_id' => $company->user_id,
            'items' => $validated['items'],
            'seller_address' => $validated['seller_address'],
            'buyer_address' => $validated['buyer_address'],
            'delivery_slot' => $validated['delivery_slot'],
            'sub_vertical' => $validated['sub_vertical'] ?? null,
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'require_invoice' => $validated['require_invoice'] ?? true,
            'require_upd' => $validated['require_upd'] ?? true,
            'require_contract' => $validated['require_contract'] ?? false,
        ]);

        try {
            $result = $this->b2bService->createB2BOrder($orderData);

            return response()->json([
                'message' => 'B2B order created successfully',
                'order_id' => $result['order_id'],
                'total_amount' => $result['total_amount'],
                'documents_required' => $result['documents_required'],
                'correlation_id' => $result['correlation_id'],
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => 'Order creation failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
