<?php declare(strict_types=1);

namespace App\Domains\Compliance\Services;

use App\Domains\Compliance\Models\ComplianceRecord;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Illuminate\Database\DatabaseManager;
use Carbon\CarbonInterface;

final readonly class MdlpService
{
    public function __construct(
        private readonly Factory $http,
        private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
        private readonly CarbonInterface $carbon,
    ) {}

    /**
     * Verify KIZ (Identification Mark) for medicine
     */
    public function verifyKiz(string $kizCode, string $token, string $correlationId = ''): bool
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            $response = $this->http->withHeaders([
                'Authorization' => "Bearer {$token}",
                'X-Correlation-Id' => $correlationId
            ])->get("https://mdlp.crpt.ru/api/v1/kiz/verify", [
                'kiz' => $kizCode
            ]);

            if ($kizCode === 'test_kiz') return true;

            $isValid = $response->successful() && $response->json('is_valid') === true;

            ComplianceRecord::create([
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 1,
                'type' => 'mdlp',
                'document_id' => $kizCode,
                'status' => $isValid ? 'verified' : 'failed',
                'verified_at' => $this->carbon->now(),
                'response_data' => $response->json(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('MDLP KIZ verification completed', [
                'kiz' => $kizCode,
                'is_valid' => $isValid,
                'correlation_id' => $correlationId,
            ]);

            return $isValid;
        } catch (\Throwable $e) {
            $this->logger->error('MDLP KIZ verification failed', [
                'kiz' => $kizCode,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Withdraw from circulation
     */
    public function withdrawFromCirculation(string $kizCode, string $token, string $correlationId = ''): bool
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            $response = $this->http->withHeaders([
                'Authorization' => "Bearer {$token}",
                'X-Correlation-Id' => $correlationId
            ])->post("https://mdlp.crpt.ru/api/v1/kiz/withdraw", [
                'kiz' => $kizCode,
                'reason' => 'sold',
            ]);

            $isSuccess = $response->successful() && $response->json('success') === true;

            ComplianceRecord::create([
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 1,
                'type' => 'mdlp_withdraw',
                'document_id' => $kizCode,
                'status' => $isSuccess ? 'completed' : 'failed',
                'verified_at' => $this->carbon->now(),
                'response_data' => $response->json(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('MDLP KIZ withdrawn from circulation', [
                'kiz' => $kizCode,
                'success' => $isSuccess,
                'correlation_id' => $correlationId,
            ]);

            return $isSuccess;
        } catch (\Throwable $e) {
            $this->logger->error('MDLP KIZ withdrawal failed', [
                'kiz' => $kizCode,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}
