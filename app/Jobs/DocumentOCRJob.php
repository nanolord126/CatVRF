<?php

declare(strict_types=1);

namespace App\Jobs;

use Psr\Log\LoggerInterface;

use App\Services\Onboarding\DocumentVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;

/**
 * Document OCR Job for async document verification.
 * Processes EGRUL, passport, and other documents with OCR.
 */
final class DocumentOCRJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300; // 5 minutes

    public function __construct(private readonly LoggerInterface $logger,
        public readonly ?int $userId,
        public readonly ?string $tenantId,
        public readonly ?int $businessGroupId,
        public readonly string $documentType,
        public readonly string $documentPath,
        public readonly ?string $expectedInn = null,
        public readonly string $correlationId = '',
        private readonly FilesystemManager $storage,
        private readonly LogManager $log,) {}

    public function uniqueId(): string
    {
        return "document_ocr:{$this->documentPath}:{$this->correlationId}";
    }

    public function handle(DocumentVerificationService $documentService): void
    {
        try {
            // Load document from storage
            $document = $this->storage->disk('secure')->get($this->documentPath);
            if (! $document) {
                throw new \RuntimeException('Document file not found');
            }

            // Create temporary UploadedFile object
            $documentFile = new UploadedFile(
                $this->storage->disk('secure')->path($this->documentPath),
                basename($this->documentPath),
                'application/pdf',
                null,
                true
            );

            // Verify document
            $result = match ($this->documentType) {
                'egrul' => $documentService->verifyEgrul($documentFile, $this->expectedInn ?? ''),
                'passport' => $documentService->verifyPassport($documentFile),
                default => throw new \DomainException("Unknown document type: {$this->documentType}"),
            };

            // Log verification
            $documentService->logVerification(
                userId: $this->userId,
                tenantId: $this->tenantId ? (int) $this->tenantId : null,
                businessGroupId: $this->businessGroupId,
                documentType: $this->documentType,
                result: $result,
                correlationId: $this->correlationId,
            );

            $this->log->channel('audit')->$this->logger->info('Document OCR completed', [
                'document_type' => $this->documentType,
                'user_id' => $this->userId,
                'tenant_id' => $this->tenantId,
                'business_group_id' => $this->businessGroupId,
                'success' => $result['success'],
                'score' => $result['score'],
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('fraud_alert')->error('Document OCR job failed', [
                'document_type' => $this->documentType,
                'user_id' => $this->userId,
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->fail($e);
        }
    }
}
