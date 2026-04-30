<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Services;

use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;

final readonly class NotificationLogService
{
    public function log(array $data): NotificationLog
    {
        return NotificationLog::create(array_merge($data, [
            'status' => 'pending',
        ]));
    }

    public function markAsSent(int $logId): bool
    {
        $log = NotificationLog::find($logId);
        if (!$log) {
            return false;
        }

        $log->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return true;
    }

    public function markAsDelivered(int $logId): bool
    {
        $log = NotificationLog::find($logId);
        if (!$log) {
            return false;
        }

        $log->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        return true;
    }

    public function markAsFailed(int $logId, string $errorMessage): bool
    {
        $log = NotificationLog::find($logId);
        if (!$log) {
            return false;
        }

        $log->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);

        return true;
    }

    public function getStatistics(array $filters = []): array
    {
        $query = NotificationLog::query();

        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (isset($filters['from']) && isset($filters['to'])) {
            $query->whereBetween('created_at', [$filters['from'], $filters['to']]);
        }

        $total = $query->count();
        $sent = (clone $query)->where('status', 'sent')->count();
        $delivered = (clone $query)->where('status', 'delivered')->count();
        $failed = (clone $query)->whereIn('status', ['failed', 'bounced'])->count();
        $pending = (clone $query)->where('status', 'pending')->count();

        $successRate = $total > 0 ? round((($sent + $delivered) / $total) * 100, 2) : 0;
        $deliveryRate = $total > 0 ? round(($delivered / $total) * 100, 2) : 0;
        $failureRate = $total > 0 ? round(($failed / $total) * 100, 2) : 0;

        // Channel breakdown
        $channelStats = (clone $query)
            ->selectRaw('channel, status, COUNT(*) as count')
            ->groupBy('channel', 'status')
            ->get()
            ->groupBy('channel')
            ->map(function ($group) {
                $total = $group->sum('count');
                $delivered = $group->where('status', 'delivered')->sum('count');
                $failed = $group->whereIn('status', ['failed', 'bounced'])->sum('count');

                return [
                    'total' => $total,
                    'delivered' => $delivered,
                    'failed' => $failed,
                    'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
                ];
            });

        // Event type breakdown
        $eventStats = (clone $query)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->get()
            ->toArray();

        return [
            'total' => $total,
            'sent' => $sent,
            'delivered' => $delivered,
            'failed' => $failed,
            'pending' => $pending,
            'success_rate' => $successRate,
            'delivery_rate' => $deliveryRate,
            'failure_rate' => $failureRate,
            'by_channel' => $channelStats,
            'by_event' => $eventStats,
        ];
    }

    public function getChannelStatistics(string $channel, array $filters = []): array
    {
        return $this->getStatistics(array_merge($filters, ['channel' => $channel]));
    }

    public function getTrends(int $days = 30): array
    {
        $from = now()->subDays($days)->startOfDay();
        $to = now()->endOfDay();

        $trends = NotificationLog::selectRaw('DATE(created_at) as date, channel, status, COUNT(*) as count')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('date', 'channel', 'status')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($group) {
                $total = $group->sum('count');
                $delivered = $group->where('status', 'delivered')->sum('count');
                $failed = $group->whereIn('status', ['failed', 'bounced'])->sum('count');

                return [
                    'date' => $group->first()->date,
                    'total' => $total,
                    'delivered' => $delivered,
                    'failed' => $failed,
                    'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
                ];
            });

        return $trends->values()->toArray();
    }
}
