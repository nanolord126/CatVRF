<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceListingController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private ListingService $listingService,
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function index(): JsonResponse
        {
            try {
                $listings = ServiceListing::where('is_active', true)
                    ->with(['contractor', 'category', 'reviews'])
                    ->paginate(20);

                return response()->json(['success' => true, 'data' => $listings, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Failed to list listings'], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $listing = ServiceListing::with(['contractor', 'category', 'reviews', 'jobs'])->findOrFail($id);
                return response()->json(['success' => true, 'data' => $listing, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Listing not found'], 404);
            }
        }

        public function byContractor(int $contractorId): JsonResponse
        {
            try {
                $listings = ServiceListing::where('contractor_id', $contractorId)
                    ->where('is_active', true)
                    ->with(['category', 'reviews'])
                    ->paginate(20);

                return response()->json(['success' => true, 'data' => $listings, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Failed to fetch listings'], 500);
            }
        }

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $fraudResult   = $this->fraudControlService->check(auth()->id() ?? 0, 'service_listing_create', 0, request()->ip(), null, $correlationId);

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('ServiceListing create blocked', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'score'          => $fraudResult['score'],
                ]);
                return response()->json(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            Log::channel('audit')->info('ServiceListing create start', ['correlation_id' => $correlationId, 'user_id' => auth()->id()]);

            try {
                $contractor = \App\Domains\HomeServices\Models\Contractor::where('user_id', auth()->id())->firstOrFail();
                $this->authorize('create', ServiceListing::class);

                $validated = request()->validate([
                    'category_id' => 'required|integer|exists:service_categories,id',
                    'name'        => 'required|string|max:255',
                    'description' => 'required|string',
                    'type'        => 'required|in:hourly,fixed,per_unit',
                    'base_price'  => 'required|numeric|min:0',
                ]);

                $listing = $this->listingService->createListing(
                    $contractor->id,
                    $validated['category_id'],
                    $validated['name'],
                    $validated['description'],
                    $validated['type'],
                    $validated['base_price'],
                    $correlationId
                );

                Log::channel('audit')->info('ServiceListing created', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'listing_id'     => $listing->id,
                ]);

                return response()->json(['success' => true, 'data' => $listing, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                Log::error('ServiceListing create failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return response()->json(['success' => false, 'message' => 'Failed to create listing'], 500);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $fraudResult   = $this->fraudControlService->check(auth()->id() ?? 0, 'service_listing_update', 0, request()->ip(), null, $correlationId);

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('ServiceListing update blocked', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'score'          => $fraudResult['score'],
                ]);
                return response()->json(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $listing = ServiceListing::findOrFail($id);
                $this->authorize('update', $listing);

                $before = $listing->toArray();

                $validated = request()->validate([
                    'name'        => 'sometimes|string',
                    'description' => 'sometimes|string',
                    'base_price'  => 'sometimes|numeric|min:0',
                    'is_active'   => 'sometimes|boolean',
                ]);

                $listing = $this->listingService->updateListing($listing, $validated, $correlationId);

                Log::channel('audit')->info('ServiceListing updated', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'listing_id'     => $id,
                    'before'         => $before,
                    'after'          => $listing->toArray(),
                ]);

                return response()->json(['success' => true, 'data' => $listing, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                Log::error('ServiceListing update failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return response()->json(['success' => false, 'message' => 'Update failed'], 500);
            }
        }

        public function delete(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $fraudResult   = $this->fraudControlService->check(auth()->id() ?? 0, 'service_listing_delete', 0, request()->ip(), null, $correlationId);

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('ServiceListing delete blocked', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'score'          => $fraudResult['score'],
                ]);
                return response()->json(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $listing = ServiceListing::findOrFail($id);
                $this->authorize('delete', $listing);

                $listing->delete();

                Log::channel('audit')->info('ServiceListing deleted', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'listing_id'     => $id,
                ]);

                return response()->json(['success' => true, 'message' => 'Listing deleted', 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                Log::error('ServiceListing delete failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return response()->json(['success' => false, 'message' => 'Deletion failed'], 500);
            }
        }

        public function forceDelete(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $fraudResult   = $this->fraudControlService->check(auth()->id() ?? 0, 'service_listing_force_delete', 0, request()->ip(), null, $correlationId);

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('ServiceListing forceDelete blocked', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'score'          => $fraudResult['score'],
                ]);
                return response()->json(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $listing = ServiceListing::findOrFail($id);
                $this->authorize('delete', $listing);

                $listing->forceDelete();

                Log::channel('audit')->info('ServiceListing permanently deleted', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'listing_id'     => $id,
                ]);

                return response()->json(['success' => true, 'message' => 'Listing permanently deleted', 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                Log::error('ServiceListing forceDelete failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return response()->json(['success' => false, 'message' => 'Deletion failed'], 500);
            }
        }
}
