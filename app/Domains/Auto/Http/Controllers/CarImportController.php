<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;

use App\Domains\Auto\Http\Requests\CarImportRequest;
use App\Domains\Auto\Resources\CarImportResource;
use App\Domains\Auto\Services\CarImportService;
use App\Domains\Auto\Events\CarImportCalculatedEvent;
use App\Domains\Auto\Events\CarImportInitiatedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;

final class CarImportController
{
    public function __construct(
        private readonly CarImportService $importService,
        private readonly DatabaseManager $db,
        private readonly LogManager $logger,
    ) {}

    public function calculateDuties(CarImportRequest $request): JsonResponse
    {
        try {
            $dto = $request->toDto();
            $result = $this->importService->calculateCustomsDuties($dto);

            CarImportCalculatedEvent::dispatch(
                vin: $dto->vin,
                userId: $dto->userId,
                tenantId: $dto->tenantId,
                correlationId: $dto->correlationId,
                calculationData: $result,
            );

            $this->logger->channel('audit')->info('car.api.import.calculation.success', [
                'correlation_id' => $dto->correlationId,
                'user_id' => $dto->userId,
                'tenant_id' => $dto->tenantId,
                'vin' => $dto->vin,
            ]);

            return (new CarImportResource($result))
                ->additional(['meta' => ['correlation_id' => $dto->correlationId]])
                ->response()
                ->setStatusCode(200);

        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('car.api.import.calculation.error', [
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

    public function initiateImport(CarImportRequest $request): JsonResponse
    {
        try {
            $dto = $request->toDto();
            $documents = [];

            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $documents[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $file->store('import-documents', 'public'),
                        'size' => $file->getSize(),
                    ];
                }
            }

            $result = $this->importService->initiateImportProcess($dto, $documents);

            Event::dispatch(new CarImportInitiatedEvent(
                importId: $result['import_id'],
                vin: $dto->vin,
                userId: $dto->userId,
                tenantId: $dto->tenantId,
                correlationId: $dto->correlationId,
                isB2b: $dto->isB2b,
            ));

            $this->logger->channel('audit')->info('car.api.import.initiated.success', [
                'correlation_id' => $dto->correlationId,
                'user_id' => $dto->userId,
                'import_id' => $result['import_id'],
                'vin' => $dto->vin,
            ]);

            return response()->json([
                'success' => true,
                'import_id' => $result['import_id'],
                'vehicle_id' => $result['vehicle_id'],
                'total_duties_rub' => $result['total_duties_rub'],
                'status' => $result['status'],
                'correlation_id' => $dto->correlationId,
            ], 201);

        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('car.api.import.initiate.error', [
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

    public function payDuties(Request $request, int $importId): JsonResponse
    {
        try {
            $correlationId = $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();
            $userId = (int) $request->user()->id;
            $tenantId = (int) tenant()->id;

            $result = $this->importService->payCustomsDuties(
                importId: $importId,
                userId: $userId,
                tenantId: $tenantId,
                correlationId: $correlationId,
            );

            $this->logger->channel('audit')->info('car.api.import.payment.success', [
                'correlation_id' => $correlationId,
                'import_id' => $importId,
                'user_id' => $userId,
            ]);

            return response()->json(array_merge($result, ['correlation_id' => $correlationId]), 200);

        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('car.api.import.payment.error', [
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

    public function getImportStatus(Request $request, int $importId): JsonResponse
    {
        try {
            $import = $this->db->table('car_imports')
                ->where('id', $importId)
                ->where('tenant_id', tenant()->id)
                ->first();

            if ($import === null) {
                return response()->json([
                    'success' => false,
                    'error' => 'Import not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'import' => [
                    'id' => $import->id,
                    'vin' => $import->vin,
                    'status' => $import->status,
                    'country_origin' => $import->country_origin,
                    'declared_value' => $import->declared_value,
                    'currency' => $import->currency,
                    'created_at' => $import->created_at,
                    'paid_at' => $import->paid_at,
                ],
            ], 200);

        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('car.api.import.status.error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
