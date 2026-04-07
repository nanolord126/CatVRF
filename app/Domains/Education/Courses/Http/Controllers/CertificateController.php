<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class CertificateController extends Controller
{

    public function __construct(
            private readonly CertificateService $certificateService, private readonly LoggerInterface $logger) {}

        public function show(int $id): JsonResponse
        {
            try {
                $certificate = Certificate::with(['enrollment', 'course'])
                    ->findOrFail($id);

                $this->authorize('view', $certificate);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $certificate,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to show certificate', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Certificate not found',
                ], 404);
            }
        }

        public function myCertificates(): JsonResponse
        {
            try {
                $certificates = Certificate::where('student_id', $request->user()?->id)
                    ->with(['course'])
                    ->orderByDesc('issued_at')
                    ->paginate(10);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $certificates,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to list my certificates', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to list my certificates',
                ], 500);
            }
        }

        public function download(int $id): JsonResponse
        {
            try {
                $certificate = Certificate::findOrFail($id);
                $this->authorize('download', $certificate);

                if (!$certificate->certificate_url) {
                    return new \Illuminate\Http\JsonResponse([
                        'success' => false,
                        'message' => 'Certificate not yet generated',
                    ], 400);
                }

                $this->logger->info('Certificate downloaded', [
                    'certificate_id' => $certificate->id,
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => [
                        'certificate_number' => $certificate->certificate_number,
                        'download_url' => $certificate->certificate_url,
                    ],
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to download certificate', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to download certificate',
                ], 500);
            }
        }

        public function verify(string $code): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $certificate = $this->certificateService->verifyCertificate($code, $correlationId);

                if (!$certificate) {
                    return new \Illuminate\Http\JsonResponse([
                        'success' => false,
                        'message' => 'Invalid certificate code',
                    ], 404);
                }

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => [
                        'certificate_number' => $certificate->certificate_number,
                        'student_name' => $certificate->student_name,
                        'course_title' => $certificate->course->title,
                        'issued_at' => $certificate->issued_at,
                    ],
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to verify certificate', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to verify certificate',
                ], 500);
            }
        }
}
