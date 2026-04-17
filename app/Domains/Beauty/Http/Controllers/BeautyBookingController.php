<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use App\Domains\Beauty\DTOs\BookAppointmentDto;
use App\Domains\Beauty\DTOs\MatchMastersByPhotoDto;
use App\Domains\Beauty\Http\Requests\BookAppointmentRequest;
use App\Domains\Beauty\Jobs\ProcessBeautyAiMatchingJob;
use App\Domains\Beauty\Resources\AppointmentResource;
use App\Domains\Beauty\Resources\MasterMatchResource;
use App\Domains\Beauty\Services\BeautyBookingService;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Str;

final class BeautyBookingController
{
    public function __construct(
        private BeautyBookingService $bookingService,
        private Queue $queue,
        private ResponseFactory $response,
    ) {}

    public function book(BookAppointmentRequest $request): JsonResponse
    {
        $dto = BookAppointmentDto::fromRequest($request, $request->getCorrelationId());

        $appointment = $this->bookingService->bookAppointment($dto);

        return $this->response->json([
            'success' => true,
            'data' => new AppointmentResource($appointment),
            'correlation_id' => $request->getCorrelationId(),
        ], 201);
    }

    public function matchMasters(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png', 'max:5120'],
            'salon_id' => ['nullable', 'integer'],
            'preferred_style' => ['nullable', 'string'],
            'max_distance' => ['nullable', 'numeric'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $dto = MatchMastersByPhotoDto::fromRequest(
            $request,
            $request->user()->id,
            $request->user()->tenant_id ?? 1,
        );

        $result = $this->bookingService->matchMastersByPhoto(
            photo: $dto->photo,
            userId: $dto->userId,
            tenantId: $dto->tenantId,
            correlationId: $dto->correlationId,
        );

        return $this->response->json([
            'success' => true,
            'data' => [
                'style_profile' => $result['style_profile'],
                'ar_preview_url' => $result['ar_preview_url'],
                'recommended_masters' => MasterMatchResource::collection($result['recommended_masters']),
            ],
            'correlation_id' => $result['correlation_id'],
        ]);
    }

    public function matchMastersAsync(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png', 'max:5120'],
            'salon_id' => ['nullable', 'integer'],
            'preferred_style' => ['nullable', 'string'],
            'max_distance' => ['nullable', 'numeric'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $dto = MatchMastersByPhotoDto::fromRequest(
            $request,
            $request->user()->id,
            $request->user()->tenant_id ?? 1,
        );

        $this->queue->push(new ProcessBeautyAiMatchingJob($dto));

        return $this->response->json([
            'success' => true,
            'message' => 'AI matching job queued',
            'job_id' => Str::uuid()->toString(),
            'correlation_id' => $dto->correlationId,
        ], 202);
    }

    public function initiateVideoCall(int $appointmentId, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $result = $this->bookingService->initiateVideoCall(
            appointmentId: $appointmentId,
            userId: $request->user()->id,
            tenantId: $request->user()->tenant_id ?? 1,
            correlationId: $correlationId,
        );

        return $this->response->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    public function processPayment(int $appointmentId, Request $request): JsonResponse
    {
        $request->validate([
            'payment_split' => ['required', 'array'],
            'payment_split.wallet' => ['nullable', 'numeric', 'min:0'],
            'payment_split.card' => ['nullable', 'numeric', 'min:0'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $appointment = $this->bookingService->processPaymentWithSplit(
            appointmentId: $appointmentId,
            userId: $request->user()->id,
            tenantId: $request->user()->tenant_id ?? 1,
            paymentSplit: $request->input('payment_split'),
            correlationId: $correlationId,
        );

        return $this->response->json([
            'success' => true,
            'data' => new AppointmentResource($appointment),
            'correlation_id' => $correlationId,
        ]);
    }

    public function cancel(int $appointmentId, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $appointment = $this->bookingService->cancelAppointment(
            appointmentId: $appointmentId,
            userId: $request->user()->id,
            tenantId: $request->user()->tenant_id ?? 1,
            reason: $request->input('reason'),
            correlationId: $correlationId,
        );

        return $this->response->json([
            'success' => true,
            'data' => new AppointmentResource($appointment),
            'correlation_id' => $correlationId,
        ]);
    }
}
