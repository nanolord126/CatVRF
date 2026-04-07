<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;




use Carbon\Carbon;
use App\Services\FraudControlService;
use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class BeautyServiceController extends Controller
{


    public function __construct(
        private FraudControlService $fraud,
        private BeautyServiceLogic $serviceLogic,
        private LoggerInterface $logger,
    ) {
    }

    public function index(): \Illuminate\Http\JsonResponse
    {
        $correlationId = (string) \Illuminate\Support\Str::uuid();
        try {
            $services = BeautyService::query()
                    ->where('is_active', true)
                    ->with(['salon', 'master'])
                    ->paginate(20);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $services, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('API Error: List Services Failed', ['c' => $correlationId, 'e' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Error', 'correlation_id' => $correlationId], 500);
            }
        }

        public function store(CreateBeautyServiceRequest $request): JsonResponse
        {
        $this->fraud->check(new \App\DTOs\OperationDto(correlationId: $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString()));

            $correlationId = (string) Str::uuid();
            try {
                $service = $this->serviceLogic->createService($request->validated(), $correlationId);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $service, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                $this->logger->error('API Error: Create Service Failed', ['c' => $correlationId, 'e' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => $correlationId], 400);
            }
        }

        public function show(BeautyService $service): JsonResponse
        {
            return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $service->load(['salon', 'master']), 'correlation_id' => (string) Str::uuid()]);
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
