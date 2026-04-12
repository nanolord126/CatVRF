<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Beauty;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Beauty Analytics API Controller — аналитика для владельцев бизнеса.
 */
final class AnalyticsController extends Controller
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * GET /analytics/revenue — выручка за период.
     */
    public function revenue(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $tenantId = (int) $request->header('X-Tenant-ID', '0');
            $from = $request->input('from', now()->subDays(30)->toDateString());
            $to = $request->input('to', now()->toDateString());

            $revenue = $this->db->table('beauty_appointments')
                ->where('tenant_id', $tenantId)
                ->where('status', 'confirmed')
                ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
                ->sum('price');

            $count = $this->db->table('beauty_appointments')
                ->where('tenant_id', $tenantId)
                ->where('status', 'confirmed')
                ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
                ->count();

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => [
                    'total_revenue' => (int) $revenue,
                    'appointments_count' => $count,
                    'period' => ['from' => $from, 'to' => $to],
                ],
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Revenue analytics failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve revenue analytics',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /analytics/appointments — статистика записей за период.
     */
    public function appointments(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $tenantId = (int) $request->header('X-Tenant-ID', '0');
            $from = $request->input('from', now()->subDays(30)->toDateString());
            $to = $request->input('to', now()->toDateString());

            $byStatus = $this->db->table('beauty_appointments')
                ->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->get()
                ->pluck('total', 'status')
                ->toArray();

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => [
                    'by_status' => $byStatus,
                    'period' => ['from' => $from, 'to' => $to],
                ],
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Appointments analytics failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve appointments analytics',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /analytics/masters — рейтинг и загруженность мастеров.
     */
    public function masters(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $tenantId = (int) $request->header('X-Tenant-ID', '0');

            $mastersStats = $this->db->table('beauty_masters')
                ->where('beauty_masters.tenant_id', $tenantId)
                ->where('beauty_masters.is_active', true)
                ->leftJoin('beauty_appointments', function ($join) {
                    $join->on('beauty_masters.id', '=', 'beauty_appointments.master_id')
                        ->where('beauty_appointments.status', '=', 'confirmed')
                        ->where('beauty_appointments.created_at', '>=', now()->subDays(30));
                })
                ->selectRaw('beauty_masters.id, beauty_masters.full_name, beauty_masters.rating, COUNT(beauty_appointments.id) as appointments_count')
                ->groupBy('beauty_masters.id', 'beauty_masters.full_name', 'beauty_masters.rating')
                ->orderBy('appointments_count', 'desc')
                ->get();

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $mastersStats,
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Masters analytics failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve masters analytics',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
