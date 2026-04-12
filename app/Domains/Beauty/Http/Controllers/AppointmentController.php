<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class AppointmentController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->logger->info('AppointmentController::store', ['correlation_id' => $correlationId, ]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => [],
            'message' => 'store выполнен',
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->logger->info('AppointmentController::show', ['correlation_id' => $correlationId, 'show' => $id, ]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => [],
            'message' => 'show выполнен',
        ], 200);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->logger->info('AppointmentController::cancel', ['correlation_id' => $correlationId, 'cancel' => $id, ]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => [],
            'message' => 'cancel выполнен',
        ], 200);
    }

    public function confirm(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->logger->info('AppointmentController::confirm', ['correlation_id' => $correlationId, 'confirm' => $id, ]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => [],
            'message' => 'confirm выполнен',
        ], 200);
    }

    public function reschedule(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->logger->info('AppointmentController::reschedule', ['correlation_id' => $correlationId, ]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => [],
            'message' => 'reschedule выполнен',
        ], 200);
    }

    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->logger->info('AppointmentController::index', ['correlation_id' => $correlationId, ]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => [],
            'message' => 'index выполнен',
        ], 200);
    }

    public function __invoke(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->logger->info('AppointmentController invoked', ['correlation_id' => $correlationId]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => [],
        ]);
    }
}
