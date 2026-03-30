<?php declare(strict_types=1);

namespace App\Domains\Furniture\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ValidateOversizedFurnitureDeliveryJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public $tries = 3;
        public $timeout = 60;

        /**
         * @param FurnitureCustomOrder $order
         * @param string|null $correlationId
         */
        public function __construct(
            private readonly FurnitureCustomOrder $order,
            private readonly ?string $correlationId = null
        ) {}

        /**
         * Execute dimensional check for custom furniture order items.
         */
        public function handle(): void
        {
            $correlationId = $this->correlationId ?? (string) \Illuminate\Support\Str::uuid();

            Log::channel('audit')->info('LAYER-8: Starting Dimensional Validation (Oversized Check)', [
                'order_id' => $this->order->id,
                'correlation_id' => $correlationId,
            ]);

            try {
                // Logic to calculate dimensions of custom project (mocked)
                $spec = $this->order->ai_specification;
                $maxDim = 0;

                if (isset($spec['dimensions'])) {
                    foreach ($spec['dimensions'] as $dim) {
                        $maxDim = max($maxDim, (int) $dim);
                    }
                }

                // If any dimension exceeds 200cm, mark as oversized
                if ($maxDim > 200) {
                    $this->order->update([
                        'is_oversized' => true,
                        'tags' => array_merge($this->order->tags ?? [], ['oversized_delivery_required'])
                    ]);

                    Log::channel('audit')->warning('LAYER-8: Order Marked as Oversized', [
                        'order_id' => $this->order->id,
                        'max_dimension' => $maxDim,
                        'correlation_id' => $correlationId,
                    ]);
                }

                Log::channel('audit')->info('LAYER-8: Dimensional Validation Completed', [
                    'order_id' => $this->order->id,
                    'correlation_id' => $correlationId,
                ]);

            } catch (\Throwable $e) {
                Log::channel('audit')->error('LAYER-8: Job Validation Failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                throw $e;
            }
        }

        /**
         * Handle the job failure.
         */
        public function failed(\Throwable $exception): void
        {
            Log::channel('audit')->error('LAYER-8: Critical Job Failure (Oversized Check)', [
                'error' => $exception->getMessage(),
                'order_id' => $this->order->id,
            ]);
        }

        /**
         * Get tags for the job monitoring.
         */
        public function tags(): array
        {
            return ['furniture', 'oversized_check', 'order:' . $this->order->id];
        }
}
