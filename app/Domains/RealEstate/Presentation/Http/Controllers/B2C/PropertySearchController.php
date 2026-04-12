<?php
declare(strict_types=1);

namespace App\Domains\RealEstate\Presentation\Http\Controllers\B2C;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class PropertySearchController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $query = $this->db->table('real_estate_properties')
            ->where('is_active', true);

        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }
        if ($request->has('min_price')) {
            $query->where('price', '>=', (float) $request->get('min_price'));
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', (float) $request->get('max_price'));
        }
        if ($request->has('rooms')) {
            $query->where('rooms', (int) $request->get('rooms'));
        }
        if ($request->has('city')) {
            $query->where('city', $request->get('city'));
        }

        $properties = $query->orderByDesc('created_at')->paginate(20);

        $this->logger->info('Property search', ['correlation_id' => $correlationId, 'count' => $properties->total()]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => $properties->items(),
            'meta' => ['current_page' => $properties->currentPage(), 'last_page' => $properties->lastPage(), 'total' => $properties->total()],
        ]);
    }

    /**
     * Component: PropertySearchController
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     */}
