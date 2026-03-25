<?php declare(strict_types=1);

namespace App\Http\Livewire\Hotels;

use App\Domains\Hotels\Models\Booking;
use App\Domains\Hotels\Services\BookingService;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;

final class BookingManagementComponent extends Component
{
    public array $bookings = [];
    public bool $isLoading = true;
    public ?string $filterStatus = null;

    protected BookingService $bookingService;

    public function mount(BookingService $bookingService): void
    {
        $this->bookingService = $bookingService;
        $this->loadBookings();
    }

    public function loadBookings(): void
    {
        try {
            $this->isLoading = true;

            $query = Booking::where('client_id', auth()->user()->id);

            if ($this->filterStatus) {
                $query = $query->where('status', $this->filterStatus);
            }

            $this->bookings = $query->orderByDesc('created_at')
                ->with(['hotel', 'room'])
                ->get()
                ->map(fn($booking) => [
                    'id' => $booking->id,
                    'hotel_name' => $booking->hotel->name,
                    'room_number' => $booking->room->number,
                    'check_in' => $booking->check_in_date->format('d.m.Y'),
                    'check_out' => $booking->check_out_date->format('d.m.Y'),
                    'nights' => $booking->check_out_date->diffInDays($booking->check_in_date),
                    'total_price' => number_format($booking->total_price / 100, 2, '.', ''),
                    'status' => $booking->status,
                    'status_label' => $this->getStatusLabel($booking->status),
                ])
                ->toArray();

        } catch (\Exception $e) {
            \$this->log->channel('error')->error('Failed to load bookings', [
                'user_id' => auth()->user()->id,
                'exception' => $e->getMessage(),
                'correlation_id' => (string) Str::uuid(),
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'На рассмотрении',
            'confirmed' => 'Подтверждено',
            'checked_in' => 'Заселено',
            'checked_out' => 'Выселено',
            'cancelled' => 'Отменено',
            default => ucfirst($status),
        };
    }

    public function cancelBooking(int $bookingId): void
    {
        try {
            $booking = Booking::findOrFail($bookingId);

            if ($booking->client_id !== auth()->user()->id) {
                throw new \Exception('Unauthorized');
            }

            if ($booking->status === 'checked_in') {
                throw new \Exception('Cannot cancel checked-in booking');
            }

            $this->bookingService->cancelBooking($booking, [
                'reason' => 'Client cancellation',
                'correlation_id' => (string) Str::uuid(),
            ]);

            \$this->log->channel('audit')->info('Booking cancelled', [
                'booking_id' => $bookingId,
                'user_id' => auth()->user()->id,
            ]);

            $this->loadBookings();
            $this->emit('bookingCancelled', $bookingId);

        } catch (\Exception $e) {
            \$this->log->channel('error')->error('Failed to cancel booking', [
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public function updatedFilterStatus(): void
    {
        $this->loadBookings();
    }

    public function render()
    {
        return view('livewire.hotels.booking-management', [
            'bookings' => $this->bookings,
        ]);
    }
}
