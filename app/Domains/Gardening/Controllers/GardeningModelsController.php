<?php declare(strict_types=1);

namespace App\Domains\Gardening\Controllers;



use Illuminate\Contracts\Routing\ResponseFactory;
use Psr\Log\LoggerInterface;
use App\Domains\Gardening\Models\GardeningModels;
use App\Domains\Gardening\Resources\GardeningModelsResource;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
final class GardeningModelsController extends Controller
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = GardeningModels::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $items = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return GardeningModelsResource::collection($items);
    }

    public function show(int $id): GardeningModelsResource
    {
        $model = GardeningModels::findOrFail($id);

        return new GardeningModelsResource($model);
    }

    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'gardening_create',
            amount: 0,
            correlationId: $correlationId,
        );

        $model = $this->db->transaction(function () use ($request, $correlationId) {
            $item = GardeningModels::create(array_merge(
                $request->validated(),
                [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $request->user()->tenant_id,
                ]
            ));

            $this->logger->info('GardeningModels created', [
                'id' => $item->id,
                'correlation_id' => $correlationId,
                'tenant_id' => $request->user()->tenant_id,
            ]);

            return $item;
        });

        return (new GardeningModelsResource($model))
            ->$this->responseFactory
            ->setStatusCode(201)
            ->header('X-Correlation-ID', $correlationId);
    }

    public function update(Request $request, int $id): GardeningModelsResource
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $model = GardeningModels::findOrFail($id);

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'gardening_update',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($model, $request, $correlationId) {
            $model->update($request->validated());

            $this->logger->info('GardeningModels updated', [
                'id' => $model->id,
                'correlation_id' => $correlationId,
            ]);
        });

        return new GardeningModelsResource($model->fresh());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $model = GardeningModels::findOrFail($id);

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'gardening_delete',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($model, $correlationId) {
            $model->delete();

            $this->logger->info('GardeningModels deleted', [
                'id' => $model->id,
                'correlation_id' => $correlationId,
            ]);
        });

        return (new \Illuminate\Http\JsonResponse(['message' => 'Deleted'], 200))
            ->header('X-Correlation-ID', $correlationId);
    }
}