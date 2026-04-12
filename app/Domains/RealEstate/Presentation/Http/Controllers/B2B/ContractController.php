<?php
declare(strict_types=1);

namespace App\Domains\RealEstate\Presentation\Http\Controllers\B2B;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class ContractController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'property_id' => 'required|integer',
            'buyer_id' => 'required|integer',
            'price' => 'required|numeric|min:0',
            'terms' => 'nullable|string',
        ]);

        $id = $this->db->transaction(function () use ($validated, $request, $correlationId) {
            return $this->db->table('real_estate_contracts')->insertGetId(array_merge($validated, [
                'tenant_id' => $request->get('tenant_id'),
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        });

        $this->logger->info('Contract created', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'id' => $id, 'message' => 'Договор создан'], 201);
    }

    public function sign(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->db->transaction(function () use ($id, $request) {
            $this->db->table('real_estate_contracts')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->update(['status' => 'signed', 'signed_at' => now(), 'updated_at' => now()]);
        });

        $this->logger->info('Contract signed', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Договор подписан']);
    }
}
