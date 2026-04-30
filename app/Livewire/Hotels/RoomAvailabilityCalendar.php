<?php

declare(strict_types=1);

namespace App\Livewire\Hotels;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Carbon\CarbonImmutable;

use Livewire\Component;

/**
 * Class RoomAvailabilityCalendar
 *
 * Livewire component for user cabinet.
 * Personal cabinets use Livewire 3 + Alpine.js + Tailwind 4.
 * Not Filament — Filament is for admin/tenant/B2B panels only.
 */
final class RoomAvailabilityCalendar extends Component
{
    private readonly int $hotelId;

    private readonly string $startDate;

    private readonly string $endDate;

    private readonly array $availableRooms = [];

    private readonly int $selectedRoomId = 0;

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function mount(int $hotelId): void
    {
        $this->hotelId = $hotelId;
        $this->startDate = CarbonImmutable::now()->toDateString();
        $this->endDate = CarbonImmutable::now()->addDays(7)->toDateString();
    }

    public function checkAvailability(): void
    {
        $this->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after:startDate',
        ]);

        // In real app, query database
        $this->availableRooms = [
            ['id' => 1, 'type' => 'Standard', 'price' => 250000, 'available' => true],
            ['id' => 2, 'type' => 'Deluxe', 'price' => 450000, 'available' => true],
            ['id' => 3, 'type' => 'Suite', 'price' => 850000, 'available' => false],
        ];
    }

    public function selectRoom(int $roomId): void
    {
        $this->selectedRoomId = $roomId;
        session()->put('booking_room', [
            'hotel_id' => $this->hotelId,
            'room_id' => $roomId,
            'check_in' => $this->startDate,
            'check_out' => $this->endDate,
        ]);
        $this->dispatch('room-selected', roomId: $roomId);
    }

    public function render(): View
    {
        return $this->viewFactory->make('livewire.hotels.room-availability-calendar');
    }
}
