<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Presentation\B2B\API\Controllers;


use Psr\Log\LoggerInterface;
use App\Domains\Beauty\Application\B2B\DTOs\CreateMasterDTO;
use App\Domains\Beauty\Application\B2B\UseCases\CreateMasterUseCase;
use App\Domains\Beauty\Presentation\B2B\API\Requests\CreateMasterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

final class MasterController extends Controller
{
    public function __construct(
        private CreateMasterUseCase $createUseCase,
        private LoggerInterface $logger,
    ) {}

    /**
     * Список мастеров текущего тенанта.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $this->logger->info('Beauty B2B: список мастеров', [
            'tenant_id' => $tenantId,
            'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
        ]);

        return new \Illuminate\Http\JsonResponse([
            'message' => 'Используйте Filament-панель для просмотра мастеров.',
        ]);
    }

    /**
     * Создать мастера (B2B).
     */
    public function store(CreateMasterRequest $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $dto = new CreateMasterDTO(
                tenantId: $request->user()->tenant_id,
                createdByUserId: $request->user()->id,
                salonUuid: $request->string('salon_uuid')->toString(),
                name: $request->string('name')->toString(),
                specialization: $request->string('specialization')->toString(),
                experienceYears: $request->integer('experience_years'),
                workDays: $request->array('work_days'),
                workStart: $request->string('work_start')->toString(),
                workEnd: $request->string('work_end')->toString(),
                correlationId: $correlationId,
            );

            $master = $this->createUseCase->handle($dto);

            $this->logger->info('Beauty B2B: мастер создан', [
                'tenant_id'      => $dto->tenantId,
                'correlation_id' => $correlationId,
            ]);

            return new \Illuminate\Http\JsonResponse([
                'success'        => true,
                'correlation_id' => $correlationId,
                'data'           => $master->toArray(),
            ], 201);
        } catch (\DomainException $e) {
            $this->logger->error('Beauty B2B: ошибка создания мастера', [
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
     * Показать мастера.
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $this->logger->info('Beauty B2B: просмотр мастера', [
            'tenant_id'   => $tenantId,
            'master_uuid' => $uuid,
            'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
        ]);

        return new \Illuminate\Http\JsonResponse([
            'message' => 'Используйте Filament-панель для редактирования мастеров.',
        ]);
    }
}
