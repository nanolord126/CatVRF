<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class EntertainmentVenueController extends Controller
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $venues = EntertainmentVenue::query()
                    ->where('is_verified', true)
                    ->where('is_active', true)
                    ->with('entertainers', 'entertainmentEvents')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $venues,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to fetch venues', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to fetch venues',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $venue = EntertainmentVenue::with('entertainers', 'entertainmentEvents', 'bookings')
                    ->findOrFail($id);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $venue,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to fetch venue', ['id' => $id, 'error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Venue not found', 'correlation_id' => Str::uuid()], 404);
            }
        }

        public function store(): JsonResponse
        {
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success'        => false,
                    'error'          => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            try {
                $this->db->transaction(function () {
                    $correlationId = Str::uuid()->toString();

                    $venue = EntertainmentVenue::create([
                        'tenant_id' => tenant()->id,
                        'name' => $request->input('name'),
                        'description' => $request->input('description'),
                        'address' => $request->input('address'),
                        'seating_capacity' => $request->input('seating_capacity'),
                        'standard_ticket_price' => $request->input('standard_ticket_price'),
                        'premium_ticket_price' => $request->input('premium_ticket_price'),
                        'is_active' => true,
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Entertainment venue created', [
                        'venue_id' => $venue->id,
                        'name' => $venue->name,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => Str::uuid()], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to create venue', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

        public function update(int $id): JsonResponse
        {
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success'        => false,
                    'error'          => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            try {
                $venue = EntertainmentVenue::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($venue, $correlationId) {
                    $venue->update([
                        'name' => $request->input('name', $venue->name),
                        'description' => $request->input('description', $venue->description),
                        'address' => $request->input('address', $venue->address),
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Entertainment venue updated', [
                        'venue_id' => $venue->id,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $venue, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to update venue', ['id' => $id, 'error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function delete(int $id): JsonResponse
        {
            try {
                $venue = EntertainmentVenue::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($venue, $correlationId) {
                    $venue->delete();
                    $this->logger->info('Entertainment venue deleted', ['venue_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to delete venue', ['id' => $id, 'error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function analytics(int $venueId): JsonResponse
        {
            try {
                $venue = EntertainmentVenue::findOrFail($venueId);
                $bookings = $venue->bookings()->count();
                $revenue = $venue->bookings()->sum('total_price');

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => ['bookings' => $bookings, 'revenue' => $revenue],
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }
}
