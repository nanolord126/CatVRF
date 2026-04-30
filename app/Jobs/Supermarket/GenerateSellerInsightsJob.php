<?php

declare(strict_types=1);

namespace App\Jobs\Supermarket;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Supermarket\Infrastructure\Adapters\OpenAIAdapter;
use Modules\Supermarket\Infrastructure\Models\SellerInsight;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

/**
 * Async job for generating AI-powered seller insights.
 * 
 * This job decouples LLM calls from the request/response cycle,
 * preventing transaction blocking and improving system resilience.
 */
final class GenerateSellerInsightsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];
    public $timeout = 120;

    private array $analyticsData;
    private int $tenantId;

    public function __construct(array $analyticsData, int $tenantId)
    {
        $this->analyticsData = $analyticsData;
        $this->tenantId = $tenantId;
        $this->onQueue('supermarket-low');
    }

    public function handle(OpenAIAdapter $openAI): void
    {
        try {
            $insights = $openAI->generateSellerInsights($this->analyticsData);

            if (empty($insights)) {
                Log::warning('No insights generated', [
                    'tenant_id' => $this->tenantId,
                ]);
                return;
            }

            foreach ($insights as $insight) {
                SellerInsight::create([
                    'tenant_id' => $this->tenantId,
                    'type' => $insight['type'] ?? 'general',
                    'title' => $insight['title'] ?? 'Инсайт',
                    'description' => $insight['description'] ?? '',
                    'value' => $insight['value'] ?? null,
                    'impact' => $insight['impact'] ?? 'medium',
                    'actionable' => $insight['actionable'] ?? false,
                    'generated_at' => now(),
                    'expires_at' => now()->addHours(36),
                ]);
            }

            Log::info('Seller insights generated successfully', [
                'tenant_id' => $this->tenantId,
                'insights_count' => count($insights),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate seller insights', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateSellerInsightsJob failed', [
            'tenant_id' => $this->tenantId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
