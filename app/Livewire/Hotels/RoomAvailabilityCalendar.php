<?php declare(strict_types=1);

namespace App\Livewire\Hotels;

use Livewire\Component;
use Illuminate\View\View;

final class RoomAvailabilityCalendar extends Component
{
    public int $hotelId;
    public string $startDate;
    public string $endDate;
    public array $availableRooms = [];
    public int $selectedRoomId = 0;

    public function mount(int $hotelId): void
    {
        $this->hotelId = $hotelId;
        $this->startDate = now()->toDateString();
        $this->endDate = now()->addDays(7)->toDateString();
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
        return view('livewire.hotels.room-availability-calendar');
    }
}
