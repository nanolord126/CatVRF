<?php declare(strict_types=1);

namespace App\Domains\TravelTourism\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\TravelTourism\Models\TravelTour;
use App\Services\Inventory\InventoryManagementService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TourService
{
    public function __construct(
        private readonly InventoryManagementService $inventoryService,
    ) {}

    public function createTour(array $data): TravelTour
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'createTour'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createTour', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'createTour'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createTour', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'createTour'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createTour', ['domain' => __CLASS__]);

        Log::channel('audit')->info('TourService: Creating tour', [
            'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
            'tour_operator_id' => $data['tour_operator_id'],
            'tenant_id' => filament()->getTenant()->id,
        ]);

        return DB::transaction(fn () => TravelTour::create([
            'uuid' => Str::uuid(),
            'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
            'tenant_id' => filament()->getTenant()->id,
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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'updateTourDetails'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL updateTourDetails', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'updateTourDetails'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL updateTourDetails', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'updateTourDetails'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL updateTourDetails', ['domain' => __CLASS__]);

        $tour = TravelTour::findOrFail($tourId);

        Log::channel('audit')->info('TourService: Updating tour details', [
            'correlation_id' => $tour->correlation_id,
            'tour_id' => $tourId,
        ]);

        return DB::transaction(function () use ($tour, $data) {
            $tour->update($data);
            return true;
        });
    }

    public function getToursForDestination(string $country, string $city = ''): Collection
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getToursForDestination'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getToursForDestination', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getToursForDestination'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getToursForDestination', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getToursForDestination'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getToursForDestination', ['domain' => __CLASS__]);

        return TravelTour::where('destination_country', $country)
            ->when($city, fn ($q) => $q->where('destination_city', $city))
            ->where('status', 'active')
            ->where('current_participants', '<', DB::raw('max_participants'))
            ->orderByDesc('start_date')
            ->get();
    }

    public function getAvailableDates(int $tourId): Collection
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getAvailableDates'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getAvailableDates', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getAvailableDates'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getAvailableDates', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getAvailableDates'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getAvailableDates', ['domain' => __CLASS__]);

        $tour = TravelTour::findOrFail($tourId);

        return collect()->range(0, $tour->duration_days - 1)->map(function (int $day) use ($tour) {
            return $tour->start_date->clone()->addDays($day);
        });
    }

    public function publishTour(int $tourId): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'publishTour'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL publishTour', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'publishTour'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL publishTour', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'publishTour'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL publishTour', ['domain' => __CLASS__]);

        $tour = TravelTour::findOrFail($tourId);

        Log::channel('audit')->info('TourService: Publishing tour', [
            'correlation_id' => $tour->correlation_id,
            'tour_id' => $tourId,
        ]);

        return DB::transaction(function () use ($tour) {
            $tour->update(['status' => 'published']);
            return true;
        });
    }

    public function closeTourRegistration(int $tourId): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'closeTourRegistration'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL closeTourRegistration', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'closeTourRegistration'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL closeTourRegistration', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'closeTourRegistration'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL closeTourRegistration', ['domain' => __CLASS__]);

        $tour = TravelTour::findOrFail($tourId);

        Log::channel('audit')->info('TourService: Closing tour registration', [
            'correlation_id' => $tour->correlation_id,
            'tour_id' => $tourId,
        ]);

        return DB::transaction(function () use ($tour) {
            $tour->update(['status' => 'registration_closed']);
            return true;
        });
    }

    public function completeTour(int $tourId): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'completeTour'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL completeTour', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'completeTour'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL completeTour', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'completeTour'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL completeTour', ['domain' => __CLASS__]);

        $tour = TravelTour::findOrFail($tourId);

        Log::channel('audit')->info('TourService: Completing tour', [
            'correlation_id' => $tour->correlation_id,
            'tour_id' => $tourId,
        ]);

        return DB::transaction(function () use ($tour) {
            $tour->update(['status' => 'completed']);
            return true;
        });
    }
}
