<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Controllers;

use App\Domains\Taxi\Services\TaxiFinanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class TaxiFinanceController extends Controller
{
    public function __construct(
        private readonly TaxiFinanceService $financeService,
    ) {}

    public function processPayment(Request $request, int $rideId): JsonResponse
    {
        $validated = $request->validate([
            'amount_kopeki' => 'required|integer|min:1',
            'payment_method' => 'required|string|in:card,cash,wallet,corporate,split',
            'split_payment_user_id' => 'nullable|integer',
        ]);

        $transaction = $this->financeService->processPayment(
            rideId: $rideId,
            amountKopeki: $validated['amount_kopeki'],
            paymentMethod: $validated['payment_method'],
            splitPaymentUserId: $validated['split_payment_user_id'] ?? null,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'transaction' => $transaction,
        ]);
    }

    public function processRefund(Request $request, int $transactionId): JsonResponse
    {
        $validated = $request->validate([
            'refund_amount_kopeki' => 'required|integer|min:1',
            'reason' => 'required|string',
        ]);

        $refund = $this->financeService->processRefund(
            transactionId: $transactionId,
            refundAmountKopeki: $validated['refund_amount_kopeki'],
            reason: $validated['reason'],
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'refund' => $refund,
        ]);
    }

    public function getDriverFinancialSummary(Request $request, int $driverId): JsonResponse
    {
        $summary = $this->financeService->getDriverFinancialSummary(
            driverId: $driverId,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }

    public function createWithdrawal(Request $request, int $driverId): JsonResponse
    {
        $validated = $request->validate([
            'amount_kopeki' => 'required|integer|min:10000',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'required|string',
            'bank_account_holder' => 'required|string',
            'bic' => 'nullable|string',
            'inn' => 'nullable|string',
            'kpp' => 'nullable|string',
        ]);

        $withdrawal = $this->financeService->createWithdrawal(
            driverId: $driverId,
            amountKopeki: $validated['amount_kopeki'],
            bankDetails: $validated,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'withdrawal' => $withdrawal,
        ]);
    }

    public function processWithdrawal(Request $request, int $withdrawalId): JsonResponse
    {
        $withdrawal = $this->financeService->processWithdrawal(
            withdrawalId: $withdrawalId,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'withdrawal' => $withdrawal,
        ]);
    }

    public function getTransactionHistory(Request $request, int $driverId): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'type' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $transactions = $this->financeService->getTransactionHistory(
            driverId: $driverId,
            startDate: $validated['start_date'] ?? null,
            endDate: $validated['end_date'] ?? null,
            type: $validated['type'] ?? null,
            perPage: $validated['per_page'] ?? 50,
        );

        return response()->json([
            'success' => true,
            'transactions' => $transactions,
        ]);
    }
}
