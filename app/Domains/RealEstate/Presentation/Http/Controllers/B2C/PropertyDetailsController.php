<?php
declare(strict_types=1);

namespace App\Domains\RealEstate\Presentation\Http\Controllers\B2C;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class PropertyDetailsController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function show(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $property = $this->db->table('real_estate_properties')->where('id', $id)->first();

        if ($property === null) {
            return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Объект не найден'], 404);
        }

        $this->logger->info('Property details viewed', ['correlation_id' => $correlationId, 'property_id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'data' => $property]);
    }

    public function requestViewing(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'preferred_date' => 'required|date|after:now',
            'preferred_time' => 'nullable|string',
            'phone' => 'required|string',
        ]);

        $viewingId = $this->db->transaction(function () use ($validated, $request, $id, $correlationId) {
            return $this->db->table('real_estate_viewings')->insertGetId([
                'property_id' => $id,
                'user_id' => $request->user()?->id,
                'preferred_date' => $validated['preferred_date'],
                'preferred_time' => $validated['preferred_time'],
                'phone' => $validated['phone'],
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->logger->info('Viewing requested', ['correlation_id' => $correlationId, 'viewing_id' => $viewingId]);

        return new JsonResponse(['correlation_id' => $correlationId, 'id' => $viewingId, 'message' => 'Запрос на просмотр создан'], 201);
    }
}
