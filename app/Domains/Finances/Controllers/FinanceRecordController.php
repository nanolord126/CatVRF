<?php

declare(strict_types=1);

namespace App\Domains\Finances\Controllers;

use App\Domains\Finances\Events\FinanceRecordCreated;
use App\Domains\Finances\Events\FinanceRecordUpdated;
use App\Domains\Finances\Models\FinanceRecord;
use App\Domains\Finances\Resources\FinanceRecordResource;
use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Контроллер CRUD для финансовых записей.
 *
 * Все мутации обёрнуты в DB::transaction(),
 * проходят FraudControlService::check(),
 * логируются через AuditService с correlation_id.
 *
 * @package App\Domains\Finances\Controllers
 */
final class FinanceRecordController extends Controller
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
        private readonly FraudControlService $fraud,
        private readonly DatabaseManager $db,
        private readonly AuditService $audit,
        private readonly Dispatcher $events,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Список финансовых записей с пагинацией.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = FinanceRecord::query();

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('business_group_id')) {
            $query->where('business_group_id', (int) $request->input('business_group_id'));
        }

        $items = $query->orderByDesc('created_at')
            ->paginate(min($request->integer('per_page', 20), 100));

        return FinanceRecordResource::collection($items);
    }

    /**
     * Показать конкретную запись.
     */
    public function show(int $id): FinanceRecordResource
    {
        $model = FinanceRecord::findOrFail($id);

        return new FinanceRecordResource($model);
    }

    /**
     * Создать финансовую запись.
     */
    public function store(Request $request): JsonResponse
    {
        $correlationId = (string) $request->header(
            'X-Correlation-ID',
            Str::uuid()->toString(),
        );

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'finances_create',
            amount: (int) ($request->input('amount', 0)),
            correlationId: $correlationId,
        );

        $model = $this->db->transaction(function () use ($request, $correlationId): FinanceRecord {
            $item = FinanceRecord::create(array_merge(
                $request->validated(),
                [
                    'correlation_id' => $correlationId,
                    'tenant_id'      => $request->user()->tenant_id,
                ],
            ));

            $this->events->dispatch(new FinanceRecordCreated(
                financeRecord: $item,
                correlationId: $correlationId,
                userId: (int) $request->user()->id,
            ));

            $this->audit->record(
                action: 'finance_record_created',
                subjectType: FinanceRecord::class,
                subjectId: $item->id,
                oldValues: [],
                newValues: $item->toArray(),
                correlationId: $correlationId,
            );

            return $item;
        });

        return $this->responseFactory->json(
            (new FinanceRecordResource($model))->resolve(),
            201,
            ['X-Correlation-ID' => $correlationId],
        );
    }

    /**
     * Обновить финансовую запись.
     */
    public function update(Request $request, int $id): FinanceRecordResource
    {
        $correlationId = (string) $request->header(
            'X-Correlation-ID',
            Str::uuid()->toString(),
        );

        $model = FinanceRecord::findOrFail($id);
        $oldValues = $model->toArray();

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'finances_update',
            amount: (int) ($request->input('amount', 0)),
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($model, $request, $correlationId, $oldValues): void {
            $model->update($request->validated());

            $this->events->dispatch(new FinanceRecordUpdated(
                financeRecord: $model,
                correlationId: $correlationId,
                oldValues: $oldValues,
                newValues: $model->getChanges(),
                userId: (int) $request->user()->id,
            ));

            $this->audit->record(
                action: 'finance_record_updated',
                subjectType: FinanceRecord::class,
                subjectId: $model->id,
                oldValues: $oldValues,
                newValues: $model->getChanges(),
                correlationId: $correlationId,
            );
        });

        return new FinanceRecordResource($model->fresh());
    }

    /**
     * Удалить финансовую запись.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $correlationId = (string) $request->header(
            'X-Correlation-ID',
            Str::uuid()->toString(),
        );

        $model = FinanceRecord::findOrFail($id);

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'finances_delete',
            amount: (int) ($model->amount ?? 0),
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($model, $correlationId, $request): void {
            $deletedData = $model->toArray();
            $model->delete();

            $this->audit->record(
                action: 'finance_record_deleted',
                subjectType: FinanceRecord::class,
                subjectId: $model->id,
                oldValues: $deletedData,
                newValues: [],
                correlationId: $correlationId,
            );
        });

        return $this->responseFactory->json(
            ['message' => 'Deleted', 'correlation_id' => $correlationId],
            200,
            ['X-Correlation-ID' => $correlationId],
        );
    }
}