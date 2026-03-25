<?php declare(strict_types=1);

namespace App\Domains\Pet\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Pet\Models\PetClinic;
use App\Domains\Pet\Models\PetProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ProductService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function createProduct(PetClinic $clinic, array $data, string $correlationId = null): PetProduct
    {


        $correlationId ??= Str::uuid()->toString();

        try {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
$this->db->transaction(function () use ($clinic, $data, $correlationId) {
                $product = PetProduct::create([
                    ...$data,
                    'tenant_id' => tenant()->id,
                    'clinic_id' => $clinic->id,
                    'correlation_id' => $correlationId,
                    'uuid' => Str::uuid(),
                ]);

                $this->log->channel('audit')->info('Pet product created', [
                    'product_id' => $product->id,
                    'clinic_id' => $clinic->id,
                    'name' => $product->name,
                    'correlation_id' => $correlationId,
                ]);

                return $product;
            });
        } catch (\Throwable $e) {
            $this->log->error('Failed to create pet product', [
                'clinic_id' => $clinic->id,
                'data' => $data,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function updateProduct(PetProduct $product, array $data, string $correlationId = null): PetProduct
    {


        $correlationId ??= Str::uuid()->toString();

        try {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
$this->db->transaction(function () use ($product, $data, $correlationId) {
                $product->update([
                    ...$data,
                    'correlation_id' => $correlationId,
                ]);

                $this->log->channel('audit')->info('Pet product updated', [
                    'product_id' => $product->id,
                    'clinic_id' => $product->clinic_id,
                    'correlation_id' => $correlationId,
                ]);

                return $product;
            });
        } catch (\Throwable $e) {
            $this->log->error('Failed to update pet product', [
                'product_id' => $product->id,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function updateStock(PetProduct $product, int $quantity, string $correlationId = null): PetProduct
    {


        $correlationId ??= Str::uuid()->toString();

        try {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
$this->db->transaction(function () use ($product, $quantity, $correlationId) {
                $newStock = $product->current_stock + $quantity;
                if ($newStock < 0) {
                    throw new \RuntimeException('Insufficient stock');
                }

                $product->update([
                    'current_stock' => $newStock,
                    'correlation_id' => $correlationId,
                ]);

                $this->log->channel('audit')->info('Pet product stock updated', [
                    'product_id' => $product->id,
                    'previous_stock' => $product->current_stock - $quantity,
                    'new_stock' => $newStock,
                    'quantity_change' => $quantity,
                    'correlation_id' => $correlationId,
                ]);

                return $product;
            });
        } catch (\Throwable $e) {
            $this->log->error('Failed to update product stock', [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
