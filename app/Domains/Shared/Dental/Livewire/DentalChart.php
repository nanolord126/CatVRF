<?php

declare(strict_types=1);

namespace Modules\Dental\Livewire;

use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Modules\Dental\Application\Services\DentalChartService;
use Modules\Dental\Domain\Entities\ToothChart;
use Modules\Dental\Domain\Enums\ToothStatus as ToothStatusEnum;
use Modules\Dental\Domain\ValueObjects\PatientId;
use Modules\Dental\Domain\ValueObjects\ToothChartId;
use Modules\Dental\Domain\ValueObjects\DoctorId;

final class DentalChart extends Component
{
    #[Locked]
    public int $patientId;

    #[Locked]
    public ?int $doctorId = null;

    #[Reactive]
    public ?ToothChart $toothChart = null;

    public string $selectedTooth = '';
    public string $selectedStatus = 'healthy';
    public string $notes = '';
    public string $description = '';
    public bool $showModal = false;
    public string $numberingSystem = 'fdi';

    public function mount(int $patientId, ?int $doctorId = null): void
    {
        $this->patientId = $patientId;
        $this->doctorId = $doctorId;
        $this->loadChart();
    }

    public function loadChart(): void
    {
        $service = app(DentalChartService::class);
        $patientId = PatientId::fromInt($this->patientId);
        
        $chart = $service->getChartForPatient($patientId);
        
        if ($chart === null) {
            // Create a new chart if none exists
            $tenantId = auth()->user()?->tenant_id ?? 1;
            $doctorId = $this->doctorId ? DoctorId::fromInt($this->doctorId) : null;
            $chart = $service->createChart(
                patientId: $patientId,
                tenantId: \Modules\Dental\Domain\ValueObjects\TenantId::fromInt($tenantId),
                doctorId: $doctorId,
            );
            $chart = $service->initializeTeeth($chart->id);
        }

        $this->toothChart = $chart;
        $this->numberingSystem = $chart->numberingSystem->value;
    }

    public function selectTooth(string $toothNumber): void
    {
        $this->selectedTooth = $toothNumber;
        
        if ($this->toothChart) {
            $tooth = $this->toothChart->teeth->getByToothNumber($toothNumber);
            if ($tooth) {
                $this->selectedStatus = $tooth->status->value;
                $this->notes = $tooth->notes ?? '';
                $this->description = $tooth->description ?? '';
            } else {
                $this->selectedStatus = 'healthy';
                $this->notes = '';
                $this->description = '';
            }
        }
        
        $this->showModal = true;
    }

    public function saveToothStatus(): void
    {
        if (!$this->toothChart || !$this->selectedTooth) {
            return;
        }

        $service = app(DentalChartService::class);
        $doctorId = $this->doctorId ? DoctorId::fromInt($this->doctorId) : null;

        $service->updateToothStatus(
            chartId: $this->toothChart->id,
            toothNumber: $this->selectedTooth,
            status: ToothStatusEnum::from($this->selectedStatus),
            description: $this->description ?: null,
            notes: $this->notes ?: null,
            performedBy: $doctorId,
        );

        $this->loadChart();
        $this->showModal = false;
        $this->selectedTooth = '';
        $this->notes = '';
        $this->description = '';
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedTooth = '';
        $this->notes = '';
        $this->description = '';
    }

    public function getTeethProperty(): array
    {
        if (!$this->toothChart) {
            return [];
        }

        $teeth = [];
        foreach ($this->toothChart->teeth->all() as $tooth) {
            $teeth[$tooth->toothNumber] = [
                'status' => $tooth->status->value,
                'color' => $tooth->color,
                'label' => $tooth->status->getLabel(),
                'requiresAttention' => $tooth->status->requiresAttention(),
            ];
        }

        return $teeth;
    }

    public function getToothStatusesProperty(): array
    {
        return [
            'healthy' => ['label' => 'Здоровый', 'color' => '#22c55e'],
            'caries' => ['label' => 'Кариес', 'color' => '#f97316'],
            'filled' => ['label' => 'Пломбированный', 'color' => '#3b82f6'],
            'crown' => ['label' => 'Коронка', 'color' => '#8b5cf6'],
            'implant' => ['label' => 'Имплант', 'color' => '#1e3a8a'],
            'extracted' => ['label' => 'Удалённый', 'color' => '#ef4444'],
            'root_canal' => ['label' => 'Лечёные каналы', 'color' => '#eab308'],
            'mobility_1' => ['label' => 'Подвижность 1', 'color' => '#dc2626'],
            'mobility_2' => ['label' => 'Подвижность 2', 'color' => '#dc2626'],
            'mobility_3' => ['label' => 'Подвижность 3', 'color' => '#dc2626'],
            'missing' => ['label' => 'Отсутствует', 'color' => '#6b7280'],
            'impacted' => ['label' => 'Ретинированный', 'color' => '#f59e0b'],
            'supernumerary' => ['label' => 'Сверхкомплектный', 'color' => '#ec4899'],
        ];
    }

    public function getUpperJawProperty(): array
    {
        if ($this->numberingSystem === 'fdi') {
            return [
                ['18', '17', '16', '15', '14', '13', '12', '11'],
                ['21', '22', '23', '24', '25', '26', '27', '28'],
            ];
        }
        
        return [
            [1, 2, 3, 4, 5, 6, 7, 8],
            [9, 10, 11, 12, 13, 14, 15, 16],
        ];
    }

    public function getLowerJawProperty(): array
    {
        if ($this->numberingSystem === 'fdi') {
            return [
                ['48', '47', '46', '45', '44', '43', '42', '41'],
                ['31', '32', '33', '34', '35', '36', '37', '38'],
            ];
        }
        
        return [
            [32, 31, 30, 29, 28, 27, 26, 25],
            [17, 18, 19, 20, 21, 22, 23, 24],
        ];
    }

    public function render()
    {
        return view('dental::livewire.dental-chart');
    }
}
