<?php declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;


use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

final class AuditJewelryCertificateJob
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public int $tries = 3;
        public int $backoff = 60;

        /**
         * Create a new job instance.
         */
        public function __construct(private readonly int $productId,
            private readonly string $correlationId,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly Request $request, private readonly LoggerInterface $logger) {}

        /**
         * Execute the job.
         */
        public function handle(FraudControlService $fraud): void
        {
            try {
                $this->logger->info('Starting jewelry certificate audit', [
                    'cid' => $this->correlationId,
                    'pid' => $this->productId,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

                $product = JewelryProduct::findOrFail($this->productId);

                if (!$product->has_certification || empty($product->certificate_number)) {
                    $this->logger->warning('Jewelry audit skipped: no certificate recorded.', [
                        'cid' => $this->correlationId,
                        'pid' => $this->productId,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                    return;
                }

                // Simulate External API Check (GIA/IGI/HRD)
                $isVerified = $this->verifyWithGemologicalInstitute($product->certificate_number);

                $this->db->transaction(function () use ($product, $isVerified, $fraudControl) {
                    if ($isVerified) {
                        $product->update([
                            'tags' => array_unique(array_merge($product->tags ?? [], ['verified-authentic'])),
                        ]);

                        $this->logger->info('Jewelry certificate verified successfully.', [
                            'cid' => $this->correlationId,
                            'cert' => $product->certificate_number,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                    } else {
                        // Critical Alert: Potential fraudulent certificate
                        $product->update(['is_published' => false]);

                        $this->logger->error('CRITICAL: Jewelry certificate verification FAILED.', [
                            'cid' => $this->correlationId,
                            'pid' => $product->id,
                            'cert' => $product->certificate_number,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

                        // Trigger additional fraud scoring
                        $fraudControl->recordViolation($product->tenant_id, 'fake-gem-certificate', $this->correlationId);
                    }
                });

            } catch (\Throwable $e) {
                $this->logger->error('Jewelry audit job failed', [
                    'cid' => $this->correlationId,
                    'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

                $this->fail($e);
            }
        }

        /**
         * Simulated external verification.
         */
        private function verifyWithGemologicalInstitute(string $certNumber): bool
        {
            // Simple mock: Certificates starting with 'FAKE' are considered invalid.
            return !str_starts_with($certNumber, 'FAKE');
        }

        /**
         * Tags for the job to identify in the monitoring dashboard.
         */
        public function tags(): array
        {
            return ['jewelry', 'audit', 'compliance', $this->correlationId];
        }
}
