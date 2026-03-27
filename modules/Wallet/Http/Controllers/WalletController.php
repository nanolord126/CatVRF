<?php declare(strict_types=1);

namespace App\Modules\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Wallet\Models\WalletTransaction;
use App\Modules\Wallet\Http\Requests\StoreWalletTransactionRequest;
use App\Modules\Wallet\Services\WalletService;
use App\Domains\Finances\Services\Security\FraudControlService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class WalletController extends Controller
{
    public function __construct(
        private readonly FraudControlService $fraudControl,
    ) {}

    /**
     * Получить транзакции кошелька.
     * Production 2026.
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            Log::channel('audit')->info('wallet.transactions.index.start', [
                'correlation_id' => $correlationId,
                'tenant_id' => tenant('id'),
                'user_id' => auth()->id(),
            ]);

            $perPage = (int) $request->input('per_page', 15);
            $transactions = WalletTransaction::where('tenant_id', tenant('id'))
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            Log::channel('audit')->info('wallet.transactions.index.success', [
                'correlation_id' => $correlationId,
                'count' => $transactions->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'correlation_id' => (string) $correlationId,
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->critical('wallet.transactions.index.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении транзакций',
                'correlation_id' => (string) $correlationId,
            ], 500);
        }
    }

    /**
     * Получить баланс кошелька.
     * Production 2026.
     */
    public function balance(): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            Log::channel('audit')->info('wallet.balance.start', [
                'correlation_id' => $correlationId,
                'user_id' => auth()->id(),
            ]);

            $balance = WalletTransaction::where('tenant_id', tenant('id'))
                ->where('user_id', auth()->id())
                ->where('status', 'completed')
                ->sum('amount');

            Log::channel('audit')->info('wallet.balance.success', [
                'correlation_id' => $correlationId,
                'balance' => $balance ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'data' => ['balance' => $balance ?? 0],
                'correlation_id' => (string) $correlationId,
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->critical('wallet.balance.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении баланса',
                'correlation_id' => (string) $correlationId,
            ], 500);
        }
    }

    /**
     * Пополнить кошелек.
     * Production 2026.
     */
    public function deposit(StoreWalletTransactionRequest $request): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            // Fraud check (обязателен перед мутацией)
            $fraudScore = $this->fraudControl->assessRisk(auth()->user(), [
                'amount' => $request->amount,
                'type' => 'deposit',
                'correlation_id' => $correlationId,
            ]);

            if ($fraudScore > 80) {
                Log::channel('audit')->warning('wallet.deposit.fraud.blocked', [
                    'correlation_id' => $correlationId,
                    'fraud_score' => $fraudScore,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Операция заблокирована системой безопасности',
                    'correlation_id' => (string) $correlationId,
                ], 403);
            }

            Log::channel('audit')->info('wallet.deposit.start', [
                'correlation_id' => $correlationId,
                'amount' => $request->amount,
                'fraud_score' => $fraudScore,
            ]);

            // Транзакция БД обязательна для всех мутаций
            $transaction = DB::transaction(function () use ($request, $correlationId) {
                return WalletTransaction::create([
                    'tenant_id' => tenant('id'),
                    'user_id' => auth()->id(),
                    'type' => 'deposit',
                    'amount' => (int) ($request->amount * 100), // копейки
                    'status' => 'pending',
                    'currency' => $request->currency ?? 'RUB',
                    'correlation_id' => (string) $correlationId,
                    'tags' => ['deposit', 'pending'],
                    'metadata' => $request->metadata ?? [],
                ]);
            });

            Log::channel('audit')->info('wallet.deposit.success', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
            ]);

            return response()->json([
                'success' => true,
                'data' => $transaction,
                'correlation_id' => (string) $correlationId,
            ], 201);
        } catch (Throwable $e) {
            Log::channel('audit')->critical('wallet.deposit.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при пополнении кошелька',
                'correlation_id' => (string) $correlationId,
            ], 500);
        }
    }

    /**
     * Вывести с кошелька.
     * Production 2026.
     */
    public function withdraw(StoreWalletTransactionRequest $request): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            // Fraud check
            $fraudScore = $this->fraudControl->assessRisk(auth()->user(), [
                'amount' => $request->amount,
                'type' => 'withdrawal',
                'correlation_id' => $correlationId,
            ]);

            if ($fraudScore > 80) {
                Log::channel('audit')->warning('wallet.withdraw.fraud.blocked', [
                    'correlation_id' => $correlationId,
                    'fraud_score' => $fraudScore,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Операция заблокирована',
                    'correlation_id' => (string) $correlationId,
                ], 403);
            }

            Log::channel('audit')->info('wallet.withdraw.start', [
                'correlation_id' => $correlationId,
                'amount' => $request->amount,
            ]);

            // Транзакция БД
            $transaction = DB::transaction(function () use ($request, $correlationId) {
                $currentBalance = WalletTransaction::where('tenant_id', tenant('id'))
                    ->where('user_id', auth()->id())
                    ->where('status', 'completed')
                    ->sum('amount');

                $requestAmountCents = (int) ($request->amount * 100);
                if ($currentBalance < $requestAmountCents) {
                    throw new \DomainException('Insufficient balance');
                }

                return WalletTransaction::create([
                    'tenant_id' => tenant('id'),
                    'user_id' => auth()->id(),
                    'type' => 'withdrawal',
                    'amount' => -$requestAmountCents,
                    'status' => 'pending',
                    'currency' => $request->currency ?? 'RUB',
                    'correlation_id' => (string) $correlationId,
                    'tags' => ['withdrawal', 'pending'],
                    'metadata' => $request->metadata ?? [],
                ]);
            });

            Log::channel('audit')->info('wallet.withdraw.success', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
            ]);

            return response()->json([
                'success' => true,
                'data' => $transaction,
                'correlation_id' => (string) $correlationId,
            ], 201);
        } catch (\DomainException $e) {
            Log::channel('audit')->warning('wallet.withdraw.insufficient_balance', [
                'correlation_id' => $correlationId,
                'amount' => $request->amount,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Недостаточно средств',
                'correlation_id' => (string) $correlationId,
            ], 422);
        } catch (Throwable $e) {
            Log::channel('audit')->critical('wallet.withdraw.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при выводе',
                'correlation_id' => (string) $correlationId,
            ], 500);
        }
    }

    /**
     * Получить детали транзакции.
     * Production 2026.
     */
    public function show(WalletTransaction $transaction): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            $this->authorize('view', $transaction);

            Log::channel('audit')->info('wallet.transaction.show', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $transaction,
                'correlation_id' => (string) $correlationId,
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->warning('wallet.transaction.unauthorized', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
                'user_id' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещён',
                'correlation_id' => (string) $correlationId,
            ], 403);
        }
    }

    /**
     * История транзакций.
     * Production 2026.
     */
    public function history(Request $request): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            Log::channel('audit')->info('wallet.history.start', [
                'correlation_id' => $correlationId,
                'user_id' => auth()->id(),
            ]);

            $perPage = (int) $request->input('per_page', 20);
            $history = WalletTransaction::where('tenant_id', tenant('id'))
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            Log::channel('audit')->info('wallet.history.success', [
                'correlation_id' => $correlationId,
                'count' => $history->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $history,
                'correlation_id' => (string) $correlationId,
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->critical('wallet.history.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении истории',
                'correlation_id' => (string) $correlationId,
            ], 500);
        }
    }

    /**
     * Выписка по счёту.
     * Production 2026.
     */
    public function statement(Request $request): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            Log::channel('audit')->info('wallet.statement.start', [
                'correlation_id' => $correlationId,
                'user_id' => auth()->id(),
            ]);

            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            
            $query = WalletTransaction::where('tenant_id', tenant('id'))
                ->where('user_id', auth()->id());
            
            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }
            
            $statement = $query->orderBy('created_at', 'desc')->get();

            Log::channel('audit')->info('wallet.statement.success', [
                'correlation_id' => $correlationId,
                'count' => $statement->count(),
                'period' => ['from' => $fromDate, 'to' => $toDate],
            ]);

            return response()->json([
                'success' => true,
                'data' => ['statement' => $statement],
                'correlation_id' => (string) $correlationId,
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->critical('wallet.statement.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при формировании выписки',
                'correlation_id' => (string) $correlationId,
            ], 500);
        }
    }
}
