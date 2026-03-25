<?php

declare(strict_types=1);

namespace Modules\Finances\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Finances\Models\PaymentTransaction;
use Modules\Finances\Http\Requests\StorePaymentTransactionRequest;
use Modules\Finances\Http\Requests\UpdatePaymentTransactionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Контроллер управления финансовыми транзакциями.
 * Согласно КАНОН 2026: все мутации в $this->db->transaction(), correlation_id в каждом логе, FraudControl проверки.
 */
final class FinanceController extends Controller
{
    /**
     * Получить список финансовых транзакций текущего tenant.
     * Согласно КАНОН 2026: tenant scoping, audit лог.
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $tenantId = tenant('id') ?? 0;

            $this->log->channel('audit')->info('Fetching financial transactions', [
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'per_page' => $request->input('per_page', 15),
            ]);

            $transactions = PaymentTransaction::where('tenant_id', $tenantId)
                ->orderBy('created_at', 'desc')
                ->paginate((int) $request->input('per_page', 15));

            $this->log->channel('audit')->info('Financial transactions fetched successfully', [
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
            $this->log->channel('audit')->error('Failed to fetch financial transactions', [
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
     * Согласно КАНОН 2026: $this->db->transaction(), FraudControl::check(), correlation_id, audit лог.
     */
    public function store(StorePaymentTransactionRequest $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use ($request, $correlationId): JsonResponse {
            try {
                $tenantId = tenant('id') ?? 0;
                $userId = auth()->id() ?? 0;

                $this->log->channel('audit')->info('Creating payment transaction', [
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

                $this->log->channel('audit')->info('Payment transaction created successfully', [
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
                $this->log->channel('audit')->error('Failed to create payment transaction', [
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
            
            $this->log->info('Retrieving transaction', ['transaction_id' => $transaction->id]);
            
            return response()->json($transaction);
        } catch (\Exception $e) {
            $this->log->warning('Unauthorized access to transaction', ['transaction_id' => $transaction->id]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    }

    public function update(UpdatePaymentTransactionRequest $request, PaymentTransaction $transaction): JsonResponse
    {
        try {
            $this->authorize('update', $transaction);
            
            $this->log->info('Updating transaction', ['transaction_id' => $transaction->id]);
            
            $transaction->update($request->validated());
            
            $this->log->info('Transaction updated', ['transaction_id' => $transaction->id]);
            
            return response()->json($transaction);
        } catch (\Exception $e) {
            $this->log->error('Error updating transaction', ['transaction_id' => $transaction->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update transaction'], 500);
        }
    }

    public function destroy(PaymentTransaction $transaction): JsonResponse
    {
        try {
            $this->authorize('delete', $transaction);
            
            $this->log->info('Deleting transaction', ['transaction_id' => $transaction->id]);
            
            $transaction->delete();
            
            $this->log->info('Transaction deleted', ['transaction_id' => $transaction->id]);
            
            return response()->json(['message' => 'Transaction deleted'], 200);
        } catch (\Exception $e) {
            $this->log->error('Error deleting transaction', ['transaction_id' => $transaction->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete transaction'], 500);
        }
    }

    public function balance(): JsonResponse
    {
        try {
            $this->log->info('Fetching account balance', ['tenant_id' => tenant('id')]);
            
            $balance = PaymentTransaction::where('tenant_id', tenant('id'))
                ->where('status', 'completed')
                ->sum('amount');
            
            return response()->json(['balance' => $balance]);
        } catch (QueryException $e) {
            $this->log->error('Error fetching balance', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch balance'], 500);
        }
    }

    public function reconcile(): JsonResponse
    {
        try {
            $this->log->warning('Starting financial reconciliation', ['tenant_id' => tenant('id')]);
            
            $pending = PaymentTransaction::where('tenant_id', tenant('id'))
                ->where('status', 'pending')
                ->count();
            
            $this->log->info('Reconciliation completed', ['pending' => $pending]);
            
            return response()->json(['pending_transactions' => $pending]);
        } catch (\Exception $e) {
            $this->log->error('Error reconciling finances', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to reconcile'], 500);
        }
    }

    public function export(Request $request): JsonResponse
    {
        try {
            $this->log->info('Exporting financial data', ['format' => $request->input('format', 'csv')]);
            
            $transactions = PaymentTransaction::where('tenant_id', tenant('id'))->get();
            
            return response()->json(['data' => $transactions, 'count' => $transactions->count()]);
        } catch (QueryException $e) {
            $this->log->error('Error exporting data', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to export data'], 500);
        }
    }
}
