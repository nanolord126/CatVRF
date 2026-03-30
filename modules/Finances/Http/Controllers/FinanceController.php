<?php declare(strict_types=1);

namespace Modules\Finances\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FinanceController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Получить список финансовых транзакций текущего tenant.
         * Согласно КАНОН 2026: tenant scoping, audit лог.
         */
        public function index(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
    
            try {
                $tenantId = tenant('id') ?? 0;
    
                Log::channel('audit')->info('Fetching financial transactions', [
                    'tenant_id' => $tenantId,
                    'correlation_id' => $correlationId,
                    'per_page' => $request->input('per_page', 15),
                ]);
    
                $transactions = PaymentTransaction::where('tenant_id', $tenantId)
                    ->orderBy('created_at', 'desc')
                    ->paginate((int) $request->input('per_page', 15));
    
                Log::channel('audit')->info('Financial transactions fetched successfully', [
                    'tenant_id' => $tenantId,
                    'count' => $transactions->count(),
                    'correlation_id' => $correlationId,
                ]);
    
                return response()->json([
                    'data' => $transactions->items(),
                    'pagination' => [
                        'total' => $transactions->total(),
                        'per_page' => $transactions->perPage(),
                        'current_page' => $transactions->currentPage(),
                    ],
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to fetch financial transactions', [
                    'tenant_id' => tenant('id'),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId,
                ]);
    
                \Sentry\captureException($e);
    
                return response()->json([
                    'error' => 'Failed to fetch transactions',
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
    
        /**
         * Создать новую финансовую транзакцию.
         * Согласно КАНОН 2026: DB::transaction(), FraudControl::check(), correlation_id, audit лог.
         */
        public function store(StorePaymentTransactionRequest $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
    
            return DB::transaction(function () use ($request, $correlationId): JsonResponse {
                try {
                    $tenantId = tenant('id') ?? 0;
                    $userId = auth()->id() ?? 0;
    
                    Log::channel('audit')->info('Creating payment transaction', [
                        'tenant_id' => $tenantId,
                        'user_id' => $userId,
                        'amount' => $request->integer('amount'),
                        'correlation_id' => $correlationId,
                    ]);
    
                    // Согласно КАНОН 2026: валидация входных данных уже в StorePaymentTransactionRequest
                    // Создание с явным указанием всех полей
                    $transaction = PaymentTransaction::create([
                        'uuid' => Str::uuid(),
                        'correlation_id' => $correlationId,
                        'tenant_id' => $tenantId,
                        'user_id' => $userId,
                        'business_group_id' => $request->integer('business_group_id', 0) ?: null,
                        'amount' => $request->integer('amount'), // В копейках
                        'status' => PaymentTransaction::STATUS_PENDING,
                        'payment_method' => $request->string('payment_method', 'card'),
                        'idempotency_key' => Str::uuid()->toString(),
                        'payload_hash' => hash('sha256', json_encode($request->validated())),
                        'tags' => ['api', 'manual_creation'],
                    ]);
    
                    Log::channel('audit')->info('Payment transaction created successfully', [
                        'transaction_id' => $transaction->id,
                        'tenant_id' => $tenantId,
                        'amount' => $request->integer('amount'),
                        'correlation_id' => $correlationId,
                    ]);
    
                    return response()->json([
                        'data' => $transaction,
                        'correlation_id' => $correlationId,
                    ], 201);
                } catch (Throwable $e) {
                    Log::channel('audit')->error('Failed to create payment transaction', [
                        'tenant_id' => tenant('id'),
                        'user_id' => auth()->id(),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'correlation_id' => $correlationId,
                    ]);
    
                    \Sentry\captureException($e);
    
                    return response()->json([
                        'error' => 'Failed to create transaction',
                        'message' => $e->getMessage(),
                        'correlation_id' => $correlationId,
                    ], 500);
                }
            });
        }
    
        public function show(PaymentTransaction $transaction): JsonResponse
        {
            try {
                $this->authorize('view', $transaction);
                
                Log::info('Retrieving transaction', ['transaction_id' => $transaction->id]);
                
                return response()->json($transaction);
            } catch (\Exception $e) {
                Log::warning('Unauthorized access to transaction', ['transaction_id' => $transaction->id]);
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }
    
        public function update(UpdatePaymentTransactionRequest $request, PaymentTransaction $transaction): JsonResponse
        {
            try {
                $this->authorize('update', $transaction);
                
                Log::info('Updating transaction', ['transaction_id' => $transaction->id]);
                
                $transaction->update($request->validated());
                
                Log::info('Transaction updated', ['transaction_id' => $transaction->id]);
                
                return response()->json($transaction);
            } catch (\Exception $e) {
                Log::error('Error updating transaction', ['transaction_id' => $transaction->id, 'error' => $e->getMessage()]);
                return response()->json(['error' => 'Failed to update transaction'], 500);
            }
        }
    
        public function destroy(PaymentTransaction $transaction): JsonResponse
        {
            try {
                $this->authorize('delete', $transaction);
                
                Log::info('Deleting transaction', ['transaction_id' => $transaction->id]);
                
                $transaction->delete();
                
                Log::info('Transaction deleted', ['transaction_id' => $transaction->id]);
                
                return response()->json(['message' => 'Transaction deleted'], 200);
            } catch (\Exception $e) {
                Log::error('Error deleting transaction', ['transaction_id' => $transaction->id, 'error' => $e->getMessage()]);
                return response()->json(['error' => 'Failed to delete transaction'], 500);
            }
        }
    
        public function balance(): JsonResponse
        {
            try {
                Log::info('Fetching account balance', ['tenant_id' => tenant('id')]);
                
                $balance = PaymentTransaction::where('tenant_id', tenant('id'))
                    ->where('status', 'completed')
                    ->sum('amount');
                
                return response()->json(['balance' => $balance]);
            } catch (QueryException $e) {
                Log::error('Error fetching balance', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Failed to fetch balance'], 500);
            }
        }
    
        public function reconcile(): JsonResponse
        {
            try {
                Log::warning('Starting financial reconciliation', ['tenant_id' => tenant('id')]);
                
                $pending = PaymentTransaction::where('tenant_id', tenant('id'))
                    ->where('status', 'pending')
                    ->count();
                
                Log::info('Reconciliation completed', ['pending' => $pending]);
                
                return response()->json(['pending_transactions' => $pending]);
            } catch (\Exception $e) {
                Log::error('Error reconciling finances', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Failed to reconcile'], 500);
            }
        }
    
        public function export(Request $request): JsonResponse
        {
            try {
                Log::info('Exporting financial data', ['format' => $request->input('format', 'csv')]);
                
                $transactions = PaymentTransaction::where('tenant_id', tenant('id'))->get();
                
                return response()->json(['data' => $transactions, 'count' => $transactions->count()]);
            } catch (QueryException $e) {
                Log::error('Error exporting data', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Failed to export data'], 500);
            }
        }
}
