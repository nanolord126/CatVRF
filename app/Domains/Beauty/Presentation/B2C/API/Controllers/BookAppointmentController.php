<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Presentation\B2C\API\Controllers;


use Psr\Log\LoggerInterface;
use App\Domains\Beauty\Application\B2C\DTOs\BookAppointmentB2CDTO;
use App\Domains\Beauty\Application\B2C\UseCases\BookAppointmentB2CUseCase;
use App\Domains\Beauty\Presentation\B2C\API\Requests\BookAppointmentB2CRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

final class BookAppointmentController extends Controller
{
    public function __construct(
        private BookAppointmentB2CUseCase $bookUseCase,
        private LoggerInterface $logger,
    ) {}

    /**
     * Онлайн-запись клиента (B2C публичный).
     */
    public function store(BookAppointmentB2CRequest $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $dto = new BookAppointmentB2CDTO(
                clientId: $request->user()->id,
                salonUuid: $request->string('salon_uuid')->toString(),
                masterUuid: $request->string('master_uuid')->toString(),
                serviceUuid: $request->string('service_uuid')->toString(),
                startAt: \Carbon\CarbonImmutable::parse($request->string('start_at')->toString()),
                correlationId: $correlationId,
            );

            $appointment = $this->bookUseCase->handle($dto);

            $this->logger->info('Beauty B2C: запись создана клиентом', [
                'client_id'      => $dto->clientId,
                'salon_uuid'     => $dto->salonUuid,
                'correlation_id' => $correlationId,
            ]);

            return new \Illuminate\Http\JsonResponse([
                'success'        => true,
                'correlation_id' => $correlationId,
                'data'           => $appointment->toArray(),
            ], 201);
        } catch (\DomainException $e) {
            $this->logger->error('Beauty B2C: ошибка онлайн-записи', [
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
}
