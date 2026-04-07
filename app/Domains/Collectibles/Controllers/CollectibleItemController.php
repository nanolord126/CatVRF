<?php declare(strict_types=1);

namespace App\Domains\Collectibles\Controllers;



use Illuminate\Contracts\Routing\ResponseFactory;
use Psr\Log\LoggerInterface;
use App\Domains\Collectibles\Models\CollectibleItem;
use App\Domains\Collectibles\Resources\CollectibleItemResource;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
final class CollectibleItemController extends Controller
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CollectibleItem::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $items = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return CollectibleItemResource::collection($items);
    }

    public function show(int $id): CollectibleItemResource
    {
        $model = CollectibleItem::findOrFail($id);

        return new CollectibleItemResource($model);
    }

    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'collectibles_create',
            amount: 0,
            correlationId: $correlationId,
        );

        $model = $this->db->transaction(function () use ($request, $correlationId) {
            $item = CollectibleItem::create(array_merge(
                $request->validated(),
                [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $request->user()->tenant_id,
                ]
            ));

            $this->logger->info('CollectibleItem created', [
                'id' => $item->id,
                'correlation_id' => $correlationId,
                'tenant_id' => $request->user()->tenant_id,
            ]);

            return $item;
        });

        return (new CollectibleItemResource($model))
            ->response()
            ->setStatusCode(201)
            ->header('X-Correlation-ID', $correlationId);
    }

    public function update(Request $request, int $id): CollectibleItemResource
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $model = CollectibleItem::findOrFail($id);

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'collectibles_update',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($model, $request, $correlationId) {
            $model->update($request->validated());

            $this->logger->info('CollectibleItem updated', [
                'id' => $model->id,
                'correlation_id' => $correlationId,
            ]);
        });

        return new CollectibleItemResource($model->fresh());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $model = CollectibleItem::findOrFail($id);

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'collectibles_delete',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($model, $correlationId) {
            $model->delete();

            $this->logger->info('CollectibleItem deleted', [
                'id' => $model->id,
                'correlation_id' => $correlationId,
            ]);
        });

        return (new \Illuminate\Http\JsonResponse(['message' => 'Deleted'], 200))
            ->header('X-Correlation-ID', $correlationId);
    }
}