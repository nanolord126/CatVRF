<?php

namespace App\Modules\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Wallet\Models\WalletTransaction;
use App\Modules\Wallet\Http\Requests\StoreWalletTransactionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class WalletController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            Log::info('Fetching wallet transactions', ['tenant_id' => tenant('id')]);
            
            $transactions = WalletTransaction::where('tenant_id', tenant('id'))
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));
            
            Log::info('Wallet transactions fetched', ['count' => $transactions->count()]);
            
            return response()->json($transactions);
        } catch (QueryException $e) {
            Log::error('Error fetching wallet transactions', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch wallet transactions'], 500);
        }
    }

    public function balance(): JsonResponse
    {
        try {
            Log::info('Fetching wallet balance', ['user_id' => auth()->id()]);
            
            $balance = WalletTransaction::where('tenant_id', tenant('id'))
                ->where('user_id', auth()->id())
                ->where('status', 'completed')
                ->sum('amount');
            
            return response()->json(['balance' => $balance ?? 0]);
        } catch (QueryException $e) {
            Log::error('Error fetching balance', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch balance'], 500);
        }
    }

    public function deposit(StoreWalletTransactionRequest $request): JsonResponse
    {
        try {
            Log::info('Processing wallet deposit', ['amount' => $request->amount]);
            
            $transaction = WalletTransaction::create([
                'tenant_id' => tenant('id'),
                'user_id' => auth()->id(),
                'type' => 'deposit',
                'amount' => $request->amount,
                'status' => 'pending',
                'currency' => $request->currency ?? 'RUB',
                'correlation_id' => \Illuminate\Support\Str::uuid(),
                'metadata' => $request->metadata ?? [],
            ]);
            
            Log::info('Wallet deposit created', ['transaction_id' => $transaction->id]);
            
            return response()->json($transaction, 201);
        } catch (QueryException $e) {
            Log::error('Error processing deposit', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to process deposit'], 500);
        }
    }

    public function withdraw(StoreWalletTransactionRequest $request): JsonResponse
    {
        try {
            Log::info('Processing wallet withdrawal', ['amount' => $request->amount]);
            
            $currentBalance = WalletTransaction::where('tenant_id', tenant('id'))
                ->where('user_id', auth()->id())
                ->where('status', 'completed')
                ->sum('amount');
            
            if ($currentBalance < $request->amount) {
                Log::warning('Insufficient balance', ['required' => $request->amount, 'available' => $currentBalance]);
                return response()->json(['error' => 'Insufficient balance'], 422);
            }
            
            $transaction = WalletTransaction::create([
                'tenant_id' => tenant('id'),
                'user_id' => auth()->id(),
                'type' => 'withdrawal',
                'amount' => -$request->amount,
                'status' => 'pending',
                'currency' => $request->currency ?? 'RUB',
                'correlation_id' => \Illuminate\Support\Str::uuid(),
                'metadata' => $request->metadata ?? [],
            ]);
            
            Log::info('Wallet withdrawal created', ['transaction_id' => $transaction->id]);
            
            return response()->json($transaction, 201);
        } catch (QueryException $e) {
            Log::error('Error processing withdrawal', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to process withdrawal'], 500);
        }
    }

    public function show(WalletTransaction $transaction): JsonResponse
    {
        try {
            $this->authorize('view', $transaction);
            
            Log::info('Retrieving wallet transaction', ['transaction_id' => $transaction->id]);
            
            return response()->json($transaction);
        } catch (\Exception $e) {
            Log::warning('Unauthorized access to wallet transaction', ['transaction_id' => $transaction->id]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    }

    public function history(Request $request): JsonResponse
    {
        try {
            Log::info('Fetching wallet history', ['user_id' => auth()->id()]);
            
            $history = WalletTransaction::where('tenant_id', tenant('id'))
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 20));
            
            return response()->json($history);
        } catch (QueryException $e) {
            Log::error('Error fetching wallet history', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch history'], 500);
        }
    }

    public function statement(Request $request): JsonResponse
    {
        try {
            Log::info('Generating wallet statement', ['user_id' => auth()->id()]);
            
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
            
            Log::info('Wallet statement generated', ['count' => $statement->count()]);
            
            return response()->json(['statement' => $statement]);
        } catch (QueryException $e) {
            Log::error('Error generating statement', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to generate statement'], 500);
        }
    }
}
