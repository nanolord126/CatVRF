<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Livewire;

use Livewire\Component;
use Modules\BeautyMasters\Application\Services\AppointmentService;
use Modules\BeautyMasters\Infrastructure\Models\MasterModel;
use Modules\BeautyMasters\Infrastructure\Models\ServiceModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

final class ClientBookingCalendar extends Component
{
    public int $venueId;
    public ?int $selectedMasterId = null;
    public ?int $selectedServiceId = null;
    public string $selectedDate;
    public array $availableSlots = [];
    public array $masters = [];
    public array $services = [];
    public bool $showBookingModal = false;
    public ?array $selectedSlot = null;
    public string $clientPhone = '';
    public string $clientName = '';
    public string $clientNotes = '';

    protected $listeners = [
        'masterSelected' => 'selectMaster',
        'serviceSelected' => 'selectService',
    ];

    public function mount(int $venueId): void
    {
        $this->venueId = $venueId;
        $this->selectedDate = now()->format('Y-m-d');
        $this->loadMasters();
        $this->loadServices();
        $this->loadAvailableSlots();
    }

    public function loadMasters(): void
    {
        $this->masters = MasterModel::where('venue_id', $this->venueId)
            ->where('is_active', true)
            ->get()
            ->map(fn ($master) => [
                'id' => $master->id,
                'name' => $master->full_name,
                'avatar' => $master->avatar,
                'rating' => $master->rating,
                'specializations' => $master->specializations,
            ])
            ->toArray();
    }

    public function loadServices(): void
    {
        $query = ServiceModel::where('venue_id', $this->venueId)
            ->where('is_active', true);

        if ($this->selectedMasterId) {
            // In a real implementation, you might filter services by master specializations
        }

        $this->services = $query
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'duration' => $service->duration_minutes,
                'price' => $service->final_price,
                'description' => $service->description,
            ])
            ->toArray();
    }

    public function loadAvailableSlots(): void
    {
        if (!$this->selectedMasterId || !$this->selectedServiceId || !$this->selectedDate) {
            $this->availableSlots = [];
            return;
        }

        $cacheKey = "beauty:client_booking:{$this->venueId}:{$this->selectedMasterId}:{$this->selectedServiceId}:{$this->selectedDate}";

        $this->availableSlots = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            $service = ServiceModel::find($this->selectedServiceId);
            if (!$service) {
                return [];
            }

            $appointmentService = app(AppointmentService::class);
            $date = Carbon::parse($this->selectedDate);

            try {
                $slots = $appointmentService->getAvailableSlots(
                    $this->selectedMasterId,
                    $this->selectedServiceId,
                    new \DateTimeImmutable($date->format('Y-m-d'))
                );

                return array_map(fn ($slot) => [
                    'start' => $slot['start_time'],
                    'end' => $slot['end_time'],
                    'price' => $service->final_price,
                ], $slots);
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    public function selectMaster(int $masterId): void
    {
        $this->selectedMasterId = $masterId;
        $this->loadAvailableSlots();
    }

    public function selectService(int $serviceId): void
    {
        $this->selectedServiceId = $serviceId;
        $this->loadAvailableSlots();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->loadAvailableSlots();
    }

    public function selectSlot(array $slot): void
    {
        $this->selectedSlot = $slot;
        $this->showBookingModal = true;
    }

    public function closeBookingModal(): void
    {
        $this->showBookingModal = false;
        $this->selectedSlot = null;
        $this->clientPhone = '';
        $this->clientName = '';
        $this->clientNotes = '';
    }

    public function bookAppointment(): void
    {
        $this->validate([
            'clientPhone' => 'required|string|min:10',
            'clientName' => 'required|string|min:2',
        ]);

        // Create appointment logic would go here
        // This would dispatch an event or call a service

        $this->closeBookingModal();
        $this->dispatch('appointmentBooked');
        $this->loadAvailableSlots(); // Refresh slots
    }

    public function getAvailableDates(): array
    {
        $dates = [];
        $startDate = now();
        $endDate = now()->addDays(14);

        for ($date = $startDate; $date <= $endDate; $date->addDay()) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }

    public function render()
    {
        return view('beauty-masters::livewire.client-booking-calendar', [
            'selectedDateFormatted' => Carbon::parse($this->selectedDate)->translatedFormat('d F Y'),
            'availableDates' => $this->getAvailableDates(),
        ]);
    }
}
