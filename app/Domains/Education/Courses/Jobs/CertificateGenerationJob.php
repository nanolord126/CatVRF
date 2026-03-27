<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Jobs;

use App\Domains\Education\Courses\Models\Certificate;
use App\Domains\Education\Courses\Notifications\CertificateIssuedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CertificateGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ?int $certificateId;
    private ?string $correlationId;

    public function __construct(int $certificateId = null, string $correlationId = '')
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
            Log::channel('audit')->info('Generating certificate PDF', [
                'certificate_id' => $this->certificateId,
                'correlation_id' => $this->correlationId,
            ]);

            $certificate = Certificate::findOrFail($this->certificateId);

            throw new \RuntimeException('PDF generation not yet configured. Install barryvdh/laravel-dompdf and implement certificate rendering.');
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to generate certificate', [
                'certificate_id' => $this->certificateId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            $this->fail($e);
        }
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(24)->toDateTime();
    }
}


