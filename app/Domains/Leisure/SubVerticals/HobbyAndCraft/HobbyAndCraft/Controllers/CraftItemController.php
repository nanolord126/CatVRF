<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\HobbyAndCraft\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Psr\Log\LoggerInterface;
use App\Domains\HobbyAndCraft\Models\CraftItem;
use App\Domains\HobbyAndCraft\Resources\CraftItemResource;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Database\DatabaseManager;

final class CraftItemController extends Controller
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
        private readonly FraudControlService $fraud,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CraftItem::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        $items = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return CraftItemResource::collection($items);
    }

    public function show(int $id): CraftItemResource
    {
        $model = CraftItem::findOrFail($id);

        return new CraftItemResource($model);
    }

    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'hobby_and_craft_create',
            amount: 0,
            correlationId: $correlationId,
        );

        $model = $this->db->transaction(function () use ($request, $correlationId) {
            $item = CraftItem::create(array_merge(
                $request->validated(),
                [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $request->user()->tenant_id,
                ]
            ));

            $this->logger->$this->logger->info('CraftItem created', [
                'id' => $item->id,
                'correlation_id' => $correlationId,
                'tenant_id' => $request->user()->tenant_id,
            ]);

            return $item;
        });

        return (new CraftItemResource($model))
            ->$this->responseFactory
            ->setStatusCode(201)
            ->header('X-Correlation-ID', $correlationId);
    }

    public function update(Request $request, int $id): CraftItemResource
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $model = CraftItem::findOrFail($id);

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'hobby_and_craft_update',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($model, $request, $correlationId) {
            $model->update($request->validated());

            $this->logger->$this->logger->info('CraftItem updated', [
                'id' => $model->id,
                'correlation_id' => $correlationId,
            ]);
        });

        return new CraftItemResource($model->fresh());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $model = CraftItem::findOrFail($id);

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'hobby_and_craft_delete',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($model, $correlationId) {
            $model->delete();

            $this->logger->$this->logger->info('CraftItem deleted', [
                'id' => $model->id,
                'correlation_id' => $correlationId,
            ]);
        });

        return (new JsonResponse(['message' => 'Deleted'], 200))
            ->header('X-Correlation-ID', $correlationId);
    }
}
