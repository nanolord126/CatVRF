<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\BeautyMasters\Application\Services\AppointmentService;
use Modules\BeautyMasters\Application\Services\MasterScheduleService;
use Modules\BeautyMasters\Application\Services\BeautyCertificationService;
use Modules\BeautyMasters\Domain\Repositories\AppointmentRepositoryInterface;
use Modules\BeautyMasters\Domain\Repositories\MasterRepositoryInterface;
use Modules\BeautyMasters\Domain\Repositories\ServiceRepositoryInterface;
use Modules\BeautyMasters\Domain\DTOs\CreateAppointmentDTO;
use Modules\BeautyMasters\Domain\DTOs\UpdateAppointmentDTO;
use Psr\Log\LoggerInterface;

final readonly class BeautyMastersController
{
    public function __construct(
        private AppointmentService $appointmentService,
        private MasterScheduleService $masterScheduleService,
        private BeautyCertificationService $beautyCertificationService,
        private AppointmentRepositoryInterface $appointmentRepository,
        private MasterRepositoryInterface $masterRepository,
        private ServiceRepositoryInterface $serviceRepository,
        private LoggerInterface $logger,
    ) {}

    public function createAppointment(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'venue_id' => 'required|integer',
                'master_id' => 'required|integer|exists:beauty_masters,id',
                'client_id' => 'required|integer',
                'service_id' => 'required|integer|exists:beauty_services,id',
                'start_time' => 'required|date|after:now',
                'end_time' => 'required|date|after:start_time',
                'price' => 'required|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'currency' => 'required|string|max:3',
                'notes' => 'nullable|string|max:1000',
                'client_notes' => 'nullable|string|max:1000',
                'is_online_booking' => 'boolean',
                'booking_source' => 'nullable|string|max:50',
            ]);

            $dto = CreateAppointmentDTO::fromArray($request->all());
            $appointment = $this->appointmentService->createAppointment($dto);

            return new JsonResponse([
                'success' => true,
                'message' => 'Appointment created successfully',
                'appointment' => [
                    'id' => $appointment->id,
                    'venue_id' => $appointment->venueId,
                    'master_id' => $appointment->masterId,
                    'client_id' => $appointment->clientId,
                    'service_id' => $appointment->serviceId,
                    'start_time' => $appointment->startTime->format('Y-m-d H:i:s'),
                    'end_time' => $appointment->endTime->format('Y-m-d H:i:s'),
                    'status' => $appointment->status->value,
                    'final_price' => $appointment->finalPrice,
                    'currency' => $appointment->currency,
                ],
            ], 201);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Appointment creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function updateAppointment(Request $request, int $appointmentId): JsonResponse
    {
        try {
            $request->validate([
                'master_id' => 'nullable|integer',
                'start_time' => 'nullable|date',
                'end_time' => 'nullable|date|after:start_time',
                'notes' => 'nullable|string|max:1000',
                'client_notes' => 'nullable|string|max:1000',
            ]);

            $data = array_merge(['id' => $appointmentId], $request->all());
            $dto = UpdateAppointmentDTO::fromArray($data);
            $appointment = $this->appointmentService->updateAppointment($dto);

            return new JsonResponse([
                'success' => true,
                'message' => 'Appointment updated successfully',
                'appointment' => [
                    'id' => $appointment->id,
                    'start_time' => $appointment->startTime->format('Y-m-d H:i:s'),
                    'end_time' => $appointment->endTime->format('Y-m-d H:i:s'),
                    'status' => $appointment->status->value,
                ],
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Appointment update failed', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function confirmAppointment(Request $request, int $appointmentId): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->confirmAppointment($appointmentId);

            return new JsonResponse([
                'success' => true,
                'message' => 'Appointment confirmed successfully',
                'appointment' => [
                    'id' => $appointment->id,
                    'status' => $appointment->status->value,
                    'confirmed_at' => $appointment->confirmedAt?->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Appointment confirmation failed', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function startAppointment(Request $request, int $appointmentId): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->startAppointment($appointmentId);

            return new JsonResponse([
                'success' => true,
                'message' => 'Appointment started successfully',
                'appointment' => [
                    'id' => $appointment->id,
                    'status' => $appointment->status->value,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Appointment start failed', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function completeAppointment(Request $request, int $appointmentId): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->completeAppointment($appointmentId);

            return new JsonResponse([
                'success' => true,
                'message' => 'Appointment completed successfully',
                'appointment' => [
                    'id' => $appointment->id,
                    'status' => $appointment->status->value,
                    'completed_at' => $appointment->completedAt?->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Appointment completion failed', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function cancelAppointment(Request $request, int $appointmentId): JsonResponse
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:1000',
            ]);

            $appointment = $this->appointmentService->cancelAppointment(
                $appointmentId,
                $request->input('reason')
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Appointment cancelled successfully',
                'appointment' => [
                    'id' => $appointment->id,
                    'status' => $appointment->status->value,
                    'cancellation_reason' => $appointment->cancellationReason,
                ],
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Appointment cancellation failed', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function getAvailableSlots(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'master_id' => 'required|integer',
                'service_id' => 'required|integer',
                'date' => 'required|date|after_or_equal:today',
            ]);

            $slots = $this->appointmentService->getAvailableSlots(
                (int) $request->input('master_id'),
                (int) $request->input('service_id'),
                new \DateTimeImmutable($request->input('date'))
            );

            return new JsonResponse([
                'success' => true,
                'slots' => $slots,
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Available slots retrieval failed', [
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function getAppointment(Request $request, int $appointmentId): JsonResponse
    {
        try {
            $appointment = $this->appointmentRepository->findById($appointmentId);
            if (!$appointment) {
                return new JsonResponse(['error' => 'Appointment not found'], 404);
            }

            return new JsonResponse([
                'success' => true,
                'appointment' => [
                    'id' => $appointment->id,
                    'venue_id' => $appointment->venueId,
                    'master_id' => $appointment->masterId,
                    'client_id' => $appointment->clientId,
                    'service_id' => $appointment->serviceId,
                    'start_time' => $appointment->startTime->format('Y-m-d H:i:s'),
                    'end_time' => $appointment->endTime->format('Y-m-d H:i:s'),
                    'status' => $appointment->status->value,
                    'price' => $appointment->price,
                    'discount_amount' => $appointment->discountAmount,
                    'final_price' => $appointment->finalPrice,
                    'currency' => $appointment->currency,
                    'payment_status' => $appointment->paymentStatus,
                    'notes' => $appointment->notes,
                    'is_online_booking' => $appointment->isOnlineBooking,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Appointment retrieval failed', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }
}
