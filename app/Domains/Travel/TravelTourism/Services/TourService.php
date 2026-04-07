<?php declare(strict_types=1);

namespace App\Domains\Travel\TravelTourism\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class TourService
{

    public function __construct(private readonly FraudControlService $fraud,
            private readonly InventoryManagementService $inventoryService,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function createTour(array $data): TravelTour
        {

            $this->logger->info('TourService: Creating tour', [
                'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
                'tour_operator_id' => $data['tour_operator_id'],
                'tenant_id' => tenant()->id,
            ]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(fn () => TravelTour::create([
                'uuid' => Str::uuid(),
                'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
                'tenant_id' => tenant()->id,
                'tour_operator_id' => $data['tour_operator_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'destination_country' => $data['destination_country'],
                'destination_city' => $data['destination_city'],
                'duration_days' => $data['duration_days'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'max_participants' => $data['max_participants'] ?? 20,
                'current_participants' => 0,
                'base_price' => $data['base_price'],
                'status' => 'active',
                'includes_flights' => $data['includes_flights'] ?? true,
                'includes_accommodation' => $data['includes_accommodation'] ?? true,
                'includes_meals' => $data['includes_meals'] ?? false,
                'itinerary' => $data['itinerary'] ?? [],
                'tags' => $data['tags'] ?? [],
            ]));
        }

        public function updateTourDetails(int $tourId, array $data): bool
        {

            $tour = TravelTour::findOrFail($tourId);

            $this->logger->info('TourService: Updating tour details', [
                'correlation_id' => $tour->correlation_id,
                'tour_id' => $tourId,
            ]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($tour, $data) {
                $tour->update($data);
                return true;
            });
        }

        public function getToursForDestination(string $country, string $city = ''): Collection
        {

            return TravelTour::where('destination_country', $country)
                ->when($city, fn ($q) => $q->where('destination_city', $city))
                ->where('status', 'active')
                ->where('current_participants', '<', $this->db->raw('max_participants'))
                ->orderByDesc('start_date')
                ->get();
        }

        public function getAvailableDates(int $tourId): Collection
        {

            $tour = TravelTour::findOrFail($tourId);

            return collect()->range(0, $tour->duration_days - 1)->map(function (int $day) use ($tour) {
                return $tour->start_date->clone()->addDays($day);
            });
        }

        public function publishTour(int $tourId): bool
        {

            $tour = TravelTour::findOrFail($tourId);

            $this->logger->info('TourService: Publishing tour', [
                'correlation_id' => $tour->correlation_id,
                'tour_id' => $tourId,
            ]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($tour) {
                $tour->update(['status' => 'published']);
                return true;
            });
        }

        public function closeTourRegistration(int $tourId): bool
        {

            $tour = TravelTour::findOrFail($tourId);

            $this->logger->info('TourService: Closing tour registration', [
                'correlation_id' => $tour->correlation_id,
                'tour_id' => $tourId,
            ]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($tour) {
                $tour->update(['status' => 'registration_closed']);
                return true;
            });
        }

        public function completeTour(int $tourId): bool
        {

            $tour = TravelTour::findOrFail($tourId);

            $this->logger->info('TourService: Completing tour', [
                'correlation_id' => $tour->correlation_id,
                'tour_id' => $tourId,
            ]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($tour) {
                $tour->update(['status' => 'completed']);
                return true;
            });
        }
}
