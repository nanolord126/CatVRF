<?php declare(strict_types=1);

namespace App\Domains\Auto\Jobs;

use App\Domains\Auto\Events\CarImportDutiesPaidEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class VerifyImportDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly int $importId,
        public readonly string $correlationId,
    ) {}

    public function handle(): void
    {
        $import = DB::table('car_imports')
            ->where('id', $this->importId)
            ->lockForUpdate()
            ->first();

        if ($import === null) {
            Log::channel('audit')->error('car.import.documents.verify.failed', [
                'error' => 'Import not found',
                'import_id' => $this->importId,
                'correlation_id' => $this->correlationId,
            ]);
            return;
        }

        $documents = json_decode($import->documents, true) ?? [];
        $verificationResults = [];

        foreach ($documents as $index => $doc) {
            $verificationResults[$index] = $this->verifyDocument($doc);
        }

        $allVerified = collect($verificationResults)->every(fn($result) => $result['status'] === 'verified');

        if ($allVerified) {
            DB::table('car_imports')
                ->where('id', $this->importId)
                ->update([
                    'status' => 'customs_processing',
                    'metadata' => array_merge(
                        json_decode($import->metadata ?? '{}', true),
                        [
                            'documents_verified_at' => now()->toIso8601String(),
                            'document_verification_results' => $verificationResults,
                        ]
                    ),
                    'updated_at' => now(),
                ]);

            Log::channel('audit')->info('car.import.documents.verified', [
                'import_id' => $this->importId,
                'correlation_id' => $this->correlationId,
            ]);
        } else {
            DB::table('car_imports')
                ->where('id', $this->importId)
                ->update([
                    'status' => 'document_rejected',
                    'metadata' => array_merge(
                        json_decode($import->metadata ?? '{}', true),
                        [
                            'document_verification_failed_at' => now()->toIso8601String(),
                            'document_verification_results' => $verificationResults,
                        ]
                    ),
                    'updated_at' => now(),
                ]);

            Log::channel('audit')->warning('car.import.documents.rejected', [
                'import_id' => $this->importId,
                'correlation_id' => $this->correlationId,
                'reason' => 'Document verification failed',
            ]);
        }
    }

    private function verifyDocument(array $document): array
    {
        $filePath = $document['path'] ?? '';
        
        if (!Storage::disk('public')->exists($filePath)) {
            return [
                'status' => 'rejected',
                'reason' => 'File not found',
            ];
        }

        $fileSize = Storage::disk('public')->size($filePath);
        
        if ($fileSize > 10 * 1024 * 1024) {
            return [
                'status' => 'rejected',
                'reason' => 'File size exceeds 10MB',
            ];
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        if (!in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            return [
                'status' => 'rejected',
                'reason' => 'Invalid file format',
            ];
        }

        return [
            'status' => 'verified',
            'name' => $document['name'] ?? '',
            'size' => $fileSize,
            'verified_at' => now()->toIso8601String(),
        ];
    }
}
