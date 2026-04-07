<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers\Api\V1\B2C;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Domains\Beauty\Domain\Services\AppointmentService;
use App\Domains\Beauty\DTOs\BookAppointmentDto;
use Illuminate\Support\Str;

final class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentService $appointmentService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $dto = BookAppointmentDto::fromRequest($request, $correlationId);
        
        $appointment = $this->appointmentService->book($dto);
        
        return new JsonResponse([
            'success' => true,
            'data' => $appointment,
            'correlation_id' => $correlationId
        ], 201);
    }
}
