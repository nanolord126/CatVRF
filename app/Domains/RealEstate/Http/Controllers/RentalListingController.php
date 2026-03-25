<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Http\Controllers;

use App\Domains\RealEstate\Models\RentalListing;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Controller для управления объявлениями об аренде.
 * Production 2026.
 */
final class RentalListingController
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $listings = RentalListing::query()
                ->where('status', 'active')
                ->with('property')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $listings,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false], 500);
        }
    }

    public function store(): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        $fraudResult = $this->fraudControlService->check(auth()->id() ?? 0, 'rental_listing_create', 0, request()->ip(), null, $correlationId);
        if ($fraudResult['decision'] === 'block') {
            return response()->json(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
        }

        try {
            $data = request()->validate([
                'property_id'      => 'required|integer',
                'rent_price_month' => 'required|integer|min:1',
                'deposit'          => 'nullable|integer|min:0',
                'lease_term_min'   => 'nullable|integer|min:1',
                'lease_term_max'   => 'nullable|integer|min:1',
                'description'      => 'nullable|string',
            ]);

            $listing = $this->db->transaction(function () use ($data, $correlationId) {
                return RentalListing::create([
                    ...$data,
                    'tenant_id'      => tenant('id') ?? auth()->user()?->tenant_id ?? 1,
                    'status'         => 'active',
                    'correlation_id' => $correlationId,
                    'uuid'           => Str::uuid(),
                ]);
            });

            $this->log->channel('audit')->info('Rental listing created', [
                'correlation_id' => $correlationId,
                'listing_id'     => $listing->id,
                'tenant_id'      => $listing->tenant_id,
                'user_id'        => auth()->id(),
                'property_id'    => $listing->property_id,
            ]);

            return response()->json([
                'success'        => true,
                'data'           => $listing,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Rental listing create failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Ошибка создания объявления.', 'correlation_id' => $correlationId], 500);
        }
    }

    public function destroy(RentalListing $rentalListing): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        $fraudResult = $this->fraudControlService->check(auth()->id() ?? 0, 'rental_listing_delete', 0, request()->ip(), null, $correlationId);
        if ($fraudResult['decision'] === 'block') {
            return response()->json(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
        }

        try {
            $this->db->transaction(function () use ($rentalListing) {
                $rentalListing->update(['status' => 'removed']);
                $rentalListing->delete();
            });

            $this->log->channel('audit')->info('Rental listing deleted', [
                'correlation_id' => $correlationId,
                'listing_id'     => $rentalListing->id,
                'tenant_id'      => $rentalListing->tenant_id,
                'user_id'        => auth()->id(),
            ]);

            return response()->json([
                'success'        => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Rental listing delete failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Ошибка удаления объявления.', 'correlation_id' => $correlationId], 500);
        }
    }
}
