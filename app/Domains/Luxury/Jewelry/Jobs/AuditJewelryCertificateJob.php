<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Jobs;

use App\Domains\Luxury\Jewelry\Models\JewelryCustomOrder;
use App\Domains\Luxury\Jewelry\Models\JewelryProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\FraudControlService;

/**
 * AuditJewelryCertificateJob (Layer 7/9)
 * Periodically or on-demand checks high-value jewelry certificates against 
 * gemological databases (simulated here) and logs the outcome in the audit channel.
 */
class AuditJewelryCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $productId,
        public readonly string $correlationId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FraudControlService $fraudControl): void
    {
        try {
            Log::channel('audit')->info('Starting jewelry certificate audit', [
                'cid' => $this->correlationId,
                'pid' => $this->productId,
            ]);

            $product = JewelryProduct::findOrFail($this->productId);

            if (!$product->has_certification || empty($product->certificate_number)) {
                Log::channel('audit')->warning('Jewelry audit skipped: no certificate recorded.', [
                    'cid' => $this->correlationId,
                    'pid' => $this->productId,
                ]);
                return;
            }

            // Simulate External API Check (GIA/IGI/HRD)
            $isVerified = $this->verifyWithGemologicalInstitute($product->certificate_number);

            DB::transaction(function () use ($product, $isVerified, $fraudControl) {
                if ($isVerified) {
                    $product->update([
                        'tags' => array_unique(array_merge($product->tags ?? [], ['verified-authentic'])),
                    ]);

                    Log::channel('audit')->info('Jewelry certificate verified successfully.', [
                        'cid' => $this->correlationId,
                        'cert' => $product->certificate_number,
                    ]);
                } else {
                    // Critical Alert: Potential fraudulent certificate
                    $product->update(['is_published' => false]);
                    
                    Log::channel('fraud_alert')->error('CRITICAL: Jewelry certificate verification FAILED.', [
                        'cid' => $this->correlationId,
                        'pid' => $product->id,
                        'cert' => $product->certificate_number,
                    ]);

                    // Trigger additional fraud scoring
                    $fraudControl->recordViolation($product->tenant_id, 'fake-gem-certificate', $this->correlationId);
                }
            });

        } catch (\Throwable $e) {
            Log::channel('audit')->error('Jewelry audit job failed', [
                'cid' => $this->correlationId,
                'error' => $e->getMessage(),
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
