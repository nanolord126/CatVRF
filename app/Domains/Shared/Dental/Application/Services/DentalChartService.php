<?php

declare(strict_types=1);

namespace Modules\Dental\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;
use Modules\Dental\Domain\Entities\ToothChart;
use Modules\Dental\Domain\Entities\ToothStatus;
use Modules\Dental\Domain\Enums\ToothStatus as ToothStatusEnum;
use Modules\Dental\Domain\Enums\NumberingSystem;
use Modules\Dental\Domain\Repositories\ToothChartRepositoryInterface;
use Modules\Dental\Domain\ValueObjects\PatientId;
use Modules\Dental\Domain\ValueObjects\DoctorId;
use Modules\Dental\Domain\ValueObjects\TenantId;
use Modules\Dental\Domain\ValueObjects\ToothChartId;
use Modules\Dental\Domain\ValueObjects\ToothStatusCollection;

final readonly class DentalChartService
{
    use WithAuditLogging;

    public function __construct(
        private ToothChartRepositoryInterface $toothChartRepository,
        private readonly AuditService $audit,
    ) {}

    public function createChart(
        PatientId $patientId,
        TenantId $tenantId,
        ?DoctorId $doctorId = null,
        NumberingSystem $numberingSystem = NumberingSystem::FDI,
        bool $isPrimary = true,
    ): ToothChart {
        $chart = ToothChart::create(
            patientId: $patientId,
            doctorId: $doctorId,
            tenantId: $tenantId,
            numberingSystem: $numberingSystem,
            isPrimary: $isPrimary,
        );

        $this->toothChartRepository->save($chart);

        $this->logCreated('ToothChart', $chart->id->value, [
            'patient_id' => $patientId->value,
            'doctor_id' => $doctorId?->value,
            'numbering_system' => $numberingSystem->value,
            'is_primary' => $isPrimary,
        ], null, $tenantId->value);

        return $chart;
    }

    public function updateToothStatus(
        ToothChartId $chartId,
        string $toothNumber,
        ToothStatusEnum $status,
        ?string $description = null,
        ?string $notes = null,
        ?DoctorId $performedBy = null,
    ): ToothChart {
        $chart = $this->toothChartRepository->findById($chartId);
        if ($chart === null) {
            throw new \InvalidArgumentException('Tooth chart not found');
        }

        $newToothStatus = ToothStatus::create(
            toothChartId: $chartId,
            toothNumber: $toothNumber,
            status: $status,
            description: $description,
            notes: $notes,
            performedBy: $performedBy,
        );

        $teeth = $chart->teeth;
        $teeth = $teeth->remove($toothNumber);
        $teeth = $teeth->add($newToothStatus);

        $updatedChart = $chart->withTeeth($teeth);
        $this->toothChartRepository->save($updatedChart);

        $this->logUpdated('ToothChart', $chartId->value, [
            'tooth_number' => $toothNumber,
            'status' => $status->value,
            'description' => $description,
            'performed_by' => $performedBy?->value,
        ], null, $chart->tenantId->value);

        return $updatedChart;
    }

    public function getChartForPatient(PatientId $patientId, bool $isPrimary = true): ?ToothChart
    {
        return $this->toothChartRepository->findByPatient($patientId, $isPrimary);
    }

    public function getChartById(ToothChartId $chartId): ?ToothChart
    {
        return $this->toothChartRepository->findById($chartId);
    }

    public function assignDoctor(ToothChartId $chartId, DoctorId $doctorId): ToothChart
    {
        $chart = $this->toothChartRepository->findById($chartId);
        if ($chart === null) {
            throw new \InvalidArgumentException('Tooth chart not found');
        }

        $updatedChart = $chart->withDoctor($doctorId);
        $this->toothChartRepository->save($updatedChart);

        $this->logUpdated('ToothChart', $chartId->value, [
            'doctor_id' => $doctorId->value,
            'action' => 'doctor_assigned',
        ], null, $chart->tenantId->value);

        return $updatedChart;
    }

    public function initializeTeeth(ToothChartId $chartId): ToothChart
    {
        $chart = $this->toothChartRepository->findById($chartId);
        if ($chart === null) {
            throw new \InvalidArgumentException('Tooth chart not found');
        }

        $teethRange = $chart->getTeethRange();
        $teeth = new ToothStatusCollection([]);

        foreach ($teethRange as $toothNumber) {
            $toothStatus = ToothStatus::create(
                toothChartId: $chartId,
                toothNumber: (string) $toothNumber,
                status: ToothStatusEnum::HEALTHY,
            );
            $teeth = $teeth->add($toothStatus);
        }

        $updatedChart = $chart->withTeeth($teeth);
        $this->toothChartRepository->save($updatedChart);

        $this->logAction('teeth_initialized', 'ToothChart', $chartId->value, [
            'teeth_count' => count($teethRange),
        ], null, $chart->tenantId->value);

        return $updatedChart;
    }

    public function exportToPdf(ToothChartId $chartId): string
    {
        $chart = $this->toothChartRepository->findById($chartId);
        if ($chart === null) {
            throw new \InvalidArgumentException('Tooth chart not found');
        }

        // PDF generation would be implemented here using a library like DomPDF or Snappy
        // For now, we'll return a placeholder
        $pdfPath = storage_path("app/dental-charts/{$chartId->value}.pdf");
        
        $this->logAction('dental_chart_exported_pdf', 'ToothChart', $chartId->value, [
            'path' => $pdfPath,
        ], null, $chart->tenantId->value);

        return $pdfPath;
    }
}
