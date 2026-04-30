<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Log\LogManager;
use Modules\BeautyMasters\Domain\Entities\BeautyCertification;
use Modules\BeautyMasters\Domain\Entities\BeautyDevelopmentPlan;
use Modules\BeautyMasters\Domain\Entities\BeautySpecialization;
use Modules\BeautyMasters\Domain\Entities\CertificationLevel;
use Modules\BeautyMasters\Domain\Entities\CertificationStatus;
use Modules\BeautyMasters\Domain\Entities\CertificationTestResult;
use Modules\BeautyMasters\Domain\Entities\CertificationType;
use Modules\BeautyMasters\Domain\Entities\SpecializationLevel;
use Modules\BeautyMasters\Domain\Repositories\BeautyCertificationRepositoryInterface;
use Modules\BeautyMasters\Domain\Repositories\BeautyDevelopmentPlanRepositoryInterface;
use Modules\BeautyMasters\Domain\Repositories\BeautySpecializationRepositoryInterface;
use Modules\BeautyMasters\Domain\Repositories\CertificationTestResultRepositoryInterface;
use Modules\BeautyMasters\Domain\Repositories\MasterRepositoryInterface;
use Modules\BeautyMasters\Domain\Events\CertificationExpiringSoon;
use Modules\BeautyMasters\Domain\Events\CertificationExpired;
use Modules\BeautyMasters\Domain\Events\MasterBlockedDueToExpiredCertification;

final readonly class BeautyCertificationService
{
    use WithAuditLogging;

    public function __construct(
        private BeautyCertificationRepositoryInterface $certificationRepository,
        private BeautySpecializationRepositoryInterface $specializationRepository,
        private BeautyDevelopmentPlanRepositoryInterface $developmentPlanRepository,
        private CertificationTestResultRepositoryInterface $testResultRepository,
        private MasterRepositoryInterface $masterRepository,
        private readonly Repository $cache,
        private readonly LogManager $logger,
        private readonly AuditService $auditService,
    ) {
    }

    public function verifyCertification(int $masterId, string $specialization): bool
    {
        $cacheKey = "beauty_certification:{$masterId}:{$specialization}";

        return $this->cache->remember($cacheKey, now()->addHours(1), function () use ($masterId, $specialization) {
            $certification = $this->certificationRepository->findActiveByMasterIdAndSpecialization($masterId, $specialization);

            if ($certification === null) {
                return false;
            }

            return $certification->isValid();
        });
    }

    public function checkExpiringCertificates(): void
    {
        $expiringSoon = $this->certificationRepository->findExpiringSoon(60);
        
        foreach ($expiringSoon as $certification) {
            $daysUntilExpiry = (int) $certification->expiryDate->diff(new \DateTimeImmutable())->format('%r%a');
            
            if ($daysUntilExpiry <= 60 && $daysUntilExpiry > 30) {
                event(new CertificationExpiringSoon(
                    masterId: $certification->masterId,
                    vertical: 'beauty',
                    certificationName: $certification->name,
                    expiryDate: $certification->expiryDate,
                    daysUntilExpiry: $daysUntilExpiry,
                ));
            } elseif ($daysUntilExpiry <= 30 && $daysUntilExpiry > 0) {
                event(new CertificationExpiringSoon(
                    masterId: $certification->masterId,
                    vertical: 'beauty',
                    certificationName: $certification->name,
                    expiryDate: $certification->expiryDate,
                    daysUntilExpiry: $daysUntilExpiry,
                ));
            }
        }

        $expired = $this->certificationRepository->findExpired();
        foreach ($expired as $certification) {
            $this->markAsExpired($certification);
            event(new CertificationExpired(
                masterId: $certification->masterId,
                vertical: 'beauty',
                certificationName: $certification->name,
                expiryDate: $certification->expiryDate,
            ));
        }
    }

    public function generateDevelopmentPlan(int $masterId): BeautyDevelopmentPlan
    {
        $existingPlan = $this->developmentPlanRepository->findByMasterId($masterId);
        
        $certifications = $this->certificationRepository->findByMasterId($masterId);
        $specializations = $this->specializationRepository->findByMasterId($masterId);
        
        $requiredCourses = $this->calculateRequiredCourses($certifications, $specializations);
        $nextCertificationDate = $this->calculateNextCertificationDate($certifications);
        
        $plan = new BeautyDevelopmentPlan(
            id: $existingPlan?->id ?? 0,
            masterId: $masterId,
            requiredCourses: $requiredCourses,
            progressPercent: $existingPlan?->progressPercent ?? 0,
            nextCertificationDate: $nextCertificationDate,
            mentorNotes: $existingPlan?->mentorNotes,
            createdAt: $existingPlan?->createdAt ?? new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        return $this->developmentPlanRepository->save($plan);
    }

    public function assignSpecialization(int $masterId, string $specialization, SpecializationLevel $level): BeautySpecialization
    {
        $existing = $this->specializationRepository->findByMasterIdAndSpecialization($masterId, $specialization);
        
        $specializationEntity = new BeautySpecialization(
            id: $existing?->id ?? 0,
            masterId: $masterId,
            specialization: $specialization,
            level: $level,
            notes: $existing?->notes,
            createdAt: $existing?->createdAt ?? new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        $result = $this->specializationRepository->save($specializationEntity);
        
        Cache::forget("beauty_certification:{$masterId}:{$specialization}");
        
        return $result;
    }

    public function createInternalCertification(
        int $masterId,
        string $name,
        ?\DateTimeImmutable $expiryDate = null,
        ?CertificationLevel $awardedLevel = null
    ): BeautyCertification {
        $certification = new BeautyCertification(
            id: 0,
            masterId: $masterId,
            certificationType: CertificationType::INTERNAL,
            name: $name,
            issuer: 'CatCRM Beauty Certification',
            issueDate: new \DateTimeImmutable(),
            expiryDate: $expiryDate,
            certificateNumber: $this->generateCertificateNumber(),
            documentFile: null,
            status: CertificationStatus::ACTIVE,
            notes: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: null,
        );

        $result = $this->certificationRepository->save($certification);

        $this->clearCertificationCache($masterId);

        $this->logCreated('BeautyCertification', $result->id, [
            'master_id' => $masterId,
            'name' => $name,
            'type' => CertificationType::INTERNAL->value,
        ]);

        return $result;
    }

    public function recordTestResult(
        int $masterId,
        string $testName,
        int $theoryScore,
        int $practiceScore,
        ?array $practicalWorkPhotos = null,
        ?string $feedback = null
    ): CertificationTestResult {
        $totalScore = (int) (($theoryScore + $practiceScore) / 2);
        $passed = $totalScore >= 70;
        
        $awardedLevel = $this->calculateAwardedLevel($totalScore);

        $result = new CertificationTestResult(
            id: 0,
            masterId: $masterId,
            testName: $testName,
            theoryScore: $theoryScore,
            practiceScore: $practiceScore,
            totalScore: $totalScore,
            passed: $passed,
            awardedLevel: $awardedLevel,
            practicalWorkPhotos: $practicalWorkPhotos,
            feedback: $feedback,
            testDate: new \DateTimeImmutable(),
            createdAt: new \DateTimeImmutable(),
        );

        return $this->testResultRepository->save($result);
    }

    public function blockMasterForExpiredCertification(int $masterId, string $specialization): void
    {
        $certification = $this->certificationRepository->findActiveByMasterIdAndSpecialization($masterId, $specialization);

        if ($certification === null || !$certification->isValid()) {
            $master = $this->masterRepository->findById($masterId);

            if ($master !== null && $master->isActive) {
                event(new MasterBlockedDueToExpiredCertification(
                    masterId: $masterId,
                    vertical: 'beauty',
                    specialization: $specialization,
                    certificationName: $certification?->name ?? 'No certification',
                ));

                $this->logger->warning("Master {$masterId} blocked for service {$specialization} due to expired certification");

                $this->logAction('master_blocked_expired_cert', 'BeautySpecialization', null, [
                    'master_id' => $masterId,
                    'specialization' => $specialization,
                ]);
            }
        }
    }

    private function markAsExpired(BeautyCertification $certification): void
    {
        $expiredCertification = new BeautyCertification(
            id: $certification->id,
            masterId: $certification->masterId,
            certificationType: $certification->certificationType,
            name: $certification->name,
            issuer: $certification->issuer,
            issueDate: $certification->issueDate,
            expiryDate: $certification->expiryDate,
            certificateNumber: $certification->certificateNumber,
            documentFile: $certification->documentFile,
            status: CertificationStatus::EXPIRED,
            notes: $certification->notes,
            createdAt: $certification->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );

        $this->certificationRepository->save($expiredCertification);
        $this->clearCertificationCache($certification->masterId);
    }

    private function calculateRequiredCourses($certifications, $specializations): array
    {
        $courses = [];
        
        $specializationNames = $specializations->map(fn ($s) => $s->specialization)->toArray();
        
        $requiredCoursesMap = [
            'Маникюр' => ['Базовый маникюр', 'Гигиена и стерилизация', 'Дизайн ногтей'],
            'Педикюр' => ['Медицинский педикюр', 'Инструментальный педикюр', 'SPA-педикюр'],
            'Косметология' => ['Базовая косметология', 'Химические пилинги', 'Массаж лица'],
            'Визаж' => ['Базовый макияж', 'Цветотип внешности', 'Дневной и вечерний макияж'],
        ];

        foreach ($specializationNames as $specialization) {
            if (isset($requiredCoursesMap[$specialization])) {
                $courses[$specialization] = $requiredCoursesMap[$specialization];
            }
        }

        return $courses;
    }

    private function calculateNextCertificationDate($certifications): ?\DateTimeImmutable
    {
        $nearestDate = null;
        
        foreach ($certifications as $certification) {
            if ($certification->expiryDate !== null) {
                if ($nearestDate === null || $certification->expiryDate < $nearestDate) {
    priv te function clearCertificationCache(int $masterId): void
                $nearestDate = $certification->expiryDate;
                }
            }
        }

    }
 $nearestDate;
    }

    private function calculateAwardedLevel(int $totalScore): ?CertificationLevel
    {
        return match (true) {
            $totalScore >= 95 => CertificationLevel::EXPERT,
            $totalScore >= 85 => CertificationLevel::MASTER,
            $totalScore >= 75 => CertificationLevel::ADVANCED,
            $totalScore >= 70 => CertificationLevel::CERTIFIED,
            default => CertificationLevel::JUNIOR,
        };
    }

a       {
        $specializations = $this->specializationRepository->findByMasterId($masterId);
        foreach ($specializations as $specialization) {
            Cache::forget("beauty_certification:{$masterId}:{$specialization->specialization}");
        }
    }$this->c->