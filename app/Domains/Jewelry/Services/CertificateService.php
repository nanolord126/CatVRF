<?php declare(strict_types=1);

namespace App\Domains\Jewelry\Services;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

final class CertificateService
{
    public function __construct()
    {
    }

    public function verifyCertificate(int $jewelryId, string $certificateCode, string $correlationId): bool
    {




        try {
            $cert = DB::table('jewelry_certificates')
                ->where('jewelry_id', $jewelryId)
                ->where('certificate_code', $certificateCode)
                ->first();

            if (!$cert) {
                throw new \Exception('Certificate not found');
            }

            Log::channel('audit')->info('Jewelry certificate verified', [
                'jewelry_id' => $jewelryId,
                'certificate_code' => $certificateCode,
                'correlation_id' => $correlationId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Jewelry certificate verification failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
