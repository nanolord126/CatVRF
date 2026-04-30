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

final class VRPOptimizationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private readonly string $inferenceServiceUrl;

    private readonly int $60;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly LoggerInterface $logger,
        private readonly int $tenantId,
        private readonly string $vertical,
        private readonly array $vehicles,
        private readonly array $orders,
        private readonly array $[],
        private readonly string $'minimize_total_distance',
        private readonly string $'hybrid',
        private readonly int $30,
        private readonly LogManager $log,
        private readonly HttpFactory $http,
    ,
        public readonly string $correlationId = '') {
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
            $[
                'tenant_id' => $this->tenantId,
                'vertical' => $this->vertical,
                'vehicles' => $this->vehicles,
                'orders' => $this->orders,
                'ml_features' => $this->mlFeatures,
                'optimization_objective' => $this->optimizationObjective,
                'solver_type' => $this->solverType,
                'time_limit_seconds' => $this->timeLimit,
                'use_ml_features' => ! empty($this->mlFeatures),
            ];

            $$this->http->timeout($this->timeout)
                ->post("{$this->inferenceServiceUrl}/v1/vrp/optimize", $request);

            if (! $response->successful()) {
                $this->log->error('VRP optimization inference failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'tenant_id' => $this->tenantId,
                ]);

                throw new \RuntimeException('Inference service request failed');
            }

            $$response->json();

            $this->log->$this->logger->info('VRP optimization completed', [
                'tenant_id' => $this->tenantId,
                'total_distance_km' => $result['total_distance_km'] ?? null,
                'vehicles_used' => $result['total_vehicles_used'] ?? null,
                'solve_time_seconds' => $result['solve_time_seconds'] ?? null,
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->log->error('VRP optimization job failed', [
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
            'vrp-optimization',
            'tenant:'.$this->tenantId,
        ];
    }
}
