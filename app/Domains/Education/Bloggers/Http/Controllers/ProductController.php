<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class ProductController extends Controller
{

    public function __construct(
            private readonly LiveCommerceService $commerceService, private readonly LoggerInterface $logger) {}

        /**
         * Add product to stream
         */
        public function addProduct(): JsonResponse
        {
            try {
                $correlationId = (string) Str::uuid();
                $blogerId = $request->user()?->id;

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

                $this->logger->info('Product added to stream', [
                    'correlation_id' => $correlationId,
                    'stream_id' => $stream->id,
                    'product_id' => $product->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'data' => $product,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Add product failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
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
                $blogerId = $request->user()?->id;

                $stream = Stream::where('room_id', $roomId)
                    ->where('blogger_id', $blogerId)
                    ->where('status', 'live')
                    ->firstOrFail();

                $product = $this->commerceService->pinProduct(
                    streamId: (int) $stream->id,
                    streamProductId: $productId,
                    correlationId: $correlationId,
                );

                $this->logger->info('Product pinned', [
                    'correlation_id' => $correlationId,
                    'stream_id' => $stream->id,
                    'product_id' => $product->id,
                    'pin_position' => $product->pin_position,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'data' => $product,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Pin product failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
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
                $blogerId = $request->user()?->id;

                $stream = Stream::where('room_id', $roomId)
                    ->where('blogger_id', $blogerId)
                    ->where('status', 'live')
                    ->firstOrFail();

                $product = $this->commerceService->unpinProduct(
                    streamId: (int) $stream->id,
                    streamProductId: $productId,
                    correlationId: $correlationId,
                );

                $this->logger->info('Product unpinned', [
                    'correlation_id' => $correlationId,
                    'stream_id' => $stream->id,
                    'product_id' => $product->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'data' => $product,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Unpin product failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'data' => $products,
                    'count' => count($products),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'message' => 'Failed to fetch pinned products',
                ], 400);
            }
        }
}
