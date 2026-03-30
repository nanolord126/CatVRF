<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CertificateGenerationJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
