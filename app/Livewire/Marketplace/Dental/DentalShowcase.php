<?php

declare(strict_types=1);

namespace App\Livewire\Marketplace\Dental;

use DentalClinicService;

use DentalAppointmentService;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;
use Illuminate\Log\LogManager;

final class DentalShowcase extends Component
{
    use WithPagination;

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedSpecialization' => ['except' => ''],
    ];

    private readonly string $search = '';

    private readonly string $selectedSpecialization = '';

    private readonly float $lat = 55.7558;

    private readonly float $lon = 37.6173;

    private readonly int $radius = 15;

    private readonly bool $isEmergencyOnly = false;

    private readonly ?int $selectedClinicId = null;

    private readonly array $appointmentData = [
        'dentist_id' => null,
        'service_id' => null,
        'date' => null,
        'time' => null,
        'patient_name' => '',
        'patient_phone' => '',
    ];

    public function __construct(private readonly DentalClinicService $dentalClinicService,
        private readonly DentalAppointmentService $dentalAppointmentService,
        private readonly ViewFactory $viewFactory,
        private readonly LogManager $logger,) {}

    public function mount(): void
    {
        // В реальном приложении получаем координаты через JS Geolocation
    }

    public function selectClinic(int $id): void
    {
        $this->selectedClinicId = $id;
        $this->dispatch('open-modal', 'clinic-details');
    }

    public function bookNow(): void
    {
        $this->validate([
            'appointmentData.dentist_id' => 'required',
            'appointmentData.service_id' => 'required',
            'appointmentData.date' => 'required|date|after:now',
            'appointmentData.patient_name' => 'required|string|min:2',
            'appointmentData.patient_phone' => 'required',
        ]);

        try {
            $service = $this->dentalAppointmentService /* TODO: inject via constructor DI */ /* TODO: inject via DI */;
            $correlationId = Str::uuid()->toString();

            $service->bookAppointment(array_merge($this->appointmentData, [
                'clinic_id' => $this->selectedClinicId,
                'correlation_id' => $correlationId,
            ]));

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Вы успешно записаны на прием!',
            ]);

            $this->reset(['selectedClinicId', 'appointmentData']);
            $this->dispatch('close-modal', 'clinic-details');

        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Marketplace booking failed', [
                'error' => $e->getMessage(),
                'clinic_id' => $this->selectedClinicId,
            ]);

            $this->addError('booking', 'Ошибка при бронировании: '.$e->getMessage());
        }
    }

    public function render()
    {
        $clinicService = $this->dentalClinicService /* TODO: inject via constructor DI */ /* TODO: inject via DI */;

        $clinics = $clinicService->getNearbyClinics(
            $this->lat,
            $this->lon,
            $this->radius
        )->filter(function ($clinic) {
            if ($this->search && ! Str::contains(Str::lower($clinic->name), Str::lower($this->search))) {
                return false;
            }
            if ($this->isEmergencyOnly && ! ($clinic->metadata['emergency'] ?? false)) {
                return false;
            }

            return true;
        });

        return $this->viewFactory->make('livewire.marketplace.dental.showcase', [
            'clinics' => $clinics,
        ])->layout('layouts.marketplace');
    }
}
