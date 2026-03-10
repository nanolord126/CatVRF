<?php

namespace App\Http\Controllers\Api;

use App\Models\PaymentTransaction;
use App\Models\Wallet;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PaymentTransaction::class);
        return response()->json(
            PaymentTransaction::where('tenant_id', tenant()->id)->paginate($request->input('per_page', 15))
        );
    }

    public function show(PaymentTransaction $transaction): JsonResponse
    {
        $this->authorize('view', $transaction);
        return response()->json($transaction);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', PaymentTransaction::class);
        
        $transaction = DB::transaction(function () use ($request) {
            return PaymentTransaction::create([
                ...$request->validated(),
                'tenant_id' => tenant()->id,
                'status' => 'pending',
            ]);
        });

        return response()->json($transaction, 201);
    }

    public function refund(PaymentTransaction $transaction): JsonResponse
    {
        $this->authorize('refund', $transaction);
        
        $transaction->update(['status' => 'refunded', 'refunded_at' => now()]);
        return response()->json($transaction);
    }
}
