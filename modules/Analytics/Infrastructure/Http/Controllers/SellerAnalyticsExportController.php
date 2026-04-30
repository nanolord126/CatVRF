<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Analytics\Domain\ValueObjects\Period;
use Modules\Analytics\Exports\ProductMetricsExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Seller Analytics Export Controller
 *
 * Handles Excel export of seller analytics data.
 * Provides downloadable reports for sellers.
 */
final class SellerAnalyticsExportController
{
    /**
     * Export product metrics to Excel.
     *
     * Query parameters:
     * - period: last_7_days, last_30_days, last_90_days
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportProductMetrics(Request $request): BinaryFileResponse
    {
        $sellerId = Auth::id();
        $tenantId = tenant()?->id ?? (int) $request->header('X-Tenant-ID');
        $periodType = $request->get('period', 'last_30_days');

        if (!$sellerId) {
            abort(401, 'Unauthorized');
        }

        $period = match ($periodType) {
            'today' => Period::today(),
            'last_7_days' => Period::last7Days(),
            'last_30_days' => Period::last30Days(),
            'last_90_days' => Period::last90Days(),
            default => Period::last30Days(),
        };

        $fileName = "product-metrics-{$periodType}-{$sellerId}-".now()->format('Y-m-d').'.xlsx';

        return (new ProductMetricsExport($sellerId, $tenantId, $period))
            ->download($fileName);
    }
}
