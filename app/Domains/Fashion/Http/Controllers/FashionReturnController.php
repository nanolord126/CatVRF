<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Controllers;

use App\Domains\Fashion\Models\FashionReturn;
use App\Domains\Fashion\Services\ReturnService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class FashionReturnController extends Controller
{
    public function __construct(
        private readonly ReturnService $returnService,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Список возвратов текущего пользователя.
     */
    public function myReturns(Request $request): JsonResponse
    {
        $userId = (int) $request->user()?->id;

        $returns = FashionReturn::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return new JsonResponse([
            'success' => true,
            'data' => $returns,
            'correlation_id' => $request->header('X-Correlation-ID', (string) Str::uuid()),
        ]);
    }

    /**
     * Создать заявку на возврат.
     */
    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $userId = (int) $request->user()?->id;

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_return',
            amount: (int) $request->get('amount_kopecks', 0),
            correlationId: $correlationId,
        );

        $return = $this->returnService->createReturn(
            userId: $userId,
            orderId: (int) $request->get('order_id'),
            reason: (string) $request->get('reason'),
            amountKopecks: (int) $request->get('amount_kopecks'),
            correlationId: $correlationId,
        );

        return new JsonResponse([
            'success' => true,
            'data' => $return,
            'correlation_id' => $correlationId,
        ], 201);
    }

    /**
     * Получить заявку на возврат по ID.
     */
    public function show(Request $request, int $returnId): JsonResponse
    {
        $return = FashionReturn::findOrFail($returnId);

        return new JsonResponse([
            'success' => true,
            'data' => $return,
            'correlation_id' => $request->header('X-Correlation-ID', (string) Str::uuid()),
        ]);
    }

    /**
     * Обновить заявку на возврат.
     */
    public function update(Request $request, int $returnId): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $return = FashionReturn::findOrFail($returnId);
        $previousData = $return->toArray();

        $return->update([
            'reason' => $request->get('reason', $return->reason),
            'correlation_id' => $correlationId,
        ]);

        $this->audit->log(
            action: 'fashion_return_updated',
            subjectType: FashionReturn::class,
            subjectId: $returnId,
            old: $previousData,
            new: $return->fresh()->toArray(),
            correlationId: $correlationId,
        );

        return new JsonResponse([
            'success' => true,
            'data' => $return->fresh(),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Все возвраты (для менеджера / Tenant Panel).
     */
    public function all(Request $request): JsonResponse
    {
        $returns = FashionReturn::orderBy('created_at', 'desc')
            ->paginate(20);

        return new JsonResponse([
            'success' => true,
            'data' => $returns,
            'correlation_id' => $request->header('X-Correlation-ID', (string) Str::uuid()),
        ]);
    }

    /**
     * Утвердить возврат.
     */
    public function approve(Request $request, int $returnId): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $return = $this->returnService->approveReturn(
            returnId: $returnId,
            correlationId: $correlationId,
        );

        return new JsonResponse([
            'success' => true,
            'data' => $return,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Отклонить возврат.
     */
    public function reject(Request $request, int $returnId): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $reason = (string) $request->get('rejection_reason', '');

        $return = $this->returnService->rejectReturn(
            returnId: $returnId,
            reason: $reason,
            correlationId: $correlationId,
        );

        return new JsonResponse([
            'success' => true,
            'data' => $return,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Аналитика по возвратам.
     */
    public function analytics(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $totalReturns = FashionReturn::count();
        $pendingReturns = FashionReturn::where('status', 'pending')->count();
        $approvedReturns = FashionReturn::where('status', 'approved')->count();
        $rejectedReturns = FashionReturn::where('status', 'rejected')->count();
        $totalAmountKopecks = FashionReturn::where('status', 'approved')->sum('amount_kopecks');

        $this->logger->info('Fashion return analytics accessed', [
            'correlation_id' => $correlationId,
        ]);

        return new JsonResponse([
            'success' => true,
            'data' => [
                'total' => $totalReturns,
                'pending' => $pendingReturns,
                'approved' => $approvedReturns,
                'rejected' => $rejectedReturns,
                'total_amount_kopecks' => (int) $totalAmountKopecks,
            ],
            'correlation_id' => $correlationId,
        ]);
    }
}
