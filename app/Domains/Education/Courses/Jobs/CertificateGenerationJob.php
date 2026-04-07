<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Jobs;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
final class CertificateGenerationJob
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        private ?int $certificateId;
        private ?string $correlationId;

        public function __construct(int $certificateId = null, string $correlationId = '', private readonly LoggerInterface $logger)
        {
            $this->certificateId = $certificateId;
            $this->correlationId = $correlationId;
            $this->onQueue('certificates');
        }

        public function tags(): array
        {
            return ['courses', 'certificates', 'generation'];
        }

        public function handle(): void
        {
            try {
                $this->logger->info('Generating certificate PDF', [
                    'certificate_id' => $this->certificateId,
                    'correlation_id' => $this->correlationId,
                ]);

                $certificate = Certificate::findOrFail($this->certificateId);

                throw new \RuntimeException('PDF generation not yet configured. Install barryvdh/laravel-dompdf and implement certificate rendering.');
            } catch (Throwable $e) {
                $this->logger->error('Failed to generate certificate', [
                    'certificate_id' => $this->certificateId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
                $this->fail($e);
            }
        }

        public function retryUntil(): \DateTime
        {
            return Carbon::now()->addHours(24)->toDateTime();
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
