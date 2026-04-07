<?php declare(strict_types=1);

namespace App\Domains\AI\Controllers;

use App\Domains\AI\Models\AIModel;
use App\Domains\AI\Resources\AIModelResource;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Class AIModelController
 *
 * Part of the AI vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * API controller handling HTTP requests for AI models.
 * All responses include correlation_id header.
 * Write operations are protected by FraudControlService.
 *
 * @package App\Domains\AI\Controllers
 */
final class AIModelController extends Controller
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
        private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * List AI models with optional search filter.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = AIModel::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $items = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return AIModelResource::collection($items);
    }

    /**
     * Show a single AI model by ID.
     */
    public function show(int $id): AIModelResource
    {
        $model = AIModel::findOrFail($id);

        return new AIModelResource($model);
    }

    /**
     * Store a new AI model with fraud check and audit logging.
     */
    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'a_i_create',
            amount: 0,
            correlationId: $correlationId,
        );

        $model = $this->db->transaction(function () use ($request, $correlationId) {
            $item = AIModel::create(array_merge(
                $request->validated(),
                [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $request->user()->tenant_id,
                ]
            ));

            $this->logger->info('AIModel created', [
                'id' => $item->id,
                'correlation_id' => $correlationId,
                'tenant_id' => $request->user()->tenant_id,
            ]);

            return $item;
        });

        return $this->responseFactory->json(
            (new AIModelResource($model))->toArray($request),
            201,
            ['X-Correlation-ID' => $correlationId],
        );
    }

    /**
     * Update an existing AI model with fraud check and audit logging.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $model = AIModel::findOrFail($id);

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'a_i_update',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($model, $request, $correlationId): void {
            $model->update($request->validated());

            $this->logger->info('AIModel updated', [
                'id' => $model->id,
                'correlation_id' => $correlationId,
            ]);
        });

        return $this->responseFactory->json(
            (new AIModelResource($model->fresh()))->toArray($request),
            200,
            ['X-Correlation-ID' => $correlationId],
        );
    }

    /**
     * Delete an AI model with fraud check and audit logging.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $model = AIModel::findOrFail($id);

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'a_i_delete',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($model, $correlationId): void {
            $model->delete();

            $this->logger->info('AIModel deleted', [
                'id' => $model->id,
                'correlation_id' => $correlationId,
            ]);
        });

        return $this->responseFactory->json(
            ['message' => 'Deleted'],
            200,
            ['X-Correlation-ID' => $correlationId],
        );
    }
}
