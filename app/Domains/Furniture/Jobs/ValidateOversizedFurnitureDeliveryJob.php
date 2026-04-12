<?php

declare(strict_types=1);

namespace App\Domains\Furniture\Jobs;


use App\Domains\Furniture\Models\FurnitureCustomOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class ValidateOversizedFurnitureDeliveryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /**
     * @param FurnitureCustomOrder $order Заказ на кастомную мебель
     * @param string|null $correlationId Идентификатор корреляции
     */
    public function __construct(
        private readonly FurnitureCustomOrder $order,
        private ?string $correlationId = null,
    ) {
    }

    /**
     * Execute dimensional check for custom furniture order items.
     * Проверяет габариты заказа и помечает как oversized при необходимости.
     */
    public function handle(LoggerInterface $logger): void
    {
        $correlationId = $this->correlationId ?? (string) Str::uuid();

        $logger->info('LAYER-8: Starting Dimensional Validation (Oversized Check)', [
            'order_id' => $this->order->id,
            'correlation_id' => $correlationId,
        ]);

        try {
            $spec = $this->order->ai_specification;
            $maxDim = 0;

            if (isset($spec['dimensions']) && is_array($spec['dimensions'])) {
                foreach ($spec['dimensions'] as $dim) {
                    $maxDim = max($maxDim, (int) $dim);
                }
            }

            if ($maxDim > 200) {
                $this->order->update([
                    'is_oversized' => true,
                    'tags' => array_merge($this->order->tags ?? [], ['oversized_delivery_required']),
                ]);

                $logger->warning('LAYER-8: Order Marked as Oversized', [
                    'order_id' => $this->order->id,
                    'max_dimension' => $maxDim,
                    'correlation_id' => $correlationId,
                ]);
            }

            $logger->info('LAYER-8: Dimensional Validation Completed', [
                'order_id' => $this->order->id,
                'is_oversized' => $maxDim > 200,
                'max_dimension' => $maxDim,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $logger->error('LAYER-8: Job Validation Failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Handle the job failure.
     * Логируем ошибку при финальном фейле джоба.
     */
    public function failed(\Throwable $exception): void
    {
        report(new \RuntimeException(
            sprintf(
                'LAYER-8: Critical Job Failure (Oversized Check) [order_id=%d, correlation_id=%s]: %s',
                $this->order->id,
                $this->correlationId ?? 'unknown',
                $exception->getMessage(),
            ),
            previous: $exception,
        ));
    }

    /**
     * Get tags for the job monitoring.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'furniture',
            'oversized_check',
            'order:' . $this->order->id,
        ];
    }
}
