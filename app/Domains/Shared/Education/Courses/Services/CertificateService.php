<?php

declare(strict_types=1);

namespace App\Domains\Education\Courses\Services;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

use Carbon\CarbonImmutable;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Database\DatabaseManager;

final readonly class CertificateService
{
    public function __construct(private readonly BusDispatcher $bus,
        private readonly FraudControlService $fraud,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard) {}

    public function issueCertificate(
        Enrollment $enrollment,
        string $studentName,
        string $correlationId = '',
    ): Certificate {

        try {
            $this->logger->$this->logger->info('Issuing certificate', [
                'enrollment_id' => $enrollment->id,
                'student_name' => $studentName,
                'correlation_id' => $correlationId,
            ]);

            if ($enrollment->progress_percent < 100) {
                throw new \RuntimeException('Student must complete 100% of course to receive certificate');
            }

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

            $certificate = $this->db->transaction(function () use ($enrollment, $studentName, $correlationId) {
                $verificationCode = strtoupper(Str::random(12));
                $certificateNumber = 'CERT-'.CarbonImmutable::now()->format('Y').'-'.Str::random(8);

                $certificate = Certificate::create([
                    'tenant_id' => tenant()->id,
                    'enrollment_id' => $enrollment->id,
                    'course_id' => $enrollment->course_id,
                    'student_id' => $enrollment->student_id,
                    'certificate_number' => $certificateNumber,
                    'issued_at' => CarbonImmutable::now(),
                    'verification_code' => $verificationCode,
                    'student_name' => $studentName,
                    'achievement_description' => "Successfully completed {$enrollment->course->title}",
                    'correlation_id' => $correlationId,
                ]);

                CertificateIssued::$this->bus->dispatch($certificate, $correlationId);

                return $certificate;
            });

            $this->logger->$this->logger->info('Certificate issued successfully', [
                'certificate_id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
                'correlation_id' => $correlationId,
            ]);

            return $certificate;
        } catch (Throwable $e) {
            $this->logger->error('Failed to issue certificate', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function verifyCertificate(string $verificationCode, string $correlationId = ''): ?Certificate
    {
        try {
            $this->logger->$this->logger->info('Verifying certificate', [
                'verification_code' => $verificationCode,
                'correlation_id' => $correlationId,
            ]);

            $certificate = Certificate::where('verification_code', $verificationCode)
                ->first();

            $this->logger->$this->logger->info('Certificate verification result', [
                'found' => $certificate ? true : false,
                'correlation_id' => $correlationId,
            ]);

            return $certificate;
        } catch (Throwable $e) {
            $this->logger->error('Failed to verify certificate', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
