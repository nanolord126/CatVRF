<?php

namespace App\Services\Automation;

use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class FraudDetectionService
{
    /**
     * Анализ активности на предмет аномалий (фрода).
     */
    public function detectAnomalies()
    {
        $alerts = [];

        // 1. Аномальное количество просмотров контактов (уже в StaffAuditLog, но расширим)
        $suspiciousViews = AuditLog::where('action', 'VIEW_CONTACT')
            ->where('created_at', '>=', now()->subHour())
            ->select('user_id', DB::raw('count(*) as view_count'))
            ->groupBy('user_id')
            ->having('view_count', '>', 50)
            ->get();

        foreach ($suspiciousViews as $view) {
            $alerts[] = [
                'type' => 'MASS_CONTACT_VIEW',
                'user_id' => $view->user_id,
                'severity' => 'high',
                'description' => "User viewed {$view->view_count} contacts in the last hour."
            ];
        }

        // 2. Аномальные возвраты / отмены (Inventory/Booking)
        // Если за час отменено более 5 заказов/броней одним сотрудником
        $suspiciousCancellations = AuditLog::whereIn('action', ['CANCEL_BOOKING', 'VOID_TRANSACTION'])
            ->where('created_at', '>=', now()->subHour())
            ->select('user_id', DB::raw('count(*) as cancel_count'))
            ->groupBy('user_id')
            ->having('cancel_count', '>', 5)
            ->get();

        foreach ($suspiciousCancellations as $cancel) {
            $alerts[] = [
                'type' => 'ABNORMAL_CANCELLATION_RATE',
                'user_id' => $cancel->user_id,
                'severity' => 'critical',
                'description' => "User cancelled {$cancel->cancel_count} operations in the last hour."
            ];
        }

        return $alerts;
    }
}
