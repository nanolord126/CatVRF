<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Wallet;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Wallet Controller — кошелёк пользователя (баланс, транзакции, статистика).
 */
final class WalletController extends Controller
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * GET /wallet — текущий баланс кошелька.
     */
    public function show(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $tenantId = (int) $request->header('X-Tenant-ID', '0');

            $wallet = $this->db->table('wallets')
                ->where('tenant_id', $tenantId)
                ->first();

            if ($wallet === null) {
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'data' => [
                        'current_balance' => 0,
                        'hold_amount' => 0,
                        'available_balance' => 0,
                    ],
                ], 200);
            }

            $available = (int) $wallet->current_balance - (int) $wallet->hold_amount;

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => [
                    'wallet_id' => $wallet->id,
                    'current_balance' => (int) $wallet->current_balance,
                    'hold_amount' => (int) $wallet->hold_amount,
                    'available_balance' => $available,
                ],
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Wallet show failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve wallet',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /wallet/transactions — история транзакций.
     */
    public function getTransactions(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $tenantId = (int) $request->header('X-Tenant-ID', '0');

            $wallet = $this->db->table('wallets')
                ->where('tenant_id', $tenantId)
                ->first();

            if ($wallet === null) {
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'data' => [],
                    'meta' => ['total' => 0],
                ], 200);
            }

            $query = $this->db->table('balance_transactions')
                ->where('wallet_id', $wallet->id);

            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }

            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', $request->input('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', $request->input('date_to') . ' 23:59:59');
            }

            $transactions = $query->orderBy('created_at', 'desc')
                ->paginate((int) $request->input('per_page', 20));

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $transactions->items(),
                'meta' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'total' => $transactions->total(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Wallet transactions failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve transactions',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /wallet/stats — статистика по кошельку.
     */
    public function getStats(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $tenantId = (int) $request->header('X-Tenant-ID', '0');

            $wallet = $this->db->table('wallets')
                ->where('tenant_id', $tenantId)
                ->first();

            if ($wallet === null) {
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'data' => [
                        'total_income' => 0,
                        'total_expenses' => 0,
                        'transactions_count' => 0,
                    ],
                ], 200);
            }

            $totalDeposits = (int) $this->db->table('balance_transactions')
                ->where('wallet_id', $wallet->id)
                ->where('type', 'deposit')
                ->sum('amount');

            $totalWithdrawals = (int) $this->db->table('balance_transactions')
                ->where('wallet_id', $wallet->id)
                ->where('type', 'withdrawal')
                ->sum('amount');

            $totalBonuses = (int) $this->db->table('balance_transactions')
                ->where('wallet_id', $wallet->id)
                ->where('type', 'bonus')
                ->sum('amount');

            $count = $this->db->table('balance_transactions')
                ->where('wallet_id', $wallet->id)
                ->count();

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => [
                    'current_balance' => (int) $wallet->current_balance,
                    'hold_amount' => (int) $wallet->hold_amount,
                    'total_deposits' => $totalDeposits,
                    'total_withdrawals' => $totalWithdrawals,
                    'total_bonuses' => $totalBonuses,
                    'transactions_count' => $count,
                ],
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Wallet stats failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve wallet stats',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
