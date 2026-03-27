<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Http\Controllers;

use App\Domains\Education\Courses\Models\Certificate;
use App\Domains\Education\Courses\Services\CertificateService;
use App\Domains\Education\Courses\Jobs\CertificateGenerationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class CertificateController
{
    public function __construct(
        private readonly CertificateService $certificateService,
    ) {}

    public function show(int $id): JsonResponse
    {
        try {
            $certificate = Certificate::with(['enrollment', 'course'])
                ->findOrFail($id);

            $this->authorize('view', $certificate);

            return response()->json([
                'success' => true,
                'data' => $certificate,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to show certificate', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found',
            ], 404);
        }
    }

    public function myCertificates(): JsonResponse
    {
        try {
            $certificates = Certificate::where('student_id', auth()->id())
                ->with(['course'])
                ->orderByDesc('issued_at')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $certificates,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to list my certificates', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
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
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate not yet generated',
                ], 400);
            }

            \Log::channel('audit')->info('Certificate downloaded', [
                'certificate_id' => $certificate->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'certificate_number' => $certificate->certificate_number,
                    'download_url' => $certificate->certificate_url,
                ],
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to download certificate', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
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
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid certificate code',
                ], 404);
            }

            return response()->json([
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
            \Log::channel('audit')->error('Failed to verify certificate', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify certificate',
            ], 500);
        }
    }
}
