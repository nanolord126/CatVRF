<?php

declare(strict_types=1);

namespace App\Jobs\Logistics;

use Psr\Log\LoggerInterface;

use Illuminate\Support\Str;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;

final class CourierAssignmentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private readonly string $inferenceServiceUrl;

    private readonly int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly LoggerInterface $logger,
        private readonly int $tenantId,
        private readonly array $shipmentData,
        private readonly array $couriersData,
        private readonly bool $includeExplanations = false,
        private readonly int $topK = 3,
        private readonly LogManager $log,
        private readonly HttpFactory $http,
        public readonly string $correlationId = '',) {
        $this->inferenceServiceUrl = config('services.logistics_inference.url', 'http://localhost:8000');
        $this->onQueue('logistics-inference');
    }

    /**
     * Execute the job.
     */
    public function handle(): array
    {
        $correlationId = $this->correlationId ?: (string) Str::uuid();
        try {
            $request = [
                'tenant_id' => $this->tenantId,
                'vertical' => $this->shipmentData['vertical'] ?? 'logistics',
                'shipment' => $this->shipmentData,
                'available_couriers' => $this->couriersData,
                'include_explanations' => $this->includeExplanations,
                'top_k' => $this->topK,
            ];

            $response = $this->http->timeout($this->timeout)
                ->post("{$this->inferenceServiceUrl}/v1/courier/assign", $request);

            if (! $response->successful()) {
                $this->log->error('Courier assignment inference failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'tenant_id' => $this->tenantId,
                ]);

                throw new \RuntimeException('Inference service request failed');
            }

            $result = $response->json();

            $this->log->$this->logger->info('Courier assignment completed', [
                'tenant_id' => $this->tenantId,
                'shipment_id' => $this->shipmentData['shipment_id'] ?? null,
                'recommended_courier' => $result['recommended_courier'] ?? null,
                'inference_time_ms' => $result['inference_time_ms'] ?? null,
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->log->error('Courier assignment job failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantId,
            ]);

            throw $e;
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'logistics',
            'courier-assignment',
            'tenant:'.$this->tenantId,
        ];
    }
}
