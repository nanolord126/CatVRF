<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Shared\Notifications\Services\NotificationLogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

final class NotificationStatisticsController extends Controller
{
    private NotificationLogService $logService;

    public function __construct(NotificationLogService $logService)
    {
        $this->logService = $logService;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'tenant_id' => $request->input('tenant_id'),
            'channel' => $request->input('channel'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];

        $stats = $this->logService->getStatistics(array_filter($filters));

        return response()->json([
            'statistics' => $stats,
        ]);
    }

    public function overview(Request $request): JsonResponse
    {
        $filters = [
            'tenant_id' => $request->input('tenant_id'),
            'from' => $request->input('from', now()->subDays(30)->toDateString()),
            'to' => $request->input('to', now()->toDateString()),
        ];

        $stats = $this->logService->getStatistics(array_filter($filters));

        return response()->json([
            'overview' => [
                'total' => $stats['total'],
                'delivered' => $stats['delivered'],
                'failed' => $stats['failed'],
                'success_rate' => $stats['success_rate'],
                'delivery_rate' => $stats['delivery_rate'],
                'failure_rate' => $stats['failure_rate'],
            ],
        ]);
    }

    public function channelStats(Request $request, string $channel): JsonResponse
    {
        $filters = [
            'channel' => $channel,
            'tenant_id' => $request->input('tenant_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];

        $stats = $this->logService->getChannelStatistics($channel, array_filter($filters));

        return response()->json([
            'channel' => $channel,
            'statistics' => $stats,
        ]);
    }

    public function trends(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);
        $trends = $this->logService->getTrends($days);

        return response()->json([
            'trends' => $trends,
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        // For now, return JSON. In production, generate Excel/CSV
        $filters = [
            'tenant_id' => $request->input('tenant_id'),
            'channel' => $request->input('channel'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];

        $stats = $this->logService->getStatistics(array_filter($filters));

        return response()->json([
            'export_url' => route('api.notifications.export.download'),
            'statistics' => $stats,
        ]);
    }
}
