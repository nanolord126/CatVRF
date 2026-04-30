<?php

declare(strict_types=1);

namespace App\Domains\Shared\Insurance\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Psr\Log\LoggerInterface;
use App\Domains\Shared\Insurance\Models\InsuranceCompany;
use App\Domains\Shared\Insurance\Resources\InsuranceCompanyResource;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Database\DatabaseManager;

final class InsuranceCompanyController extends Controller
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
        private readonly FraudControlService $fraud,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = InsuranceCompany::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        $items = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return InsuranceCompanyResource::collection($items);
    }

    public function show(int $id): InsuranceCompanyResource
    {
        $model = InsuranceCompany::findOrFail($id);

        return new InsuranceCompanyResource($model);
    }

    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'insurance_create',
            amount: 0,
            correlationId: $correlationId,
        );

        $model = $this->db->transaction(function () use ($request, $correlationId) {
            $item = InsuranceCompany::create(array_merge(
                $request->validated(),
                [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $request->user()->tenant_id,
                ]
            ));

            $this->logger->$this->logger->info('InsuranceCompany created', [
                'id' => $item->id,
                'correlation_id' => $correlationId,
                'tenant_id' => $request->user()->tenant_id,
            ]);

            return $item;
        });

        return (new InsuranceCompanyResource($model))
            ->$this->responseFactory
            ->setStatusCode(201)
            ->header('X-Correlation-ID', $correlationId);
    }

    public function update(Request $request, int $id): InsuranceCompanyResource
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $model = InsuranceCompany::findOrFail($id);

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'insurance_update',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($model, $request, $correlationId) {
            $model->update($request->validated());

            $this->logger->$this->logger->info('InsuranceCompany updated', [
                'id' => $model->id,
                'correlation_id' => $correlationId,
            ]);
        });

        return new InsuranceCompanyResource($model->fresh());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $model = InsuranceCompany::findOrFail($id);

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'insurance_delete',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($model, $correlationId) {
            $model->delete();

            $this->logger->$this->logger->info('InsuranceCompany deleted', [
                'id' => $model->id,
                'correlation_id' => $correlationId,
            ]);
        });

        return (new JsonResponse(['message' => 'Deleted'], 200))
            ->header('X-Correlation-ID', $correlationId);
    }
}
