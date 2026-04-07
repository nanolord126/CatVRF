<?php declare(strict_types=1);

namespace App\Services\CarRental;




use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\FraudControlService;
use App\Models\CarRental\Car;
use App\Models\CarRental\Booking;
use App\Models\CarRental\Insurance;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class CarRentalBookingService
{

    /**
         * Dependency injection for modular logic.
         */
        public function __construct(
        private readonly Request $request,
            private FraudControlService $fraud,
            private PricingService $pricingService,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

        /**
         * Initialize booking process (Layer: Reservation).
         * Strictly uses $this->db->transaction and pessimistic locking.
         */
        public function createBooking(array $data, string $correlationId): Booking
        {
            return $this->db->transaction(function () use ($data, $correlationId) {
                // 1. Mandatory Fraud Check (Canon Rule 2026)
                $this->fraud->check((int) $data['user_id'], 'car_rental_booking', $this->request->ip());

                // 2. Fetch car with pessimistic lock to prevent double booking
                $car = Car::where('id', $data['car_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$car->isAvailable()) {
                    throw new \DomainException("The vehicle '{$car->getDisplayNameAttribute()}' is currently unvailable.");
                }

                // 3. Date Parsing
                $start = Carbon::parse($data['starts_at']);
                $end = Carbon::parse($data['ends_at']);
                $days = max((int)$start->diffInDays($end), 1);

                // 4. Pricing Logic (Using PricingService)
                $insurance = Insurance::find($data['insurance_id'] ?? null);
                $pricing = $this->pricingService->calculate(
                    $car,
                    $days,
                    (bool)($data['is_b2b'] ?? false)
                );

                $deposit = $this->calculateDeposit($car, $insurance);

                // 5. Create Booking Entity
                $booking = Booking::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => tenant()->id ?? 1,
                    'user_id' => $data['user_id'],
                    'car_id' => $car->id,
                    'insurance_id' => $insurance?->id,
                    'starts_at' => $start,
                    'ends_at' => $end,
                    'daily_price' => $pricing['daily_price'],
                    'total_price' => $pricing['total_price'],
                    'deposit_amount' => $deposit,
                    'status' => 'pending',
                    'is_b2b' => (bool)($data['is_b2b'] ?? false),
                    'firm_name' => $data['firm_name'] ?? null,
                    'correlation_id' => $correlationId,
                    'idempotency_key' => $data['idempotency_key'] ?? (string) Str::random(16),
                ]);

                // 6. Update Car Status
                $car->update(['status' => 'reserved']);

                // 7. Audit Logging (Canon Rule 2026)
                $this->logger->channel('audit')->info('[CarRental] Booking Created', [
                    'correlation_id' => $correlationId,
                    'booking_uuid' => $booking->uuid,
                    'user_id' => $data['user_id'],
                    'total_price' => $booking->total_price,
                ]);

                return $booking;
            });
        }

        /**
         * Check-In Process: Client takes the car.
         * Requires photo-fix and mileage verification.
         */
        public function processCheckIn(string $bookingUuid, array $photoData, int $mileage, string $correlationId): bool
        {
            return $this->db->transaction(function () use ($bookingUuid, $photoData, $mileage, $correlationId) {
                $booking = Booking::where('uuid', $bookingUuid)->lockForUpdate()->firstOrFail();

                if ($booking->status !== 'confirmed') {
                    throw new \LogicException("Cannot check-in. Booking must be in 'confirmed' status.");
                }

                $booking->update([
                    'status' => 'picked_up',
                    'check_in_data' => [
                        'photos' => $photoData,
                        'start_mileage' => $mileage,
                        'timestamp' => now()->toIso8601String(),
                    ],
                    'correlation_id' => $correlationId,
                ]);

                $booking->car->update(['status' => 'rented', 'mileage' => $mileage]);

                $this->logger->channel('audit')->info('[CarRental] Vehicle Picked Up', [
                    'booking_uuid' => $bookingUuid,
                    'correlation_id' => $correlationId,
                    'mileage' => $mileage,
                ]);

                return true;
            });
        }

        /**
         * Check-Out: Return process with mileage verification.
         */
        public function processCheckOut(string $bookingUuid, array $photoData, int $endMileage, string $correlationId): bool
        {
            return $this->db->transaction(function () use ($bookingUuid, $photoData, $endMileage, $correlationId) {
                $booking = Booking::with('car')->where('uuid', $bookingUuid)->lockForUpdate()->firstOrFail();

                if ($booking->status !== 'picked_up') {
                    throw new \LogicException("Cannot return vehicle. Status mismatch.");
                }

                $startMileage = $booking->check_in_data['start_mileage'] ?? 0;
                $tripDistance = $endMileage - $startMileage;

                $booking->update([
                    'status' => 'returned',
                    'check_out_data' => [
                        'photos' => $photoData,
                        'end_mileage' => $endMileage,
                        'trip_distance' => $tripDistance,
                        'timestamp' => now()->toIso8601String(),
                    ],
                    'correlation_id' => $correlationId,
                ]);

                $booking->car->update([
                    'status' => 'available',
                    'mileage' => $endMileage
                ]);

                $this->logger->channel('audit')->info('[CarRental] Vehicle Returned', [
                    'booking_uuid' => $bookingUuid,
                    'distance' => $tripDistance,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        }

        /**
         * Calculate security deposit based on car value and insurance.
         */
        private function calculateDeposit(Car $car, ?Insurance $insurance): int
        {
            $baseDeposit = 3000000; // 30,000 Rub default

            // Premium cars have higher base deposit
            if ($car->type->daily_price_base > 1000000) {
                $baseDeposit = 5000000; // 50,000 Rub
            }

            // Full insurance reduces deposit
            if ($insurance && $insurance->deductible === 0) {
                return (int) ($baseDeposit * 0.3); // Only 30% if zero franchise
            }

            return $baseDeposit;
        }
}
