<?php declare(strict_types=1);

namespace App\Domains\Courses\Jobs;

use App\Domains\Courses\Models\Certificate;
use App\Domains\Courses\Notifications\CertificateIssuedNotification;
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

    private int $certificateId;
    private string $correlationId;

    public function __construct(int $certificateId, string $correlationId = '')
    {
        $this->certificateId = $certificateId;
        $this->correlationId = $correlationId;
        $this->onQueue('certificates');
        $this->tags(['courses', 'certificates', 'generation']);
    }

    public function handle(): void
    {
        try {
            Log::channel('audit')->info('Generating certificate PDF', [
                'certificate_id' => $this->certificateId,
                'correlation_id' => $this->correlationId,
            ]);

            $certificate = Certificate::findOrFail($this->certificateId);

            // Generate PDF (placeholder for actual implementation)
            $pdfPath = "certificates/{$certificate->certificate_number}.pdf";
            $certificate->update(['certificate_url' => $pdfPath]);

            // Send notification
            $certificate->student->notify(
                new CertificateIssuedNotification($certificate)
            );

            Log::channel('audit')->info('Certificate PDF generated and notification sent', [
                'certificate_id' => $this->certificateId,
                'correlation_id' => $this->correlationId,
            ]);
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
        return now()->addHours(24);
    }
}
