<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Controllers;

use App\Domains\Travel\DTOs\TourismBookingDto;
use App\Domains\Travel\Http\Requests\CreateTourismBookingRequest;
use App\Domains\Travel\Http\Requests\ConfirmTourismBookingRequest;
use App\Domains\Travel\Http\Requests\CancelTourismBookingRequest;
use App\Domains\Travel\Http\Requests\ScheduleVideoCallRequest;
use App\Domains\Travel\Services\TourismBookingOrchestratorService;
use App\Domains\Travel\Models\TourBooking;
use App\Domains\Travel\Http\Resources\TourismBookingResource;
use App\Domains\Travel\Http\Resources\TourismBookingCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

/**
 * Tourism Booking Controller
 * 
 * API controller for tourism booking operations.
 * Handles B2C and B2B booking flows with AI personalization,
 * dynamic pricing, biometric verification, and CRM integration.
 */
final class TourismBookingController
{
    public function __construct(
        private readonly TourismBookingOrchestratorService $orchestrator,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Create a new tourism booking with hold.
     */
    public function store(CreateTourismBookingRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $dto = TourismBookingDto::fromRequest($request);

        try {
            $booking = $this->orchestrator->createBooking($dto);

            $this->logger->info('Tourism booking created via API', [
                'booking_uuid' => $booking->uuid,
                'user_id' => $dto->userId,
                'correlation_id' => $correlationId,
            ]);

            return (new TourismBookingResource($booking))
                ->additional([
                    'message' => 'Booking created with hold. Please confirm before hold expires.',
                    'hold_expires_at' => $booking->hold_expires_at->toIso8601String(),
                    'correlation_id' => $correlationId,
                ])
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $e) {
            $this->logger->error('Tourism booking creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'error' => 'Booking creation failed',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Confirm a held booking.
     */
    public function confirm(ConfirmTourismBookingRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $bookingUuid = $request->input('booking_uuid');

        try {
            $booking = $this->orchestrator->confirmBooking($bookingUuid, $correlationId);

            $this->logger->info('Tourism booking confirmed via API', [
                'booking_uuid' => $booking->uuid,
                'correlation_id' => $correlationId,
            ]);

            return (new TourismBookingResource($booking))
                ->additional([
                    'message' => 'Booking confirmed successfully',
                    'cashback_amount' => $booking->cashback_amount,
                    'correlation_id' => $correlationId,
                ])
                ->response();
        } catch (\Throwable $e) {
            $this->logger->error('Tourism booking confirmation failed', [
                'booking_uuid' => $bookingUuid,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'error' => 'Booking confirmation failed',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Cancel a booking with ML-fraud detection.
     */
    public function cancel(CancelTourismBookingRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $bookingUuid = $request->input('booking_uuid');
        $reason = $request->input('reason');

        try {
            $booking = $this->orchestrator->cancelBooking($bookingUuid, $reason, $correlationId);

            $this->logger->info('Tourism booking cancelled via API', [
                'booking_uuid' => $booking->uuid,
                'fraud_score' => $booking->fraud_score,
                'refund_amount' => $booking->refund_amount,
                'correlation_id' => $correlationId,
            ]);

            return (new TourismBookingResource($booking))
                ->additional([
                    'message' => 'Booking cancelled',
                    'refund_amount' => $booking->refund_amount,
                    'fraud_score' => $booking->fraud_score,
                    'correlation_id' => $correlationId,
                ])
                ->response();
        } catch (\Throwable $e) {
            $this->logger->error('Tourism booking cancellation failed', [
                'booking_uuid' => $bookingUuid,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'error' => 'Booking cancellation failed',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Schedule video call with guide.
     */
    public function scheduleVideoCall(ScheduleVideoCallRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $bookingUuid = $request->input('booking_uuid');
        $scheduledTime = $request->input('scheduled_time');

        try {
            $booking = $this->orchestrator->scheduleVideoCall($bookingUuid, $scheduledTime, $correlationId);

            $this->logger->info('Tourism video call scheduled via API', [
                'booking_uuid' => $booking->uuid,
                'scheduled_time' => $scheduledTime,
                'correlation_id' => $correlationId,
            ]);

            return (new TourismBookingResource($booking))
                ->additional([
                    'message' => 'Video call scheduled successfully',
                    'correlation_id' => $correlationId,
                ])
                ->response();
        } catch (\Throwable $e) {
            $this->logger->error('Tourism video call scheduling failed', [
                'booking_uuid' => $bookingUuid,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'error' => 'Video call scheduling failed',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Mark virtual tour as viewed.
     */
    public function markVirtualTourViewed(Request $request, string $bookingUuid): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $booking = $this->orchestrator->markVirtualTourViewed($bookingUuid, $correlationId);

            $this->logger->info('Tourism virtual tour marked as viewed', [
                'booking_uuid' => $booking->uuid,
                'correlation_id' => $correlationId,
            ]);

            return (new TourismBookingResource($booking))
                ->additional([
                    'message' => 'Virtual tour marked as viewed',
                    'correlation_id' => $correlationId,
                ])
                ->response();
        } catch (\Throwable $e) {
            $this->logger->error('Virtual tour marking failed', [
                'booking_uuid' => $bookingUuid,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'error' => 'Virtual tour marking failed',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Show booking details.
     */
    public function show(string $uuid): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $booking = TourBooking::where('uuid', $uuid)
                ->where('tenant_id', function_exists('tenant') && tenant() ? tenant()->id : 1)
                ->firstOrFail();

            return (new TourismBookingResource($booking))
                ->additional(['correlation_id' => $correlationId])
                ->response();
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Booking not found',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 404);
        }
    }

    /**
     * List user bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $userId = $request->input('user_id', auth()->id());
        $status = $request->input('status');

        try {
            $query = TourBooking::where('user_id', $userId)
                ->where('tenant_id', function_exists('tenant') && tenant() ? tenant()->id : 1);

            if ($status) {
                $query->where('status', $status);
            }

            $bookings = $query->orderBy('created_at', 'desc')->paginate(25);

            return (new TourismBookingCollection($bookings))
                ->additional(['correlation_id' => $correlationId])
                ->response();
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to fetch bookings',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
