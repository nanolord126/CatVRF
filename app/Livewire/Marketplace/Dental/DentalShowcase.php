<?php

declare(strict_types=1);

namespace App\Livewire\Marketplace\Dental;

use App\Domains\Dental\Services\DentalClinicService;
use App\Domains\Dental\Services\DentalAppointmentService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

final class DentalShowcase extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedSpecialization = '';
    public float $lat = 55.7558;
    public float $lon = 37.6173;
    public int $radius = 15;
    public bool $isEmergencyOnly = false;
    
    public ?int $selectedClinicId = null;
    public array $appointmentData = [
        'dentist_id' => null,
        'service_id' => null,
        'date' => null,
        'time' => null,
        'patient_name' => '',
        'patient_phone' => '',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedSpecialization' => ['except' => ''],
    ];

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
            $service = app(DentalAppointmentService::class);
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
            Log::channel('audit')->error('Marketplace booking failed', [
                'error' => $e->getMessage(),
                'clinic_id' => $this->selectedClinicId
            ]);

            $this->addError('booking', 'Ошибка при бронировании: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $clinicService = app(DentalClinicService::class);
        
        $clinics = $clinicService->getNearbyClinics(
            $this->lat,
            $this->lon,
            $this->radius
        )->filter(function($clinic) {
            if ($this->search && !Str::contains(Str::lower($clinic->name), Str::lower($this->search))) {
                return false;
            }
            if ($this->isEmergencyOnly && !($clinic->metadata['emergency'] ?? false)) {
                return false;
            }
            return true;
        });

        return view('livewire.marketplace.dental.showcase', [
            'clinics' => $clinics,
        ])->layout('layouts.marketplace');
    }
}
