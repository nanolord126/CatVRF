<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class ServiceListingController extends Controller
{


    public function __construct(
            private readonly ListingService $listingService,
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $listings = ServiceListing::where('is_active', true)
                    ->with(['contractor', 'category', 'reviews'])
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $listings, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to list listings'], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $listing = ServiceListing::with(['contractor', 'category', 'reviews', 'jobs'])->findOrFail($id);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $listing, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Listing not found'], 404);
            }
        }

        public function byContractor(int $contractorId): JsonResponse
        {
            try {
                $listings = ServiceListing::where('contractor_id', $contractorId)
                    ->where('is_active', true)
                    ->with(['category', 'reviews'])
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $listings, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to fetch listings'], 500);
            }
        }

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'service_listing_create', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('ServiceListing create blocked', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            $this->logger->info('ServiceListing create start', ['correlation_id' => $correlationId, 'user_id' => $request->user()?->id]);

            try {
                $contractor = \App\Domains\HomeServices\Models\Contractor::where('user_id', $request->user()?->id)->firstOrFail();
                $this->authorize('create', ServiceListing::class);

                $validated = $request->validate([
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

                $this->logger->info('ServiceListing created', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'listing_id'     => $listing->id,
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $listing, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                $this->logger->error('ServiceListing create failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to create listing'], 500);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'service_listing_update', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('ServiceListing update blocked', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $listing = ServiceListing::findOrFail($id);
                $this->authorize('update', $listing);

                $before = $listing->toArray();

                $validated = $request->validate([
                    'name'        => 'sometimes|string',
                    'description' => 'sometimes|string',
                    'base_price'  => 'sometimes|numeric|min:0',
                    'is_active'   => 'sometimes|boolean',
                ]);

                $listing = $this->listingService->updateListing($listing, $validated, $correlationId);

                $this->logger->info('ServiceListing updated', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'listing_id'     => $id,
                    'before'         => $before,
                    'after'          => $listing->toArray(),
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $listing, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('ServiceListing update failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Update failed'], 500);
            }
        }

        public function delete(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'service_listing_delete', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('ServiceListing delete blocked', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $listing = ServiceListing::findOrFail($id);
                $this->authorize('delete', $listing);

                $listing->delete();

                $this->logger->info('ServiceListing deleted', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'listing_id'     => $id,
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Listing deleted', 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('ServiceListing delete failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Deletion failed'], 500);
            }
        }

        public function forceDelete(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'service_listing_force_delete', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('ServiceListing forceDelete blocked', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $listing = ServiceListing::findOrFail($id);
                $this->authorize('delete', $listing);

                $listing->forceDelete();

                $this->logger->info('ServiceListing permanently deleted', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'listing_id'     => $id,
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Listing permanently deleted', 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('ServiceListing forceDelete failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Deletion failed'], 500);
            }
        }
}
