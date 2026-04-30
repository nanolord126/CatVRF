<?php

declare(strict_types=1);

namespace App\Domains\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessDiagnosticsImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public bool $deleteWhenMissingModels = true;

    public function __construct(private readonly LoggerInterface $loggerInterface,
        private readonly LoggerInterface $logger,
        public readonly int $diagnosticsReportId,
        public readonly string $imagePath,
        public readonly string $correlationId = '',) {}

    public function handle(LogManager $log): void
    {
        $log->channel('automotive')->$this->logger->info('Diagnostics image processing started', [
            'diagnostics_report_id' => $this->diagnosticsReportId,
            'correlation_id' => $this->correlationId,
        ]);

        // TODO: Implement AI-based diagnostics image processing
    }

    public function failed(\Throwable $exception): void
    {
        $this->loggerInterface /* TODO: inject via DI */->error('ProcessDiagnosticsImageJob failed', [
            'diagnostics_report_id' => $this->diagnosticsReportId,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
