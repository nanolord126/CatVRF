<?php

declare(strict_types=1);

namespace App\Jobs;

use Psr\Log\LoggerInterface;

use App\Services\Security\ContinuousAuthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Carbon\CarbonImmutable;

final class ContinuousScoringJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    private readonly string $correlationId;

    public function __construct(private readonly LoggerInterface $logger,
        public readonly string $sessionId,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,) {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('continuous-auth');
    }

    public function tags(): array
    {
        return ['security', 'continuous-scoring', 'session:' . $this->sessionId];
    }

    public function retryUntil(): \DateTime
    {
        return CarbonImmutable::now()->addMinutes(10);
    }

    public function handle(ContinuousAuthService $continuousAuth): void
    {
        $this->logger->channel('audit')->$this->logger->info('[ContinuousScoringJob] Started', [
            'session_id' => $this->sessionId,
            'correlation_id' => $this->correlationId,
        ]);

        try {
            $result = $continuousAuth->scoreSession($this->sessionId);

            $this->db->table('continuous_scoring_logs')->insert([
                'session_id' => $this->sessionId,
                'score' => $result['score'] ?? 0,
                'risk_level' => $result['risk_level'] ?? 'unknown',
                'correlation_id' => $this->correlationId,
                'scored_at' => CarbonImmutable::now(),
            ]);

            $this->logger->channel('audit')->$this->logger->info('[ContinuousScoringJob] Completed', [
                'session_id' => $this->sessionId,
                'correlation_id' => $this->correlationId,
                'score' => $result['score'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('[ContinuousScoringJob] Failed', [
                'session_id' => $this->sessionId,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            $this->release(60);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->channel('audit')->error('[ContinuousScoringJob] Failed permanently', [
            'session_id' => $this->sessionId,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
