<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Presentation\B2B\API\Controllers;


use Psr\Log\LoggerInterface;
use App\Domains\Beauty\Application\B2B\DTOs\BookAppointmentDTO;
use App\Domains\Beauty\Application\B2B\DTOs\CancelAppointmentDTO;
use App\Domains\Beauty\Application\B2B\DTOs\CompleteAppointmentDTO;
use App\Domains\Beauty\Application\B2B\DTOs\ConfirmAppointmentDTO;
use App\Domains\Beauty\Application\B2B\UseCases\BookAppointmentUseCase;
use App\Domains\Beauty\Application\B2B\UseCases\CancelAppointmentUseCase;
use App\Domains\Beauty\Application\B2B\UseCases\CompleteAppointmentUseCase;
use App\Domains\Beauty\Application\B2B\UseCases\ConfirmAppointmentUseCase;
use App\Domains\Beauty\Presentation\B2B\API\Requests\BookAppointmentRequest;
use App\Domains\Beauty\Presentation\B2B\API\Requests\CancelAppointmentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

final class AppointmentController extends Controller
{
    public function __construct(
        private BookAppointmentUseCase $bookUseCase,
        private ConfirmAppointmentUseCase $confirmUseCase,
        private CompleteAppointmentUseCase $completeUseCase,
        private CancelAppointmentUseCase $cancelUseCase,
        private LoggerInterface $logger,
    ) {}

    /**
     * Список записей текущего тенанта.
     *
     * Фильтрация по статусу, дате, мастеру. Пагинация 20 записей.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $query = \App\Domains\Beauty\Models\Appointment::query()
                ->where('tenant_id', $tenantId)
                ->with(['master', 'service', 'salon'])
                ->orderByDesc('start_at');

            if ($request->filled('status')) {
                $query->where('status', $request->string('status')->toString());
            }

            if ($request->filled('master_id')) {
                $query->where('master_id', $request->integer('master_id'));
            }

            if ($request->filled('date_from')) {
                $query->where('start_at', '>=', $request->string('date_from')->toString());
            }

            if ($request->filled('date_to')) {
                $query->where('start_at', '<=', $request->string('date_to')->toString());
            }

            $appointments = $query->paginate(20);

            $this->logger->info('Beauty B2B: список записей', [
                'tenant_id'      => $tenantId,
                'count'          => $appointments->total(),
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success'        => true,
                'correlation_id' => $correlationId,
                'data'           => $appointments->items(),
                'meta'           => [
                    'current_page' => $appointments->currentPage(),
                    'last_page'    => $appointments->lastPage(),
                    'per_page'     => $appointments->perPage(),
                    'total'        => $appointments->total(),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Beauty B2B: ошибка получения записей', [
                'error'          => $e->getMessage(),
                'tenant_id'      => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success'        => false,
                'message'        => 'Не удалось получить список записей.',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Создать запись (B2B).
     */
    public function store(BookAppointmentRequest $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $dto = new BookAppointmentDTO(
                tenantId: $request->user()->tenant_id,
                clientId: $request->integer('client_id'),
                salonUuid: $request->string('salon_uuid')->toString(),
                masterUuid: $request->string('master_uuid')->toString(),
                serviceUuid: $request->string('service_uuid')->toString(),
                startAt: \Carbon\CarbonImmutable::parse($request->string('start_at')->toString()),
                correlationId: $correlationId,
            );

            $appointment = $this->bookUseCase->handle($dto);

            $this->logger->info('Beauty B2B: запись создана', [
                'tenant_id'      => $dto->tenantId,
                'correlation_id' => $correlationId,
            ]);

            return new \Illuminate\Http\JsonResponse([
                'success'        => true,
                'correlation_id' => $correlationId,
                'data'           => $appointment->toArray(),
            ], 201);
        } catch (\DomainException $e) {
            $this->logger->error('Beauty B2B: ошибка создания записи', [
                'error'          => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return new \Illuminate\Http\JsonResponse([
                'success'        => false,
                'message'        => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }

    /**
     * Подтвердить запись.
     */
    public function confirm(Request $request, string $uuid): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $this->confirmUseCase->handle(new ConfirmAppointmentDTO(
                tenantId: $request->user()->tenant_id,
                confirmedByUserId: $request->user()->id,
                appointmentUuid: $uuid,
                correlationId: $correlationId,
            ));

            $this->logger->info('Beauty B2B: запись подтверждена', [
                'appointment_uuid' => $uuid,
                'tenant_id'        => $request->user()->tenant_id,
                'correlation_id'   => $correlationId,
            ]);

            return new \Illuminate\Http\JsonResponse([
                'success'        => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (\DomainException $e) {
            $this->logger->error('Beauty B2B: ошибка подтверждения', [
                'appointment_uuid' => $uuid,
                'error'            => $e->getMessage(),
                'correlation_id'   => $correlationId,
            ]);

            return new \Illuminate\Http\JsonResponse([
                'success'        => false,
                'message'        => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }

    /**
     * Завершить запись.
     */
    public function complete(Request $request, string $uuid): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $this->completeUseCase->handle(new CompleteAppointmentDTO(
                tenantId: $request->user()->tenant_id,
                completedByUserId: $request->user()->id,
                appointmentUuid: $uuid,
                correlationId: $correlationId,
            ));

            $this->logger->info('Beauty B2B: запись завершена', [
                'appointment_uuid' => $uuid,
                'tenant_id'        => $request->user()->tenant_id,
                'correlation_id'   => $correlationId,
            ]);

            return new \Illuminate\Http\JsonResponse([
                'success'        => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (\DomainException $e) {
            $this->logger->error('Beauty B2B: ошибка завершения', [
                'appointment_uuid' => $uuid,
                'error'            => $e->getMessage(),
                'correlation_id'   => $correlationId,
            ]);

            return new \Illuminate\Http\JsonResponse([
                'success'        => false,
                'message'        => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }

    /**
     * Отменить запись.
     */
    public function cancel(CancelAppointmentRequest $request, string $uuid): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $this->cancelUseCase->handle(new CancelAppointmentDTO(
                tenantId: $request->user()->tenant_id,
                cancelledByUserId: $request->user()->id,
                appointmentUuid: $uuid,
                reason: $request->string('reason')->toString(),
                correlationId: $correlationId,
            ));

            $this->logger->info('Beauty B2B: запись отменена', [
                'appointment_uuid' => $uuid,
                'tenant_id'        => $request->user()->tenant_id,
                'correlation_id'   => $correlationId,
            ]);

            return new \Illuminate\Http\JsonResponse([
                'success'        => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (\DomainException $e) {
            $this->logger->error('Beauty B2B: ошибка отмены', [
                'appointment_uuid' => $uuid,
                'error'            => $e->getMessage(),
                'correlation_id'   => $correlationId,
            ]);

            return new \Illuminate\Http\JsonResponse([
                'success'        => false,
                'message'        => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }
}
