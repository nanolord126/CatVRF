<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Payment\Application\Services\PaymentRulesService;
use Modules\Payment\Application\Services\AMLService;
use Modules\Payment\Application\Services\FiscalizationService;
use Illuminate\Support\Facades\Auth;

/**
 * Payment Compliance API Controller.
 * 
 * Provides REST API endpoints for federal law compliance features:
 * - ФЗ-161: Payment rules management
 * - ФЗ-115: AML/KYC checks
 * - 54-ФЗ: Fiscal receipts
 */
final class PaymentComplianceController extends Controller
{
    public function __construct(
        private readonly PaymentRulesService $paymentRules,
        private readonly AMLService $amlService,
        private readonly FiscalizationService $fiscalization,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | ФЗ-161 Endpoints
    |--------------------------------------------------------------------------
    */

    /**
     * Validate payment against ФЗ-161 requirements.
     */
    public function validateFZ161(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount_kopecks' => 'required|integer|min:0',
            'user_id' => 'nullable|integer',
            'tenant_id' => 'nullable|integer',
            'settlement_method' => 'required|string|in:bank_account,sbp',
            'fraud_check_passed' => 'nullable|boolean',
        ]);

        $result = $this->paymentRules->validateUnder161($validated);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get active payment rules.
     */
    public function getPaymentRules(Request $request): JsonResponse
    {
        $category = $request->query('category');
        
        if ($category) {
            $rules = $this->paymentRules->getActiveRulesByCategory($category);
        } else {
            $rules = [
                'transaction_limits' => $this->paymentRules->getActiveRulesByCategory('transaction_limits'),
                'fraud_detection' => $this->paymentRules->getActiveRulesByCategory('fraud_detection'),
                'settlement' => $this->paymentRules->getActiveRulesByCategory('settlement'),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $rules,
        ]);
    }

    /**
     * Get payment rule history.
     */
    public function getRuleHistory(string $code): JsonResponse
    {
        $history = $this->paymentRules->getRuleHistory($code);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Export payment rules for audit.
     */
    public function exportRules(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after:from',
        ]);

        $export = $this->paymentRules->exportRulesForAudit(
            from: new \DateTime($validated['from']),
            to: new \DateTime($validated['to']),
        );

        return response()->json([
            'success' => true,
            'data' => $export,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ФЗ-115 Endpoints
    |--------------------------------------------------------------------------
    */

    /**
     * Get AML check by UUID.
     */
    public function getAMLCheck(string $uuid): JsonResponse
    {
        $check = $this->amlService->getCheck($uuid);

        if (! $check) {
            return response()->json([
                'success' => false,
                'error' => 'AML check not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $check->toArray(),
        ]);
    }

    /**
     * Get AML checks for user.
     */
    public function getUserAMLChecks(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
        ]);

        $checks = $this->amlService->getUserChecks(
            userId: $userId,
            limit: $validated['limit'] ?? 50,
            offset: $validated['offset'] ?? 0,
        );

        return response()->json([
            'success' => true,
            'data' => array_map(fn($check) => $check->toArray(), $checks),
        ]);
    }

    /**
     * Get pending AML checks requiring manual review.
     */
    public function getPendingAMLChecks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        // This would need a method in AMLService
        // For now, return empty array
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }

    /**
     * Mark AML check as reported to Rosfinmonitoring.
     */
    public function markAMLAsReported(string $uuid): JsonResponse
    {
        $check = $this->amlService->markAsReported($uuid);

        return response()->json([
            'success' => true,
            'data' => $check->toArray(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 54-ФЗ Endpoints
    |--------------------------------------------------------------------------
    */

    /**
     * Get fiscal receipt by UUID.
     */
    public function getFiscalReceipt(string $uuid): JsonResponse
    {
        $receipt = $this->fiscalization->getReceipt($uuid);

        if (! $receipt) {
            return response()->json([
                'success' => false,
                'error' => 'Fiscal receipt not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $receipt->toArray(),
        ]);
    }

    /**
     * Get fiscal receipts by payment intent.
     */
    public function getPaymentIntentReceipts(string $paymentIntentUuid): JsonResponse
    {
        $receipts = $this->fiscalization->getReceiptsByPaymentIntent($paymentIntentUuid);

        return response()->json([
            'success' => true,
            'data' => array_map(fn($receipt) => $receipt->toArray(), $receipts),
        ]);
    }

    /**
     * Get pending fiscal receipts.
     */
    public function getPendingFiscalReceipts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $receipts = $this->fiscalization->getPendingReceipts(
            limit: $validated['limit'] ?? 100,
        );

        return response()->json([
            'success' => true,
            'data' => array_map(fn($receipt) => $receipt->toArray(), $receipts),
        ]);
    }

    /**
     * Retry failed fiscal receipt.
     */
    public function retryFiscalReceipt(string $uuid): JsonResponse
    {
        $receipt = $this->fiscalization->getReceipt($uuid);

        if (! $receipt) {
            return response()->json([
                'success' => false,
                'error' => 'Fiscal receipt not found',
            ], 404);
        }

        if (! $receipt->canRetry()) {
            return response()->json([
                'success' => false,
                'error' => 'Receipt cannot be retried (max retries exceeded)',
            ], 400);
        }

        // Dispatch retry job
        dispatch(new \App\Jobs\Payments\FiscalReceiptRetryJob($uuid));

        return response()->json([
            'success' => true,
            'message' => 'Fiscal receipt retry queued',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard Endpoints
    |--------------------------------------------------------------------------
    */

    /**
     * Get compliance dashboard statistics.
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'nullable|string|in:24h,7d,30d',
            'tenant_id' => 'nullable|integer',
        ]);

        $period = $validated['period'] ?? '24h';
        $tenantId = $validated['tenant_id'] ?? (Auth::check() ? Auth::user()->tenant_id : null);

        $stats = [
            'fz161' => [
                'violations_count' => $this->getFZ161ViolationsCount($period, $tenantId),
                'blocked_payments' => $this->getBlockedPaymentsCount($period, $tenantId),
                'transaction_volume' => $this->getTransactionVolume($period, $tenantId),
            ],
            'fz115' => [
                'total_checks' => $this->getAMLChecksCount($period, $tenantId),
                'high_risk_count' => $this->getHighRiskAMLCount($period, $tenantId),
                'blocked_count' => $this->getBlockedAMLCount($period, $tenantId),
                'pending_review' => $this->getPendingReviewAMLCount($tenantId),
            ],
            'fz54' => [
                'total_receipts' => $this->getFiscalReceiptsCount($period, $tenantId),
                'pending_receipts' => $this->getPendingFiscalCount($tenantId),
                'failed_receipts' => $this->getFailedFiscalCount($period, $tenantId),
                'confirmed_receipts' => $this->getConfirmedFiscalCount($period, $tenantId),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get compliance trend data for charts.
     */
    public function getComplianceTrends(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'nullable|string|in:7d,30d,90d',
            'metric' => 'required|string|in:aml_checks,fiscal_receipts,fz161_violations',
        ]);

        $period = $validated['period'] ?? '30d';
        $metric = $validated['metric'];

        // This would query ClickHouse for trend data
        // For now, return mock data
        $trends = $this->getMockTrendData($metric, $period);

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    private function getFZ161ViolationsCount(string $period, ?int $tenantId): int
    {
        $startDate = match ($period) {
            '24h' => now()->subHours(24),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subHours(24),
        };

        $query = \DB::table('payment_rule_violations')
            ->where('created_at', '>=', $startDate);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->count();
    }

    private function getBlockedPaymentsCount(string $period, ?int $tenantId): int
    {
        $startDate = match ($period) {
            '24h' => now()->subHours(24),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subHours(24),
        };

        $query = \DB::table('payment_records')
            ->where('status', 'blocked')
            ->where('created_at', '>=', $startDate);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->count();
    }

    private function getTransactionVolume(string $period, ?int $tenantId): int
    {
        $startDate = match ($period) {
            '24h' => now()->subHours(24),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subHours(24),
        };

        $query = \DB::table('payment_records')
            ->where('status', 'succeeded')
            ->where('created_at', '>=', $startDate);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return (int) $query->sum('amount_kopecks');
    }

    private function getAMLChecksCount(string $period, ?int $tenantId): int
    {
        $startDate = match ($period) {
            '24h' => now()->subHours(24),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subHours(24),
        };

        $query = \DB::table('aml_checks')
            ->where('checked_at', '>=', $startDate);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->count();
    }

    private function getHighRiskAMLCount(string $period, ?int $tenantId): int
    {
        $startDate = match ($period) {
            '24h' => now()->subHours(24),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subHours(24),
        };

        $query = \DB::table('aml_checks')
            ->where('risk_score', '>=', 70)
            ->where('checked_at', '>=', $startDate);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->count();
    }

    private function getBlockedAMLCount(string $period, ?int $tenantId): int
    {
        $startDate = match ($period) {
            '24h' => now()->subHours(24),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subHours(24),
        };

        $query = \DB::table('aml_checks')
            ->where('passed', false)
            ->where('checked_at', '>=', $startDate);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->count();
    }

    private function getPendingReviewAMLCount(?int $tenantId): int
    {
        $query = \DB::table('aml_checks')
            ->where('passed', false)
            ->where('risk_score', '>=', 70);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->count();
    }

    private function getFiscalReceiptsCount(string $period, ?int $tenantId): int
    {
        $startDate = match ($period) {
            '24h' => now()->subHours(24),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subHours(24),
        };

        $query = \DB::table('fiscal_documents')
            ->where('created_at', '>=', $startDate);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->count();
    }

    private function getPendingFiscalCount(?int $tenantId): int
    {
        $query = \DB::table('fiscal_documents')
            ->where('status', 'pending');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->count();
    }

    private function getFailedFiscalCount(string $period, ?int $tenantId): int
    {
        $startDate = match ($period) {
            '24h' => now()->subHours(24),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subHours(24),
        };

        $query = \DB::table('fiscal_documents')
            ->where('status', 'failed')
            ->where('created_at', '>=', $startDate);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->count();
    }

    private function getConfirmedFiscalCount(string $period, ?int $tenantId): int
    {
        $startDate = match ($period) {
            '24h' => now()->subHours(24),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subHours(24),
        };

        $query = \DB::table('fiscal_documents')
            ->where('status', 'confirmed')
            ->where('created_at', '>=', $startDate);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->count();
    }

    private function getMockTrendData(string $metric, string $period): array
    {
        $days = match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 30,
        };

        $data = [];
        for ($i = $days; $i >= 0; $i--) {
            $data[] = [
                'date' => now()->subDays($i)->format('Y-m-d'),
                'value' => rand(10, 100),
            ];
        }

        return [
            'metric' => $metric,
            'period' => $period,
            'data' => $data,
        ];
    }
}
