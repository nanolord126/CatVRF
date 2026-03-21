<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Http\Controllers;

use App\Domains\HomeServices\Models\ServiceListing;
use App\Domains\HomeServices\Services\ListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class ServiceListingController
{
    public function __construct(private ListingService $listingService) {}

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
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $contractor = \App\Domains\HomeServices\Models\Contractor::where('user_id', auth()->id())->firstOrFail();
            $this->authorize('create', ServiceListing::class);

            $validated = request()->validate([
                'category_id' => 'required|integer|exists:service_categories,id',
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'type' => 'required|in:hourly,fixed,per_unit',
                'base_price' => 'required|numeric|min:0',
            ]);

            $correlationId = Str::uuid();
            $listing = $this->listingService->createListing(
                $contractor->id,
                $validated['category_id'],
                $validated['name'],
                $validated['description'],
                $validated['type'],
                $validated['base_price'],
                $correlationId
            );

            return response()->json(['success' => true, 'data' => $listing, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create listing'], 500);
        }
    }

    public function update(int $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $listing = ServiceListing::findOrFail($id);
            $this->authorize('update', $listing);

            $validated = request()->validate([
                'name' => 'sometimes|string',
                'description' => 'sometimes|string',
                'base_price' => 'sometimes|numeric|min:0',
                'is_active' => 'sometimes|boolean',
            ]);

            $correlationId = Str::uuid();
            $listing = $this->listingService->updateListing($listing, $validated, $correlationId);

            return response()->json(['success' => true, 'data' => $listing, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Update failed'], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $listing = ServiceListing::findOrFail($id);
            $this->authorize('delete', $listing);

            $listing->delete();

            return response()->json(['success' => true, 'message' => 'Listing deleted', 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Deletion failed'], 500);
        }
    }

    public function forceDelete(int $id): JsonResponse
    {
        try {
            $listing = ServiceListing::findOrFail($id);
            $this->authorize('delete', $listing);

            $listing->forceDelete();

            return response()->json(['success' => true, 'message' => 'Listing permanently deleted']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Deletion failed'], 500);
        }
    }
}
