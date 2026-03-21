<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Fashion\Models\FashionProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class ProductService
{
    public function createProduct(
        int $tenantId,
        int $storeId,
        int $categoryId,
        string $name,
        string $sku,
        float $price,
        int $stock,
        array $colors = [],
        array $sizes = [],
        ?string $correlationId = null,
    ): FashionProduct {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'createProduct'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createProduct', ['domain' => __CLASS__]);

        try {
            $correlationId ??= Str::uuid();

            $product = DB::transaction(function () use (
                $tenantId,
                $storeId,
                $categoryId,
                $name,
                $sku,
                $price,
                $stock,
                $colors,
                $sizes,
                $correlationId,
            ) {
                $product = FashionProduct::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => $tenantId,
                    'fashion_store_id' => $storeId,
                    'category_id' => $categoryId,
                    'name' => $name,
                    'sku' => $sku,
                    'price' => $price,
                    'current_stock' => $stock,
                    'colors' => collect($colors),
                    'sizes' => collect($sizes),
                    'status' => 'active',
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Fashion product created', [
                    'product_id' => $product->id,
                    'store_id' => $storeId,
                    'sku' => $sku,
                    'price' => $price,
                    'correlation_id' => $correlationId,
                ]);

                return $product;
            });

            return $product;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to create fashion product', [
                'error' => $e->getMessage(),
                'sku' => $sku,
                'correlation_id' => $correlationId ?? 'unknown',
            ]);

            throw $e;
        }
    }

    public function updateProduct(FashionProduct $product, array $data, ?string $correlationId = null): void
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'updateProduct'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL updateProduct', ['domain' => __CLASS__]);

        try {
            $correlationId ??= Str::uuid();

            DB::transaction(function () use ($product, $data, $correlationId) {
                $product->update([...$data, 'correlation_id' => $correlationId]);

                Log::channel('audit')->info('Fashion product updated', [
                    'product_id' => $product->id,
                    'correlation_id' => $correlationId,
                ]);
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to update fashion product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId ?? 'unknown',
            ]);

            throw $e;
        }
    }

    public function updateStock(FashionProduct $product, int $quantity, ?string $correlationId = null): void
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'updateStock'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL updateStock', ['domain' => __CLASS__]);

        try {
            $correlationId ??= Str::uuid();

            DB::transaction(function () use ($product, $quantity, $correlationId) {
                $product->update([
                    'current_stock' => $quantity,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Fashion product stock updated', [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'correlation_id' => $correlationId,
                ]);
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to update fashion product stock', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId ?? 'unknown',
            ]);

            throw $e;
        }
    }
}
