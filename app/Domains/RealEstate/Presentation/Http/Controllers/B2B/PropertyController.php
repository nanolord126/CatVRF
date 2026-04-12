<?php
declare(strict_types=1);

namespace App\Domains\RealEstate\Presentation\Http\Controllers\B2B;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class PropertyController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:apartment,house,commercial,land',
            'address' => 'required|string',
            'price' => 'required|numeric|min:0',
            'rooms' => 'nullable|integer|min:1',
            'area_sqm' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $id = $this->db->transaction(function () use ($validated, $request, $correlationId) {
            return $this->db->table('real_estate_properties')->insertGetId(array_merge($validated, [
                'tenant_id' => $request->get('tenant_id'),
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        });

        $this->logger->info('B2B property created', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'id' => $id, 'message' => 'Объект создан'], 201);
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->db->transaction(function () use ($id, $request) {
            $this->db->table('real_estate_properties')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->update(['status' => 'published', 'is_active' => true, 'updated_at' => now()]);
        });

        $this->logger->info('B2B property published', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Объект опубликован']);
    }
}
