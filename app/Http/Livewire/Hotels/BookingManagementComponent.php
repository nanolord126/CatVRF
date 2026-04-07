<?php declare(strict_types=1);

namespace App\Http\Livewire\Hotels;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use Livewire\Component;

final class BookingManagementComponent extends Component
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


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

                $query = Booking::where('client_id', $this->guard->user()->id);

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
                $this->logger->error('Failed to load bookings', [
                    'user_id' => $this->guard->user()->id,
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

                if ($booking->client_id !== $this->guard->user()->id) {
                    throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Unauthorized');
                }

                if ($booking->status === 'checked_in') {
                    throw new \LogicException('Cannot cancel checked-in booking');
                }

                $this->bookingService->cancelBooking($booking, [
                    'reason' => 'Client cancellation',
                    'correlation_id' => (string) Str::uuid(),
                ]);

                $this->logger->info('Booking cancelled', [
                    'booking_id' => $bookingId,
                    'user_id' => $this->guard->user()->id,
                ]);

                $this->loadBookings();
                $this->emit('bookingCancelled', $bookingId);

            } catch (\Exception $e) {
                $this->logger->error('Failed to cancel booking', [
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
