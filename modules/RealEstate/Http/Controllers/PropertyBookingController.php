<?php declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Http\Requests\CreatePropertyBookingRequest;
use Modules\RealEstate\Http\Resources\PropertyBookingResource;
use Modules\RealEstate\Models\PropertyBooking;
use Modules\RealEstate\Services\PropertyBookingService;

final class PropertyBookingController
{
    public function __construct(
        private PropertyBookingService $bookingService,
    ) {}

    public function create(CreatePropertyBookingRequest $request): JsonResponse
    {
        try {
            $booking = $this->bookingService->createBooking($request->validated() + [
                'tenant_id' => tenant()->id,
                'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString(),
            ]);

            return response()->json([
                'success' => true,
                'data' => new PropertyBookingResource($booking),
                'message' => 'Booking created successfully',
            ], 201);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], $e->getCode() >= 400 ? $e->getCode() : 400);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    public function confirm(int $bookingId, Request $request): JsonResponse
    {
        try {
            $booking = $this->bookingService->confirmBooking(
                $bookingId,
                $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString()
            );

            return response()->json([
                'success' => true,
                'data' => new PropertyBookingResource($booking),
                'message' => 'Booking confirmed successfully',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], $e->getCode() >= 400 ? $e->getCode() : 400);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    public function complete(int $bookingId, Request $request): JsonResponse
    {
        try {
            $booking = $this->bookingService->completeDeal(
                $bookingId,
                $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString()
            );

            return response()->json([
                'success' => true,
                'data' => new PropertyBookingResource($booking),
                'message' => 'Deal completed successfully',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], $e->getCode() >= 400 ? $e->getCode() : 400);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    public function cancel(int $bookingId, Request $request): JsonResponse
    {
        try {
            $booking = $this->bookingService->cancelBooking(
                $bookingId,
                $request->input('reason', 'User cancelled'),
                $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString()
            );

            return response()->json([
                'success' => true,
                'data' => new PropertyBookingResource($booking),
                'message' => 'Booking cancelled successfully',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], $e->getCode() >= 400 ? $e->getCode() : 400);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    public function initiateVideoCall(int $bookingId, Request $request): JsonResponse
    {
        try {
            $result = $this->bookingService->initiateVideoCall(
                $bookingId,
                auth()->id(),
                $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString()
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Video call initiated successfully',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], $e->getCode() >= 400 ? $e->getCode() : 400);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    public function getAvailableSlots(int $propertyId, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date' => ['required', 'date_format:Y-m-d'],
                'end_date' => ['required', 'date_format:Y-m-d', 'after:start_date'],
            ]);

            $slots = $this->bookingService->getAvailableSlots(
                $propertyId,
                $request->input('start_date'),
                $request->input('end_date'),
                $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString()
            );

            return response()->json([
                'success' => true,
                'data' => $slots,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], $e->getCode() >= 400 ? $e->getCode() : 400);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    public function show(int $bookingId): JsonResponse
    {
        try {
            $booking = PropertyBooking::where('id', $bookingId)
                ->where('tenant_id', tenant()->id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => new PropertyBookingResource($booking),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Booking not found',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = PropertyBooking::where('tenant_id', tenant()->id)
                ->with(['property', 'user']);

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('user_id')) {
                $query->where('user_id', $request->input('user_id'));
            }

            if ($request->has('property_id')) {
                $query->where('property_id', $request->input('property_id'));
            }

            if ($request->has('is_b2b')) {
                $query->where('is_b2b', $request->boolean('is_b2b'));
            }

            $bookings = $query->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => PropertyBookingResource::collection($bookings),
                'meta' => [
                    'current_page' => $bookings->currentPage(),
                    'last_page' => $bookings->lastPage(),
                    'per_page' => $bookings->perPage(),
                    'total' => $bookings->total(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }
}
