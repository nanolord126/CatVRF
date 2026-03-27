<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;

use App\Domains\Auto\Models\CarDetailing;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Finances\Services\Security\FraudControlService;

final class CarDetailingController extends Controller
{
    public function __construct(
        private readonly FraudControlService $fraudControl
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $detailings = CarDetailing::query()
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
                ->with(['client', 'vehicle'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $detailings,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Car detailing index failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve car detailing bookings',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        $validated = $request->validate([
            'client_id' => 'required|exists:users,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_type' => 'required|array',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:60',
            'price' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->fraudControl->check('car_detailing_booking', $request->ip(), [
                'user_id' => auth()->id(),
                'amount' => $validated['price'],
            ]);

            $detailing = DB::transaction(function () use ($validated, $correlationId) {
                return CarDetailing::create([
                    ...$validated,
                    'tenant_id' => tenant()->id,
                    'status' => 'confirmed',
                    'payment_status' => 'pending',
                    'uuid' => Str::uuid()->toString(),
                    'correlation_id' => $correlationId,
                ]);
            });

            Log::channel('audit')->info('Car detailing booking created', [
                'correlation_id' => $correlationId,
                'detailing_id' => $detailing->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => $detailing->load(['client', 'vehicle']),
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Car detailing booking creation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create car detailing booking',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function show(CarDetailing $detailing): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $detailing->load(['client', 'vehicle']),
        ]);
    }

    public function update(Request $request, CarDetailing $detailing): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        $validated = $request->validate([
            'scheduled_at' => 'sometimes|date|after:now',
            'status' => 'sometimes|in:confirmed,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($detailing, $validated) {
                $detailing->update($validated);
            });

            Log::channel('audit')->info('Car detailing booking updated', [
                'correlation_id' => $correlationId,
                'detailing_id' => $detailing->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => $detailing->fresh(['client', 'vehicle']),
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Car detailing booking update failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update car detailing booking',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function complete(CarDetailing $detailing): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            DB::transaction(function () use ($detailing) {
                $detailing->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            });

            Log::channel('audit')->info('Car detailing completed', [
                'correlation_id' => $correlationId,
                'detailing_id' => $detailing->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => $detailing->fresh(),
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Car detailing completion failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete car detailing',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function cancel(CarDetailing $detailing): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            DB::transaction(function () use ($detailing) {
                $detailing->update(['status' => 'cancelled']);
            });

            Log::channel('audit')->info('Car detailing cancelled', [
                'correlation_id' => $correlationId,
                'detailing_id' => $detailing->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => $detailing->fresh(),
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Car detailing cancellation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel car detailing',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function destroy(CarDetailing $detailing): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $detailing->delete();

            Log::channel('audit')->info('Car detailing deleted', [
                'correlation_id' => $correlationId,
                'detailing_id' => $detailing->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Car detailing booking deleted',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Car detailing deletion failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete car detailing booking',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
