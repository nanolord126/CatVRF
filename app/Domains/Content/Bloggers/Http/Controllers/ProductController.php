<?php

declare(strict_types=1);

namespace App\Domains\Content\Bloggers\Http\Controllers;

use App\Domains\Content\Bloggers\Models\Stream;
use App\Domains\Content\Bloggers\Services\LiveCommerceService;
use App\Domains\Content\Bloggers\Http\Requests\AddProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ProductController
{
    public function __construct(
        private readonly LiveCommerceService $commerceService,
    ) {}

    /**
     * Add product to stream
     */
    public function a: JsonResponse
    {
        try {
            $correlationId = (string) Str::uuid();
            $blogerId = auth()->id();

            $stream = Stream::where('room_id', $roomId)
                ->where('blogger_id', $blogerId)
                ->where('status', 'live')
                ->firstOrFail();

            $product = $this->commerceService->addProductToStream(
                streamId: (int) $stream->id,
                productId: $request->integer('product_id'),
                priceOverride: $request->integer('price_override', null),
                quantity: $request->integer('quantity', 1),
                correlationId: $correlationId,
            );

            Log::channel('audit')->info('Product added to stream', [
                'correlation_id' => $correlationId,
                'stream_id' => $stream->id,
                'product_id' => $product->id,
            ]);

            return response()->json([
                'correlation_id' => $correlationId,
                'data' => $product,
            ], 201);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Add product failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to add product',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Pin product (max 5 per stream)
     */
    public function pin(string $roomId, int $productId): JsonResponse
    {
        try {
            $correlationId = (string) Str::uuid();
            $blogerId = auth()->id();

            $stream = Stream::where('room_id', $roomId)
                ->where('blogger_id', $blogerId)
                ->where('status', 'live')
                ->firstOrFail();

            $product = $this->commerceService->pinProduct(
                streamId: (int) $stream->id,
                streamProductId: $productId,
                correlationId: $correlationId,
            );

            Log::channel('audit')->info('Product pinned', [
                'correlation_id' => $correlationId,
                'stream_id' => $stream->id,
                'product_id' => $product->id,
                'pin_position' => $product->pin_position,
            ]);

            return response()->json([
                'correlation_id' => $correlationId,
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Pin product failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to pin product',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Unpin product
     */
    public function unpin(string $roomId, int $productId): JsonResponse
    {
        try {
            $correlationId = (string) Str::uuid();
            $blogerId = auth()->id();

            $stream = Stream::where('room_id', $roomId)
                ->where('blogger_id', $blogerId)
                ->where('status', 'live')
                ->firstOrFail();

            $product = $this->commerceService->unpinProduct(
                streamId: (int) $stream->id,
                streamProductId: $productId,
                correlationId: $correlationId,
            );

            Log::channel('audit')->info('Product unpinned', [
                'correlation_id' => $correlationId,
                'stream_id' => $stream->id,
                'product_id' => $product->id,
            ]);

            return response()->json([
                'correlation_id' => $correlationId,
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Unpin product failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to unpin product',
            ], 400);
        }
    }

    /**
     * Get pinned products for stream
     */
    public function getPinned(string $roomId): JsonResponse
    {
        try {
            $stream = Stream::where('room_id', $roomId)
                ->where('tenant_id', tenant()->id)
                ->firstOrFail();

            $products = $stream->pinnedProducts()
                ->orderBy('pin_position', 'asc')
                ->get();

            return response()->json([
                'data' => $products,
                'count' => count($products),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch pinned products',
            ], 400);
        }
    }
}
