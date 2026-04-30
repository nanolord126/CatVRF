<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Generate3DFromPhotosService;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;
use Throwable;

final class Generate3DModelJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;
    public int $backoff = [30, 60, 120];

    public function __construct(
        private readonly int $productId,
        private readonly string $productType,
        private readonly array $photoIds,
        private readonly string $type = 'clothing',
        private readonly ?int $variantId = null,
        private readonly ?string $correlationId = null,
    ) {
        $this->onQueue('3d-generation');
    }

    public function handle(Generate3DFromPhotosService $service): void
    {
        $startTime = microtime(true);

        try {
            $product = $this->fetchProduct();
            if (!$product) {
                $this->fail(new \RuntimeException("Product not found: {$this->productId}"));
                return;
            }

            Log::info('Starting 3D model generation', [
                'product_id' => $this->productId,
                'product_type' => $this->productType,
                'variant_id' => $this->variantId,
                'type' => $this->type,
                'photo_count' => count($this->photoIds),
                'correlation_id' => $this->correlationId,
            ]);

            $asset = $service->generateFromPhotos(
                $product,
                $this->productType,
                $this->photoIds,
                $this->type,
                $this->variantId
            );

            $processingTime = round((microtime(true) - $startTime) * 1000);

            Log::info('3D model generation completed successfully', [
                'asset_id' => $asset->id,
                'product_id' => $this->productId,
                'processing_time_ms' => $processingTime,
                'confidence' => $asset->generation_confidence,
                'quality_score' => $asset->quality_score,
                'polygon_count' => $asset->polygon_count,
                'correlation_id' => $this->correlationId,
            ]);

            $this->notifySuccess($asset, $processingTime);

        } catch (\Exception $e) {
            Log::error('3D model generation failed', [
                'product_id' => $this->productId,
                'product_type' => $this->productType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->notifyFailure($e);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('3D model generation job failed permanently', [
            'product_id' => $this->productId,
            'product_type' => $this->productType,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'correlation_id' => $this->correlationId,
        ]);

        $this->markAssetAsFailed($exception->getMessage());
    }

    private function fetchProduct(): ?object
    {
        return match($this->productType) {
            'fashion', 'clothing' => \App\Domains\Fashion\Models\FashionProduct::find($this->productId),
            'footwear', 'shoes' => \App\Domains\Footwear\Models\FootwearProduct::find($this->productId),
            default => null,
        };
    }

    private function notifySuccess(\App\Models\Product3DAsset $asset, int $processingTime): void
    {
        try {
            $notification = new \App\Notifications\ThreeDModelGeneratedNotification(
                $asset,
                $processingTime,
                $this->correlationId
            );

            $tenant = \App\Models\Tenant::find($asset->tenant_id);
            if ($tenant) {
                $tenant->notify($notification);
            }

            \App\Events\ThreeDModelGenerated::dispatch($asset, $processingTime);

        } catch (\Exception $e) {
            Log::warning('Failed to send success notification', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyFailure(\Exception $exception): void
    {
        try {
            $notification = new \App\Notifications\ThreeDModelGenerationFailedNotification(
                $this->productId,
                $this->productType,
                $exception->getMessage(),
                $this->correlationId
            );

            $tenant = \App\Models\Tenant::current();
            if ($tenant) {
                $tenant->notify($notification);
            }

            \App\Events\ThreeDModelGenerationFailed::dispatch(
                $this->productId,
                $this->productType,
                $exception->getMessage()
            );

        } catch (\Exception $e) {
            Log::warning('Failed to send failure notification', [
                'product_id' => $this->productId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function markAssetAsFailed(string $errorMessage): void
    {
        try {
            $asset = \App\Models\Product3DAsset::where('product_id', $this->productId)
                ->where('product_type', $this->getProductClass())
                ->where('processing_status', '!=', \App\Models\Product3DAsset::PROCESSING_STATUS_COMPLETED)
                ->latest()
                ->first();

            if ($asset) {
                $asset->update([
                    'processing_status' => \App\Models\Product3DAsset::PROCESSING_STATUS_FAILED,
                    'error_message' => $errorMessage,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to mark asset as failed', [
                'product_id' => $this->productId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getProductClass(): string
    {
        return match($this->productType) {
            'fashion', 'clothing' => \App\Domains\Fashion\Models\FashionProduct::class,
            'footwear', 'shoes' => \App\Domains\Footwear\Models\FootwearProduct::class,
            default => throw new \InvalidArgumentException("Unknown product type: {$this->productType}"),
        };
    }

    public static function batchGenerate(array $products): Batch
    {
        $jobs = collect($products)->map(function ($product) {
            return new self(
                $product['id'],
                $product['type'],
                $product['photo_ids'],
                $product['asset_type'] ?? 'clothing',
                $product['variant_id'] ?? null,
                $product['correlation_id'] ?? null,
            );
        })->toArray();

        return Bus::batch($jobs)
            ->then(function (Batch $batch) {
                Log::info('Batch 3D generation completed', [
                    'batch_id' => $batch->id,
                    'total_jobs' => $batch->totalJobs,
                    'failed_jobs' => $batch->failedJobs,
                ]);
            })
            ->catch(function (Batch $batch, Throwable $e) {
                Log::error('Batch 3D generation failed', [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage(),
                ]);
            })
            ->onQueue('3d-generation-batch')
            ->allowFailures()
            ->dispatch();
    }
}
