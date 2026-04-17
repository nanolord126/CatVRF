<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;

use App\Domains\Auto\Requests\AIDiagnosticsRequest;
use App\Domains\Auto\Resources\AIDiagnosticsResource;
use App\Domains\Auto\Resources\VideoInspectionResource;
use App\Domains\Auto\Services\AIDiagnosticsService;
use App\Domains\Auto\Events\AIDiagnosticsCompletedEvent;
use App\Domains\Auto\Events\VideoInspectionInitiatedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

final class AIDiagnosticsController
{
    public function __construct(
        private readonly AIDiagnosticsService $diagnosticsService,
    ) {}

    public function diagnose(AIDiagnosticsRequest $request): JsonResponse
    {
        try {
            $dto = $request->toDto();
            $result = $this->diagnosticsService->diagnoseByPhotoAndVIN($dto);

            $vehicle = $this->diagnosticsService->getVehicleById($result['vehicle']['id']);

            if ($vehicle !== null) {
                Event::dispatch(new AIDiagnosticsCompletedEvent(
                    vehicle: $vehicle,
                    userId: $dto->userId,
                    tenantId: $dto->tenantId,
                    correlationId: $dto->correlationId,
                    diagnosticsData: $result,
                ));
            }

            Log::channel('audit')->info('auto.api.diagnostics.success', [
                'correlation_id' => $dto->correlationId,
                'user_id' => $dto->userId,
                'tenant_id' => $dto->tenantId,
            ]);

            return (new AIDiagnosticsResource($result))
                ->additional(['meta' => ['correlation_id' => $dto->correlationId]])
                ->response()
                ->setStatusCode(200);

        } catch (\Throwable $e) {
            Log::channel('audit')->error('auto.api.diagnostics.error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'correlation_id' => $request->header('X-Correlation-ID'),
            ], 500);
        }
    }

    public function initiateVideoInspection(Request $request, int $vehicleId): JsonResponse
    {
        try {
            $correlationId = $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();
            $userId = (int) $request->user()->id;
            $tenantId = (int) tenant()->id;

            $result = $this->diagnosticsService->initiateVideoInspection(
                vehicleId: $vehicleId,
                userId: $userId,
                tenantId: $tenantId,
                correlationId: $correlationId,
            );

            $vehicle = $this->diagnosticsService->getVehicleById($vehicleId);

            if ($vehicle !== null) {
                Event::dispatch(new VideoInspectionInitiatedEvent(
                    vehicle: $vehicle,
                    userId: $userId,
                    tenantId: $tenantId,
                    correlationId: $correlationId,
                    webrtcRoomId: $result['webrtc_room_id'],
                    webrtcToken: $result['webrtc_token'],
                ));
            }

            Log::channel('audit')->info('auto.api.video_inspection.initiated', [
                'correlation_id' => $correlationId,
                'vehicle_id' => $vehicleId,
                'user_id' => $userId,
            ]);

            return (new VideoInspectionResource($result))
                ->additional(['meta' => ['correlation_id' => $correlationId]])
                ->response()
                ->setStatusCode(200);

        } catch (\Throwable $e) {
            Log::channel('audit')->error('auto.api.video_inspection.error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'correlation_id' => $request->header('X-Correlation-ID'),
            ], 500);
        }
    }

    public function bookService(Request $request, int $vehicleId): JsonResponse
    {
        try {
            $correlationId = $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();
            $userId = (int) $request->user()->id;
            $tenantId = (int) tenant()->id;

            $validated = $request->validate([
                'work_items' => ['required', 'array', 'min:1'],
                'work_items.*.id' => ['required', 'string'],
                'work_items.*.location' => ['required', 'string'],
                'work_items.*.type' => ['required', 'string'],
                'work_items.*.description' => ['required', 'string'],
                'work_items.*.severity' => ['required', 'in:low,medium,high'],
                'work_items.*.estimated_hours' => ['required', 'integer', 'min:1'],
                'work_items.*.price' => ['required', 'numeric', 'min:0'],
                'work_items.*.priority' => ['required', 'in:normal,urgent'],
                'payment_split' => ['required', 'array'],
                'payment_split.wallet' => ['nullable', 'numeric', 'min:0'],
                'payment_split.card' => ['nullable', 'numeric', 'min:0'],
                'payment_split.credit_limit' => ['nullable', 'numeric', 'min:0'],
            ]);

            $order = $this->diagnosticsService->bookServiceWithSplitPayment(
                vehicleId: $vehicleId,
                workItems: $validated['work_items'],
                paymentSplit: $validated['payment_split'],
                userId: $userId,
                tenantId: $tenantId,
                correlationId: $correlationId,
            );

            Log::channel('audit')->info('auto.api.service_booking.success', [
                'correlation_id' => $correlationId,
                'order_id' => $order->id,
                'vehicle_id' => $vehicleId,
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'uuid' => $order->uuid,
                    'status' => $order->status,
                    'total_price' => $order->total_price,
                    'is_b2b' => $order->is_b2b,
                ],
                'correlation_id' => $correlationId,
            ], 201);

        } catch (\Throwable $e) {
            Log::channel('audit')->error('auto.api.service_booking.error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'correlation_id' => $request->header('X-Correlation-ID'),
            ], 500);
        }
    }
}
