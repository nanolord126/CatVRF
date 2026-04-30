<?php

declare(strict_types=1);

namespace App\Jobs\KYB;

use Psr\Log\LoggerInterface;

use App\Services\KYB\PEPScreeningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final class PEPRescreeningJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    private readonly string $correlationId;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,) {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('kyb-rescreen');
    }

    public function tags(): array
    {
        return ['kyb', 'pep', 'rescreen'];
    }

    public function retryUntil(): \DateTime
    {
        return CarbonImmutable::now()->addHours(2);
    }

    public function handle(PEPScreeningService $pepScreening): void
    {
        $this->logger->channel('audit')->$this->logger->info('[PEPRescreeningJob] Started', [
            'correlation_id' => $this->correlationId,
        ]);

        try {
            $results = $pepScreening->reScreenPEPs($this->correlationId);

            $this->db->table('kyb_rescreen_logs')->insert([
                'job_type' => 'pep_rescreen',
                'results_count' => $results->count(),
                'correlation_id' => $this->correlationId,
                'completed_at' => CarbonImmutable::now(),
            ]);

            $this->logger->channel('audit')->$this->logger->info('[PEPRescreeningJob] Completed', [
                'correlation_id' => $this->correlationId,
                'results_count' => $results->count(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('[PEPRescreeningJob] Failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->channel('audit')->error('[PEPRescreeningJob] Failed permanently', [
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
