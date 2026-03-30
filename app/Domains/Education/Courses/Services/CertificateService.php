<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CertificateService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function issueCertificate(
            Enrollment $enrollment,
            string $studentName,
            string $correlationId = '',
        ): Certificate {


            try {
                Log::channel('audit')->info('Issuing certificate', [
                    'enrollment_id' => $enrollment->id,
                    'student_name' => $studentName,
                    'correlation_id' => $correlationId,
                ]);

                if ($enrollment->progress_percent < 100) {
                    throw new \Exception('Student must complete 100% of course to receive certificate');
                }

                $this->fraudControlService->check(
                    auth()->id() ?? 0,
                    __CLASS__ . '::' . __FUNCTION__,
                    0,
                    request()->ip(),
                    null,
                    $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
                );

                $certificate = DB::transaction(function () use ($enrollment, $studentName, $correlationId) {
                    $verificationCode = strtoupper(Str::random(12));
                    $certificateNumber = 'CERT-' . now()->format('Y') . '-' . Str::random(8);

                    $certificate = Certificate::create([
                        'tenant_id' => tenant('id'),
                        'enrollment_id' => $enrollment->id,
                        'course_id' => $enrollment->course_id,
                        'student_id' => $enrollment->student_id,
                        'certificate_number' => $certificateNumber,
                        'issued_at' => now(),
                        'verification_code' => $verificationCode,
                        'student_name' => $studentName,
                        'achievement_description' => "Successfully completed {$enrollment->course->title}",
                        'correlation_id' => $correlationId,
                    ]);

                    CertificateIssued::dispatch($certificate, $correlationId);

                    return $certificate;
                });

                Log::channel('audit')->info('Certificate issued successfully', [
                    'certificate_id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'correlation_id' => $correlationId,
                ]);

                return $certificate;
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to issue certificate', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }

        public function verifyCertificate(string $verificationCode, string $correlationId = ''): ?Certificate
        {
            try {
                Log::channel('audit')->info('Verifying certificate', [
                    'verification_code' => $verificationCode,
                    'correlation_id' => $correlationId,
                ]);

                $certificate = Certificate::where('verification_code', $verificationCode)
                    ->first();

                Log::channel('audit')->info('Certificate verification result', [
                    'found' => $certificate ? true : false,
                    'correlation_id' => $correlationId,
                ]);

                return $certificate;
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to verify certificate', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }
}
