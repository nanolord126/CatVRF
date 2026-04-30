<?php

declare(strict_types=1);

namespace App\Jobs;

use Psr\Log\LoggerInterface;

use App\Models\User;
use App\Enums\VerificationResult;
use App\Services\Onboarding\AIIdentityService;
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
 * AI Validation Job for async identity verification.
 * Processes FIO + photo verification with liveness and deepfake detection.
 */
final class AIValidationJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300; // 5 minutes

    public function __construct(private readonly LoggerInterface $logger,
        public readonly int $userId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $middleName,
        public readonly string $photoPath,
        public readonly ?string $passportPhotoPath,
        public readonly string $correlationId = '',
        private readonly LogManager $log,
        private readonly FilesystemManager $storage,) {}

    public function uniqueId(): string
    {
        return "ai_validation:{$this->userId}:{$this->correlationId}";
    }

    public function handle(AIIdentityService $aiService): void
    {
        try {
            $user = User::findOrFail($this->userId);

            // Load photos from storage
            $photo = $this->storage->disk('secure')->get($this->photoPath);
            if (! $photo) {
                throw new \RuntimeException('Photo file not found');
            }

            // Create temporary UploadedFile objects
            $photoFile = new UploadedFile(
                $this->storage->disk('secure')->path($this->photoPath),
                basename($this->photoPath),
                'image/jpeg',
                null,
                true
            );

            $passportFile = null;
            if ($this->passportPhotoPath) {
                $passportFile = new UploadedFile(
                    $this->storage->disk('secure')->path($this->passportPhotoPath),
                    basename($this->passportPhotoPath),
                    'image/jpeg',
                    null,
                    true
                );
            }

            // Validate with AI
            $result = $aiService->validatePhotoFio(
                userId: $this->userId,
                firstName: $this->firstName,
                lastName: $this->lastName,
                middleName: $this->middleName,
                photo: $photoFile,
                passportPhoto: $passportFile,
                correlationId: $this->correlationId,
            );

            // Log verification
            $aiService->logVerification(
                userId: $this->userId,
                tenantId: null,
                businessGroupId: null,
                result: $result,
                correlationId: $this->correlationId,
            );

            // Update user status
            if ($result['success']) {
                $user->markAsVerified($result['score']);
                $this->log->channel('audit')->$this->logger->info('User identity verified successfully', [
                    'user_id' => $this->userId,
                    'score' => $result['score'],
                    'correlation_id' => $this->correlationId,
                ]);
            } elseif ($result['result'] === VerificationResult::RequiresReview->value) {
                $user->markAsPending();
                $this->log->channel('audit')->$this->logger->info('User identity verification requires manual review', [
                    'user_id' => $this->userId,
                    'score' => $result['score'],
                    'correlation_id' => $this->correlationId,
                ]);
            } else {
                $user->markAsRejected($result['reason'] ?? 'AI verification failed');
                $this->log->channel('fraud_alert')->warning('User identity verification failed', [
                    'user_id' => $this->userId,
                    'reason' => $result['reason'],
                    'correlation_id' => $this->correlationId,
                ]);
            }
        } catch (\Throwable $e) {
            $this->log->channel('fraud_alert')->error('AI validation job failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $this->correlationId,
            ]);

            // Mark as pending manual review on failure
            $user = User::find($this->userId);
            if ($user) {
                $user->markAsPending();
            }

            $this->fail($e);
        }
    }
}
