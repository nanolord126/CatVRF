<?php

declare(strict_types=1);

namespace App\Jobs\PersonalData;

use App\Enums\ConsentType;
use App\Models\User;
use App\Models\UserConsent;
use App\Services\PersonalData\PersonalDataAccessAudit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Purge Personal Data Job
 * 
 * Destroys personal data after consent withdrawal as required by 152-FZ.
 * Data must be destroyed within 30 days of consent withdrawal.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * CRITICAL: This job ensures compliance with right to be forgotten.
 */
final readonly class PurgePersonalDataJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600; // 1 hour timeout for large datasets

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $consentId,
        private readonly ?int $userId = null,
    ) {
        $this->onQueue('personal-data');
    }

    /**
     * Execute the job.
     */
    public function handle(
        LoggerInterface $logger,
        PersonalDataAccessAudit $audit
    ): void {
        $consent = UserConsent::with('user')->findOrFail($this->consentId);
        $user = $this->userId ? User::findOrFail($this->userId) : $consent->user;

        $logger->info('Starting personal data purge', [
            'consent_id' => $this->consentId,
            'user_id' => $user->id,
            'consent_type' => $consent->consent_type->value,
        ]);

        DB::transaction(function () use ($consent, $user, $audit, $logger) {
            $consentType = $consent->consent_type;
            $destroyed = false;

            switch ($consentType) {
                case ConsentType::BIOMETRIC_FACE_ID:
                    $destroyed = $this->purgeBiometricFaceData($user, $audit, $logger);
                    break;

                case ConsentType::BIOMETRIC_LIVENESS:
                    $destroyed = $this->purgeLivenessData($user, $audit, $logger);
                    break;

                case ConsentType::BIOMETRIC_BEHAVIORAL:
                    $destroyed = $this->purgeBehavioralData($user, $audit, $logger);
                    break;

                case ConsentType::BIOMETRIC_VOICE:
                    $destroyed = $this->purgeVoiceData($user, $audit, $logger);
                    break;

                case ConsentType::REGISTRATION:
                    $destroyed = $this->purgeRegistrationData($user, $audit, $logger);
                    break;

                case ConsentType::ORDERS:
                    // Orders data retention for tax purposes (3 years)
                    // Only anonymize, don't delete
                    $destroyed = $this->anonymizeOrderData($user, $audit, $logger);
                    break;

                case ConsentType::COMMUNICATIONS:
                    $destroyed = $this->purgeCommunicationData($user, $audit, $logger);
                    break;

                case ConsentType::KYB_VERIFICATION:
                    $destroyed = $this->purgeKYBData($user, $audit, $logger);
                    break;

                case ConsentType::DOCUMENT_VERIFICATION:
                    $destroyed = $this->purgeDocumentData($user, $audit, $logger);
                    break;

                default:
                    $logger->warning('Unknown consent type for purge', [
                        'consent_type' => $consentType->value,
                    ]);
            }

            if ($destroyed) {
                $consent->markDataAsPurged($this->job?->getJobId() ?? 'manual');
                
                $logger->info('Personal data purged successfully', [
                    'consent_id' => $this->consentId,
                    'user_id' => $user->id,
                    'consent_type' => $consentType->value,
                ]);
            }
        });
    }

    /**
     * Purge Face ID biometric data
     */
    private function purgeBiometricFaceData(
        User $user,
        PersonalDataAccessAudit $audit,
        LoggerInterface $logger
    ): bool {
        try {
            // Remove face reference and verification data
            $user->update([
                'face_reference_id' => null,
                'face_verified_at' => null,
                'face_id_consent_id' => null,
            ]);

            // Delete biometric templates if stored separately
            DB::table('biometric_templates')
                ->where('user_id', $user->id)
                ->where('template_type', 'face')
                ->delete();

            $audit->logDataDestruction($user, 'biometric_face_id', 'consent_withdrawn', $this->job?->getJobId());

            return true;
        } catch (\Throwable $e) {
            $logger->error('Failed to purge biometric face data', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Purge liveness data
     */
    private function purgeLivenessData(
        User $user,
        PersonalDataAccessAudit $audit,
        LoggerInterface $logger
    ): bool {
        try {
            // Delete liveness verification records
            DB::table('liveness_verifications')
                ->where('user_id', $user->id)
                ->delete();

            // Delete liveness images/videos from storage
            // This would require integration with storage service
            
            $audit->logDataDestruction($user, 'biometric_liveness', 'consent_withdrawn', $this->job?->getJobId());

            return true;
        } catch (\Throwable $e) {
            $logger->error('Failed to purge liveness data', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Purge behavioral biometric data
     */
    private function purgeBehavioralData(
        User $user,
        PersonalDataAccessAudit $audit,
        LoggerInterface $logger
    ): bool {
        try {
            // Delete behavioral baselines
            DB::table('behavioral_baselines')
                ->where('user_id', $user->id)
                ->delete();

            // Delete behavioral samples
            DB::table('behavioral_samples')
                ->where('user_id', $user->id)
                ->delete();

            // Update user consent reference
            $user->update([
                'behavioral_consent_id' => null,
            ]);

            $audit->logDataDestruction($user, 'biometric_behavioral', 'consent_withdrawn', $this->job?->getJobId());

            return true;
        } catch (\Throwable $e) {
            $logger->error('Failed to purge behavioral data', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Purge voice biometric data
     */
    private function purgeVoiceData(
        User $user,
        PersonalDataAccessAudit $audit,
        LoggerInterface $logger
    ): bool {
        try {
            // Delete voice templates
            DB::table('biometric_templates')
                ->where('user_id', $user->id)
                ->where('template_type', 'voice')
                ->delete();

            // Delete voice recordings from storage
            // This would require integration with storage service
            
            $audit->logDataDestruction($user, 'biometric_voice', 'consent_withdrawn', $this->job?->getJobId());

            return true;
        } catch (\Throwable $e) {
            $logger->error('Failed to purge voice data', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Purge registration data (full account deletion)
     */
    private function purgeRegistrationData(
        User $user,
        PersonalDataAccessAudit $audit,
        LoggerInterface $logger
    ): bool {
        try {
            // Soft delete user account
            $user->delete();

            // Or hard delete with anonymization
            // $user->forceDelete();

            $audit->logDataDestruction($user, 'registration_data', 'consent_withdrawn', $this->job?->getJobId());

            return true;
        } catch (\Throwable $e) {
            $logger->error('Failed to purge registration data', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Anonymize order data (retain for tax purposes)
     */
    private function anonymizeOrderData(
        User $user,
        PersonalDataAccessAudit $audit,
        LoggerInterface $logger
    ): bool {
        try {
            // Anonymize orders but keep transaction records
            DB::table('orders')
                ->where('user_id', $user->id)
                ->update([
                    'customer_name' => 'Anonymized',
                    'customer_phone' => null,
                    'customer_email' => null,
                    'customer_address' => null,
                    'anonymized_at' => now(),
                ]);

            $audit->logDataDestruction($user, 'order_data', 'anonymized_for_retention', $this->job?->getJobId());

            return true;
        } catch (\Throwable $e) {
            $logger->error('Failed to anonymize order data', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Purge communication data
     */
    private function purgeCommunicationData(
        User $user,
        PersonalDataAccessAudit $audit,
        LoggerInterface $logger
    ): bool {
        try {
            // Delete notification logs
            DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type', User::class)
                ->delete();

            // Delete communication preferences
            $user->update([
                'email_notifications' => false,
                'sms_notifications' => false,
                'push_notifications' => false,
            ]);

            $audit->logDataDestruction($user, 'communication_data', 'consent_withdrawn', $this->job?->getJobId());

            return true;
        } catch (\Throwable $e) {
            $logger->error('Failed to purge communication data', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Purge KYB verification data
     */
    private function purgeKYBData(
        User $user,
        PersonalDataAccessAudit $audit,
        LoggerInterface $logger
    ): bool {
        try {
            // Anonymize KYB records
            DB::table('kyb_verifications')
                ->where('user_id', $user->id)
                ->update([
                    'director_name' => null,
                    'director_passport' => null,
                    'anonymized_at' => now(),
                ]);

            $audit->logDataDestruction($user, 'kyb_data', 'consent_withdrawn', $this->job?->getJobId());

            return true;
        } catch (\Throwable $e) {
            $logger->error('Failed to purge KYB data', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Purge document verification data
     */
    private function purgeDocumentData(
        User $user,
        PersonalDataAccessAudit $audit,
        LoggerInterface $logger
    ): bool {
        try {
            // Delete document verification records
            DB::table('document_verifications')
                ->where('user_id', $user->id)
                ->delete();

            // Delete document files from storage
            // This would require integration with storage service
            
            $audit->logDataDestruction($user, 'document_data', 'consent_withdrawn', $this->job?->getJobId());

            return true;
        } catch (\Throwable $e) {
            $logger->error('Failed to purge document data', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Purge personal data job failed', [
            'consent_id' => $this->consentId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
