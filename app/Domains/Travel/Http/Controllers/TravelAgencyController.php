<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Controllers;

use App\Domains\Travel\Models\TravelAgency;
use App\Domains\Travel\Models\TravelAccommodation;
use App\Domains\Travel\Models\TravelGuide;
use App\Domains\Travel\Models\TravelReview;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class TravelAgencyController extends Controller
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}
    public function index(Request $request): JsonResponse
    {
        try {
            $page = $request->get('page', 1);
            $per_page = $request->get('per_page', 20);
            $is_verified = $request->get('is_verified');

            $query = TravelAgency::query()
                ->where('tenant_id', tenant()->id)
                ->where('is_active', true);

            if ($is_verified !== null) {
                $query->where('is_verified', $is_verified);
            }

            $agencies = $query->paginate($per_page, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $agencies->items(),
                'pagination' => [
                    'total' => $agencies->total(),
                    'per_page' => $agencies->perPage(),
                    'current_page' => $agencies->currentPage(),
                    'last_page' => $agencies->lastPage(),
                ],
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to list travel agencies', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to list agencies',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $agency = TravelAgency::where('tenant_id', tenant()->id)->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $agency,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to show travel agency', [
                'agency_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Agency not found',
                'correlation_id' => Str::uuid(),
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->get('correlation_id', Str::uuid()->toString());
        $this->fraudControlService->check(auth()->id() ?? 0, 'agency_store', 0, $request->ip(), null, $correlationId);

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'required|string',
                'phone' => 'required|string',
                'email' => 'required|email',
                'specializations' => 'nullable|array',
                'website' => 'nullable|url',
            ]);

            $validated = $request->all();
            $agency = DB::transaction(function () use ($validated, $correlationId) {
                return TravelAgency::create([
                    'tenant_id' => tenant()->id,
                    'owner_id' => auth()->id(),
                    'name' => ($validated['name'] ?? null),
                    'address' => ($validated['address'] ?? null),
                    'phone' => ($validated['phone'] ?? null),
                    'email' => ($validated['email'] ?? null),
                    'specializations' => ($validated['specializations'] ?? []),
                    'website' => ($validated['website'] ?? null),
                    'correlation_id' => $correlationId,
                    'uuid' => Str::uuid(),
                ]);
            });

            Log::channel('audit')->info('Travel agency created', [
                'agency_id' => $agency->id,
                'name' => $agency->name,
                'owner_id' => auth()->id(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $agency,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (Throwable $e) {
            Log::channel('audit')->error('Travel agency creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create agency',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->get('correlation_id', Str::uuid()->toString());
        $this->fraudControlService->check(auth()->id() ?? 0, 'agency_update', 0, $request->ip(), null, $correlationId);

        try {
            $agency = TravelAgency::where('tenant_id', tenant()->id)->findOrFail($id);

            $this->authorize('update', $agency);

            $validated = $request->all();
            $agency = DB::transaction(function () use ($validated, $agency, $correlationId) {
                $agency->update([
                    'name' => ($validated['name'] ?? $agency->name),
                    'address' => ($validated['address'] ?? $agency->address),
                    'phone' => ($validated['phone'] ?? $agency->phone),
                    'email' => ($validated['email'] ?? $agency->email),
                    'specializations' => ($validated['specializations'] ?? $agency->specializations),
                    'website' => ($validated['website'] ?? $agency->website),
                    'correlation_id' => $correlationId,
                ]);

                return $agency;
            });

            Log::channel('audit')->info('Travel agency updated', [
                'agency_id' => $agency->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $agency,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->error('Travel agency update failed', [
                'agency_id' => $id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update agency',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'agency_destroy', 0, request()->ip(), null, $correlationId);

        try {
            $agency = TravelAgency::where('tenant_id', tenant()->id)->findOrFail($id);

            $this->authorize('delete', $agency);

            DB::transaction(function () use ($agency, $correlationId) {
                $agency->delete();
            });

            Log::channel('audit')->info('Travel agency deleted', [
                'agency_id' => $agency->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->error('Travel agency deletion failed', [
                'agency_id' => $id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete agency',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function getTours(int $id): JsonResponse
    {
        try {
            $agency = TravelAgency::where('tenant_id', tenant()->id)->findOrFail($id);

            $tours = $agency->tours()->where('is_active', true)->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $tours->items(),
                'pagination' => [
                    'total' => $tours->total(),
                    'per_page' => $tours->perPage(),
                ],
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get agency tours',
                'correlation_id' => Str::uuid(),
            ], 404);
        }
    }

    public function indexAccommodations(): JsonResponse
    {
        try {
            $accommodations = TravelAccommodation::where('tenant_id', tenant()->id)
                ->where('is_available', true)
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $accommodations->items(),
                'pagination' => [
                    'total' => $accommodations->total(),
                ],
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list accommodations',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function showAccommodation(int $id): JsonResponse
    {
        try {
            $accommodation = TravelAccommodation::where('tenant_id', tenant()->id)->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $accommodation,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Accommodation not found',
                'correlation_id' => Str::uuid(),
            ], 404);
        }
    }

    public function indexGuides(): JsonResponse
    {
        try {
            $guides = TravelGuide::where('tenant_id', tenant()->id)
                ->where('is_available', true)
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $guides->items(),
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list guides',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function showGuide(int $id): JsonResponse
    {
        try {
            $guide = TravelGuide::where('tenant_id', tenant()->id)->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $guide,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Guide not found',
                'correlation_id' => Str::uuid(),
            ], 404);
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            $type = $request->get('type', 'agencies');

            $results = match ($type) {
                'agencies' => TravelAgency::where('tenant_id', tenant()->id)
                    ->where('name', 'ilike', "%{$query}%")
                    ->paginate(20),
                'tours' => \App\Domains\Travel\Models\TravelTour::where('tenant_id', tenant()->id)
                    ->where('name', 'ilike', "%{$query}%")
                    ->paginate(20),
                'guides' => TravelGuide::where('tenant_id', tenant()->id)
                    ->where('full_name', 'ilike', "%{$query}%")
                    ->paginate(20),
                default => collect([]),
            };

            return response()->json([
                'success' => true,
                'data' => $results instanceof \Illuminate\Pagination\Paginator ? $results->items() : $results,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function analytics(int $id): JsonResponse
    {
        try {
            $agency = TravelAgency::where('tenant_id', tenant()->id)->findOrFail($id);

            $this->authorize('view', $agency);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_tours' => $agency->tour_count,
                    'total_bookings' => $agency->bookings()->count(),
                    'avg_rating' => $agency->rating,
                    'review_count' => $agency->review_count,
                ],
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get analytics',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function earnings(int $id): JsonResponse
    {
        try {
            $agency = TravelAgency::where('tenant_id', tenant()->id)->findOrFail($id);

            $this->authorize('view', $agency);

            $monthlyEarnings = $agency->bookings()
                ->where('status', '!=', 'cancelled')
                ->whereBetween('booked_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('commission_amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'monthly_earnings' => $monthlyEarnings,
                    'total_earnings' => $agency->bookings()
                        ->where('status', '!=', 'cancelled')
                        ->sum('commission_amount'),
                ],
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get earnings',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function bookingsList(int $id): JsonResponse
    {
        try {
            $agency = TravelAgency::where('tenant_id', tenant()->id)->findOrFail($id);

            $this->authorize('view', $agency);

            $bookings = $agency->bookings()->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $bookings->items(),
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get bookings',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function verify(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $agency = TravelAgency::where('tenant_id', tenant()->id)->findOrFail($id);

            DB::transaction(function () use ($agency, $correlationId) {
                $agency->update([
                    'is_verified' => true,
                    'correlation_id' => $correlationId,
                ]);
            });

            Log::channel('audit')->info('Travel agency verified', [
                'agency_id' => $agency->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify agency',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function reject(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $agency = TravelAgency::where('tenant_id', tenant()->id)->findOrFail($id);

            DB::transaction(function () use ($agency, $correlationId) {
                $agency->delete();
            });

            Log::channel('audit')->info('Travel agency rejected', [
                'agency_id' => $agency->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject agency',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function allAgencies(): JsonResponse
    {
        try {
            $agencies = TravelAgency::where('tenant_id', tenant()->id)
                ->where('is_verified', false)
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $agencies->items(),
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get agencies',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function restore(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $agency = TravelAgency::withTrashed()
                ->where('tenant_id', tenant()->id)
                ->findOrFail($id);

            DB::transaction(function () use ($agency, $correlationId) {
                $agency->restore();
                $agency->update(['correlation_id' => $correlationId]);
            });

            Log::channel('audit')->info('Travel agency restored', [
                'agency_id' => $agency->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore agency',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
