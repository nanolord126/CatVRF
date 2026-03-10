<?php

namespace App\Http\Controllers\Api;

use App\Models\Wallet;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Wallet::class);
        return response()->json(
            Wallet::where('tenant_id', tenant()->id)->paginate($request->input('per_page', 15))
        );
    }

    public function show(Wallet $wallet): JsonResponse
    {
        $this->authorize('view', $wallet);
        return response()->json($wallet);
    }

    public function deposit(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('deposit', $wallet);
        
        $amount = $request->input('amount');
        $wallet->deposit($amount);
        
        return response()->json(['balance' => $wallet->balance, 'message' => 'Deposit successful']);
    }

    public function withdraw(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('withdraw', $wallet);
        
        $amount = $request->input('amount');
        if ($wallet->balance < $amount) {
            return response()->json(['error' => 'Insufficient balance'], 400);
        }
        
        $wallet->withdraw($amount);
        return response()->json(['balance' => $wallet->balance, 'message' => 'Withdrawal successful']);
    }
}
