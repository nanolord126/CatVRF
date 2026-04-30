<?php

declare(strict_types=1);

namespace Modules\Veterinary\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Veterinary\Application\Services\AppointmentService;
use Modules\Veterinary\Domain\DTOs\CreateAppointmentDto;
use Ramsey\Uuid\UuidInterface;

final readonly class AppointmentController
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
        private readonly UuidInterface $uuid,
    ) {}

    public function create(Request $request): JsonResponse
    {
        $dto = CreateAppointmentDto::from($request, $this->uuid);
        $appointment = $this->appointmentService->create($dto->data, $dto->correlationId);

        return new JsonResponse([
            'data' => $appointment,
            'message' => 'Appointment created successfully',
        ], Response::HTTP_CREATED);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string']);
        $appointment = $this->appointmentService->cancel($id, $request->input('reason'));

        return new JsonResponse([
            'data' => $appointment,
            'message' => 'Appointment cancelled successfully',
        ]);
    }

    public function confirm(int $id): JsonResponse
    {
        $appointment = $this->appointmentService->confirm($id);

        return new JsonResponse([
            'data' => $appointment,
            'message' => 'Appointment confirmed successfully',
        ]);
    }

    public function complete(int $id): JsonResponse
    {
        $appointment = $this->appointmentService->complete($id);

        return new JsonResponse([
            'data' => $appointment,
            'message' => 'Appointment completed successfully',
        ]);
    }
}
