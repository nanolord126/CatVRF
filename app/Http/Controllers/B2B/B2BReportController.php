<?php declare(strict_types=1);

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Illuminate\Support\Str;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * B2BReportController — отчёты для B2B-клиентов.
 *
 * Эндпоинты:
 *   GET /turnover   — оборот за период
 *   GET /credit     — состояние кредитного лимита
 *   GET /orders     — история и статистика заказов
 */
final class B2BReportController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * GET /api/b2b/v1/reports/turnover
     *
     * Query params:
     *   from  — date Y-m-d (default: начало текущего месяца)
     *   to    — date Y-m-d (default: сегодня)
     */
    public function turnover(Request $request): JsonResponse
    {
        $correlationId  = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $businessGroup  = $request->attributes->get('b2b_business_group');
        $tenantId       = $request->input('b2b_tenant_id');

        $from = $request->query('from')
            ? \Carbon\Carbon::parse($request->query('from'))->startOfDay()
            : \Carbon\Carbon::now()->startOfMonth();

        $to = $request->query('to')
            ? \Carbon\Carbon::parse($request->query('to'))->endOfDay()
            : \Carbon\Carbon::now()->endOfDay();

        // Суммарный оборот из orders
        $stats = $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('business_group_id', $businessGroup->id)
            ->whereIn('status', ['completed', 'delivered'])
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('
                COUNT(*)                   AS orders_count,
                COALESCE(SUM(total_kopecks), 0) AS total_kopecks,
                COALESCE(AVG(total_kopecks), 0) AS avg_order_kopecks,
                MIN(created_at)            AS first_order_at,
                MAX(created_at)            AS last_order_at
            ')
            ->first();

        // Разбивка по неделям
        $weekly = $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('business_group_id', $businessGroup->id)
            ->whereIn('status', ['completed', 'delivered'])
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("
                DATE_TRUNC('week', created_at) AS week_start,
                COUNT(*)                       AS orders_count,
                SUM(total_kopecks)             AS total_kopecks
            ")
            ->groupByRaw("DATE_TRUNC('week', created_at)")
            ->orderByRaw("DATE_TRUNC('week', created_at)")
            ->get();

        return $this->response->json([
            'success'        => true,
            'period'         => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'data'           => [
                'orders_count'      => (int) ($stats->orders_count ?? 0),
                'total_kopecks'     => (int) ($stats->total_kopecks ?? 0),
                'total_rubles'      => round((int) ($stats->total_kopecks ?? 0) / 100, 2),
                'avg_order_kopecks' => (int) ($stats->avg_order_kopecks ?? 0),
                'first_order_at'    => $stats->first_order_at ?? null,
                'last_order_at'     => $stats->last_order_at ?? null,
            ],
            'weekly'         => $weekly->map(fn(object $w): array => [
                'week_start'    => $w->week_start,
                'orders_count'  => (int) $w->orders_count,
                'total_kopecks' => (int) $w->total_kopecks,
            ]),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * GET /api/b2b/v1/reports/credit
     * Состояние кредитного лимита и история использования.
     */
    public function credit(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $businessGroup = $request->attributes->get('b2b_business_group');

        $limitKopecks = (int) ($businessGroup->credit_limit_kopecks ?? 0);
        $usedKopecks  = (int) ($businessGroup->credit_used_kopecks  ?? 0);
        $freeKopecks  = max(0, $limitKopecks - $usedKopecks);

        // История использования кредита (из balance_transactions)
        $history = $this->db->table('balance_transactions')
            ->where('wallet_id', function ($sub) use ($businessGroup) {
                $sub->select('id')
                    ->from('wallets')
                    ->where('business_group_id', $businessGroup->id)
                    ->limit(1);
            })
            ->whereIn('type', ['b2b_credit', 'b2b_credit_release'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return $this->response->json([
            'success'        => true,
            'data'           => [
                'b2b_tier'           => $businessGroup->b2b_tier ?? 'standard',
                'credit_limit_kopecks' => $limitKopecks,
                'credit_used_kopecks'  => $usedKopecks,
                'credit_free_kopecks'  => $freeKopecks,
                'credit_limit_rubles'  => round($limitKopecks / 100, 2),
                'credit_used_rubles'   => round($usedKopecks  / 100, 2),
                'credit_free_rubles'   => round($freeKopecks  / 100, 2),
                'payment_term_days'    => $businessGroup->payment_term_days ?? 14,
            ],
            'history'        => $history->map(fn(object $t): array => [
                'id'             => $t->id,
                'type'           => $t->type,
                'amount_kopecks' => $t->amount,
                'created_at'     => $t->created_at,
            ]),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * GET /api/b2b/v1/reports/orders
     * Список заказов с пагинацией и статистикой.
     */
    public function orders(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $businessGroup = $request->attributes->get('b2b_business_group');
        $tenantId      = $request->input('b2b_tenant_id');

        $status = $request->query('status');
        $from   = $request->query('from')
            ? \Carbon\Carbon::parse($request->query('from'))->startOfDay()
            : \Carbon\Carbon::now()->subDays(30)->startOfDay();
        $to     = $request->query('to')
            ? \Carbon\Carbon::parse($request->query('to'))->endOfDay()
            : \Carbon\Carbon::now()->endOfDay();

        $query = $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('business_group_id', $businessGroup->id)
            ->whereBetween('created_at', [$from, $to])
            ->select(['id', 'uuid', 'status', 'total_kopecks', 'created_at', 'updated_at']);

        if ($status) {
            $query->where('status', $status);
        }

        $rows = $query->orderByDesc('created_at')->paginate(50);

        return $this->response->json([
            'success'        => true,
            'data'           => $rows->items(),
            'meta'           => [
                'total'    => $rows->total(),
                'per_page' => $rows->perPage(),
                'page'     => $rows->currentPage(),
            ],
            'correlation_id' => $correlationId,
        ]);
    }
}
