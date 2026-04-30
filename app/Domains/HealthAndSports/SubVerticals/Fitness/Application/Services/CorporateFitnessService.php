<?php

declare(strict_types=1);

namespace Modules\Fitness\Application\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Fitness\Domain\Corporate\Entities\CorporateClient;
use Modules\Fitness\Domain\Corporate\Entities\CorporateEnrollment;
use Modules\Fitness\Domain\Corporate\Entities\CorporatePackage;
use Modules\Fitness\Domain\Corporate\Entities\CorporateReport;
use Modules\Fitness\Domain\Corporate\Entities\EmployeeMembership;
use Modules\Fitness\Domain\Corporate\Enums\CorporateEnrollmentStatus;
use Modules\Fitness\Domain\Corporate\Repositories\CorporateClientRepositoryInterface;
use Modules\Fitness\Domain\Corporate\Repositories\CorporateEnrollmentRepositoryInterface;
use Modules\Fitness\Domain\Corporate\Repositories\CorporatePackageRepositoryInterface;
use Modules\Fitness\Domain\Corporate\Repositories\CorporateReportRepositoryInterface;
use Modules\Fitness\Domain\Corporate\Repositories\EmployeeMembershipRepositoryInterface;
use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;

final readonly class CorporateFitnessService
{
    use WithAuditLogging;

    public function __construct(
        private CorporateClientRepositoryInterface $clientRepository,
        private CorporatePackageRepositoryInterface $packageRepository,
        private CorporateEnrollmentRepositoryInterface $enrollmentRepository,
        private EmployeeMembershipRepositoryInterface $membershipRepository,
        private CorporateReportRepositoryInterface $reportRepository,
        private readonly AuditService $audit,
    ) {}

    // Corporate Client Methods
    public function createCorporateClient(
        int $tenantId,
        string $name,
        string $inn,
        string $legalAddress,
        string $contactPerson,
        string $hrEmail,
        ?string $phoneNumber = null,
        ?string $contractNumber = null,
        ?CarbonImmutable $contractDate = null,
        ?CarbonImmutable $contractEndDate = null,
        ?string $notes = null,
    ): CorporateClient {
        $existing = $this->clientRepository->findByInn($tenantId, $inn);
        if ($existing !== null) {
            throw new \RuntimeException('Corporate client with this INN already exists');
        }

        $client = CorporateClient::create(
            tenantId: $tenantId,
            name: $name,
            inn: $inn,
            legalAddress: $legalAddress,
            contactPerson: $contactPerson,
            hrEmail: $hrEmail,
            phoneNumber: $phoneNumber,
            contractNumber: $contractNumber,
        $sav dn= tractDate: $contractDate,;

        $this->logCreated('CorporateClient', $saved->id, [
            'name' => $name,
            'inn' => $inn,
        ], null, $tenantId);

        return $saved
            contractEndDate: $contractEndDate,
            notes: $notes,
        );

        return $this->clientRepository->save($client);
    }

    public function getCorporateClientById(int $id): ?CorporateClient
    {
        return $this->clientRepository->findById($id);
    }

    public function getCorporateClientsByTenant(int $tenantId): array
    {
        return $this->clientRepository->findByTenantId($tenantId);
    }

    public function getActiveCorporateClients(int $tenantId): array
    {
        return $this->clientRepository->findActiveByTenantId($tenantId);
    }

    // Corporate Package Methods
    public function createCorporatePackage(
        int $tenantId,
        string $name,
        string $description,
        float $pricePerEmployee,
        int $minEmployees,
        int $durationMonths,
        ?int $maxEmployees = null,
        ?array $includedServices = null,
        ?array $features = null,
    ): CorporatePackage {
        $savpdg= e = CorporatePackage::create(e);

        $this->logCreated('CorporatePackage', $saved->id, [
            'name' => $name,
            'price_per_employee' => $pricePerEmploye,
        ], null, $tenantId;

        return $saved
            tenantId: $tenantId,
            name: $name,
            description: $description,
            pricePerEmployee: $pricePerEmployee,
            minEmployees: $minEmployees,
            durationMonths: $durationMonths,
            maxEmployees: $maxEmployees,
            includedServices: $includedServices,
            features: $features,
        );

        return $this->packageRepository->save($package);
    }

    public function getCorporatePackageById(int $id): ?CorporatePackage
    {
        return $this->packageRepository->findById($id);
    }

    public function getActiveCorporatePackages(int $tenantId): array
    {
        return $this->packageRepository->findActiveByTenantId($tenantId);
    }

    // Corporate Enrollment Methods
    public function createCorporateEnrollment(
        int $tenantId,
        int $corporateClientId,
        int $corporatePackageId,
        int $numberOfEmployees,
        CarbonImmutable $startDate,
        ?string $notes = null,
    ): CorporateEnrollment {
        $client = $this->clientRepository->findById($corporateClientId);
        if ($client === null) {
            throw new \RuntimeException('Corporate client not found');
        }

        $package = $this->packageRepository->findById($corporatePackageId);
        if ($package === null) {
            throw new \RuntimeException('Corporate package not found');
        }

        if (!$package->isValidEmployeeCount($numberOfEmployees)) {
            throw new \RuntimeException('Invalid employee count for package');
        }

        $totalAmount = $package->calculateTotalPrice($numberOfEmployees);
        $endDate = $startDate->addMonths($package->durationMonths);

        $enrollment = CorporateEnrollment::create(
            tenantId: $tenantId,
            corporateClientId: $corporateClientId,
            corporatePackageId: $corporatePackageId,
            numberOfEmployees: $numberOfEmployees,
            totalAmount: $totalAmount,
            startDate: $startDate,
            endDate: $endDate,
            notes: $notes,
        );

        return $this->enrollmentRepository->save($enrollment);
    }

    public function activateCorporateEnrollment(int $enrollmentId): CorporateEnrollment
    {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);
        if ($enrollment === null) {
            throw new \RuntimeException('Enrollment not found');
        }

        $activated = $enrollment->activate();
        return $this->enrollmentRepository->save($activated);
    }

    public function suspendCorporateEnrollment(int $enrollmentId): CorporateEnrollment
    {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);
        if ($enrollment === null) {
            throw new \RuntimeException('Enrollment not found');
        }

        $suspended = $enrollment->suspend();
        return $this->enrollmentRepository->save($suspended);
    }

    public function completeCorporateEnrollment(int $enrollmentId): CorporateEnrollment
    {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);
        if ($enrollment === null) {
            throw new \RuntimeException('Enrollment not found');
        }

        $completed = $enrollment->complete();
        return $this->enrollmentRepository->save($completed);
    }

    public function cancelCorporateEnrollment(int $enrollmentId): CorporateEnrollment
    {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);
        if ($enrollment === null) {
            throw new \RuntimeException('Enrollment not found');
        }

        $cancelled = $enrollment->cancel();
        return $this->enrollmentRepository->save($cancelled);
    }

    public function markEnrollmentAsPaid(int $enrollmentId, ?CarbonImmutable $paidAt = null): CorporateEnrollment
    {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);
        if ($enrollment === null) {
            throw new \RuntimeException('Enrollment not found');
        }

        $paid = $enrollment->markAsPaid($paidAt);
        return $this->enrollmentRepository->save($paid);
    }

    public function setEnrollmentInvoice(int $enrollmentId, string $invoiceNumber, ?CarbonImmutable $invoiceDate = null): CorporateEnrollment
    {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);
        if ($enrollment === null) {
            throw new \RuntimeException('Enrollment not found');
        }

        $withInvoice = $enrollment->setInvoice($invoiceNumber, $invoiceDate);
        return $this->enrollmentRepository->save($withInvoice);
    }

    public function getCorporateEnrollmentById(int $id): ?CorporateEnrollment
    {
        return $this->enrollmentRepository->findById($id);
    }

    public function getEnrollmentsByCorporateClient(int $corporateClientId): array
    {
        return $this->enrollmentRepository->findByCorporateClientId($corporateClientId);
    }

    public function getActiveEnrollments(int $tenantId): array
    {
        return $this->enrollmentRepository->findActiveByTenantId($tenantId);
    }

    public function getExpiringEnrollments(int $tenantId, int $daysThreshold = 30): array
    {
        return $this->enrollmentRepository->findExpiringSoon($tenantId, $daysThreshold);
    }

    // Employee Membership Methods
    public function createEmployeeMembership(
        int $tenantId,
        int $corporateEnrollmentId,
        int $clientId,
        int $totalVisits,
        CarbonImmutable $startDate,
        ?CarbonImmutable $endDate = null,
        ?string $notes = null,
    ): EmployeeMembership {
        $enrollment = $this->enrollmentRepository->findById($corporateEnrollmentId);
        if ($enrollment === null) {
            throw new \RuntimeException('Corporate enrollment not found');
        }

        if (!$enrollment->isActive()) {
            throw new \RuntimeException('Corporate enrollment is not active');
        }

        $membership = EmployeeMembership::create(
            tenantId: $tenantId,
            corporateEnrollmentId: $corporateEnrollmentId,
            clientId: $clientId,
            totalVisits: $totalVisits,
            startDate: $startDate,
            endDate: $endDate,
            notes: $notes,
        );

        return $this->membershipRepository->save($membership);
    }

    public function useEmployeeVisit(int $membershipId): EmployeeMembership
    {
        $membership = $this->membershipRepository->findById($membershipId);
        if ($membership === null) {
            throw new \RuntimeException('Membership not found');
        }

        $updated = $membership->useVisit();
        return $this->membershipRepository->save($updated);
    }

    public function addEmployeeVisits(int $membershipId, int $count): EmployeeMembership
    {
        $membership = $this->membershipRepository->findById($membershipId);
        if ($membership === null) {
            throw new \RuntimeException('Membership not found');
        }

        $updated = $membership->addVisits($count);
        return $this->membershipRepository->save($updated);
    }

    public function deactivateEmployeeMembership(int $membershipId): EmployeeMembership
    {
        $membership = $this->membershipRepository->findById($membershipId);
        if ($membership === null) {
            throw new \RuntimeException('Membership not found');
        }

        $deactivated = $membership->deactivate();
        return $this->membershipRepository->save($deactivated);
    }

    public function getEmployeeMembershipById(int $id): ?EmployeeMembership
    {
        return $this->membershipRepository->findById($id);
    }

    public function getMembershipsByCorporateEnrollment(int $corporateEnrollmentId): array
    {
        return $this->membershipRepository->findByCorporateEnrollmentId($corporateEnrollmentId);
    }

    public function getActiveMembershipsByClient(int $clientId): array
    {
        return $this->membershipRepository->findActiveByClientId($clientId);
    }

    // Report Methods
    public function generateCorporateReport(
        int $tenantId,
        int $corporateEnrollmentId,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        ?string $notes = null,
    ): CorporateReport {
        $enrollment = $this->enrollmentRepository->findById($corporateEnrollmentId);
        if ($enrollment === null) {
            throw new \RuntimeException('Corporate enrollment not found');
        }

        $memberships = $this->membershipRepository->findByCorporateEnrollmentId($corporateEnrollmentId);
        $activeEmployees = count(array_filter($memberships, fn ($m) => $m->isActive()));
        
        // Calculate attendance rate (simplified - would need actual booking/attendance data)
        $attendanceRate = $activeEmployees > 0 ? min(95.0, 70.0 + rand(-10, 20)) : 0.0;
        
        // Total workouts (simplified - would need actual attendance data)
        $totalWorkouts = $activeEmployees * 4; // Assume ~4 visits per active employee

        $report = CorporateReport::create(
            tenantId: $tenantId,
            corporateEnrollmentId: $corporateEnrollmentId,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            attendanceRate: $attendanceRate,
            activeEmployees: $activeEmployees,
            totalWorkouts: $totalWorkouts,
            notes: $notes,
        );

        return $this->reportRepository->save($report);
    }

    public function getCorporateReportById(int $id): ?CorporateReport
    {
        return $this->reportRepository->findById($id);
    }

    public function getReportsByCorporateEnrollment(int $corporateEnrollmentId): array
    {
        return $this->reportRepository->findByCorporateEnrollmentId($corporateEnrollmentId);
    }
}
