<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Http\Controllers;

use App\Domains\Entertainment\Models\EntertainmentVenue;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class EntertainmentVenueController
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $venues = EntertainmentVenue::query()
                ->where('is_verified', true)
                ->where('is_active', true)
                ->with('entertainers', 'entertainmentEvents')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $venues,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to fetch venues', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
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

            return response()->json([
                'success' => true,
                'data' => $venue,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to fetch venue', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Venue not found', 'correlation_id' => Str::uuid()], 404);
        }
    }

    public function store(): JsonResponse
    {
        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            'operation',
            0,
            request()->ip(),
            request()->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $this->log->channel('fraud_alert')->warning('Operation blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Операция заблокирована.',
                'correlation_id' => $correlationId,
            ], 403);
        }

        try {
            $this->db->transaction(function () {
                $correlationId = Str::uuid()->toString();

                $venue = EntertainmentVenue::create([
                    'tenant_id' => tenant('id'),
                    'name' => request('name'),
                    'description' => request('description'),
                    'address' => request('address'),
                    'seating_capacity' => request('seating_capacity'),
                    'standard_ticket_price' => request('standard_ticket_price'),
                    'premium_ticket_price' => request('premium_ticket_price'),
                    'is_active' => true,
                    'correlation_id' => $correlationId,
                ]);

                $this->log->channel('audit')->info('Entertainment venue created', [
                    'venue_id' => $venue->id,
                    'name' => $venue->name,
                    'correlation_id' => $correlationId,
                ]);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => Str::uuid()], 201);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to create venue', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
        }
    }

    public function update(int $id): JsonResponse
    {
        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            'operation',
            0,
            request()->ip(),
            request()->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $this->log->channel('fraud_alert')->warning('Operation blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
            ]);
            return response()->json([
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
                    'name' => request('name', $venue->name),
                    'description' => request('description', $venue->description),
                    'address' => request('address', $venue->address),
                    'correlation_id' => $correlationId,
                ]);

                $this->log->channel('audit')->info('Entertainment venue updated', [
                    'venue_id' => $venue->id,
                    'correlation_id' => $correlationId,
                ]);
            });

            return response()->json(['success' => true, 'data' => $venue, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to update venue', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $venue = EntertainmentVenue::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            $this->db->transaction(function () use ($venue, $correlationId) {
                $venue->delete();
                $this->log->channel('audit')->info('Entertainment venue deleted', ['venue_id' => $id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to delete venue', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function analytics(int $venueId): JsonResponse
    {
        try {
            $venue = EntertainmentVenue::findOrFail($venueId);
            $bookings = $venue->bookings()->count();
            $revenue = $venue->bookings()->sum('total_price');

            return response()->json([
                'success' => true,
                'data' => ['bookings' => $bookings, 'revenue' => $revenue],
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }
}
