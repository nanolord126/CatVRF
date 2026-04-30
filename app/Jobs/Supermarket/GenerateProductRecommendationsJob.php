<?php

declare(strict_types=1);

namespace App\Jobs\Supermarket;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Supermarket\Infrastructure\Adapters\OpenAIAdapter;
use Illuminate\Support\Facades\Log;

/**
 * Async job for generating AI-powered product recommendations.
 * 
 * This job decouples LLM calls from the request/response cycle,
 * preventing transaction blocking and improving system resilience.
 */
final class GenerateProductRecommendationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];
    public $timeout = 120;

    private array $orderHistory;
    private int $limit;
    private int $userId;

    public function __construct(array $orderHistory, int $limit, int $userId)
    {
        $this->orderHistory = $orderHistory;
        $this->limit = $limit;
        $this->userId = $userId;
        $this->onQueue('supermarket-low');
    }

    public function handle(OpenAIAdapter $openAI): void
    {
        try {
            $recommendations = $openAI->generateProductRecommendations(
                $this->orderHistory,
                $this->limit
            );

            Log::info('Product recommendations generated successfully', [
                'user_id' => $this->userId,
                'recommendations_count' => count($recommendations),
            ]);

            // Store recommendations in cache for quick access
            $cacheKey = "supermarket:recommendations:user:{$this->userId}";
            \Illuminate\Support\Facades\Cache::put(
                $cacheKey,
                $recommendations,
                now()->addHours(24)
            );
        } catch (\Exception $e) {
            Log::error('Failed to generate product recommendations', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateProductRecommendationsJob failed', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
