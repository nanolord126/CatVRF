<?php

namespace App\Modules\Finances\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finances\Models\PaymentTransaction;
use App\Modules\Finances\Http\Requests\StorePaymentTransactionRequest;
use App\Modules\Finances\Http\Requests\UpdatePaymentTransactionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class FinanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            Log::info('Fetching financial transactions', ['tenant_id' => tenant('id')]);
            
            $transactions = PaymentTransaction::where('tenant_id', tenant('id'))
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));
            
            Log::info('Financial transactions fetched', ['count' => $transactions->count()]);
            
            return response()->json($transactions);
        } catch (QueryException $e) {
            Log::error('Error fetching transactions', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch transactions'], 500);
        }
    }

    public function store(StorePaymentTransactionRequest $request): JsonResponse
    {
        try {
            Log::info('Creating payment transaction', ['amount' => $request->amount]);
            
            $transaction = PaymentTransaction::create([
                'tenant_id' => tenant('id'),
                'user_id' => auth()->id(),
                'amount' => $request->amount,
                'currency' => $request->currency ?? 'RUB',
                'status' => 'pending',
                'correlation_id' => \Illuminate\Support\Str::uuid(),
            ]);
            
            Log::info('Payment transaction created', ['transaction_id' => $transaction->id]);
            
            return response()->json($transaction, 201);
        } catch (QueryException $e) {
            Log::error('Error creating transaction', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create transaction'], 500);
        }
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
