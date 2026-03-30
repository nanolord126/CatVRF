<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WalletController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly WalletService $walletService,
        ) {}
        /**
         * Get wallet balance for authenticated tenant
         */
        public function index(Request $request): JsonResponse
        {
            $tenantId = (int) tenant('id');
            $correlationId = Str::uuid()->toString();
            try {
                $wallet = Wallet::where('tenant_id', $tenantId)->firstOrFail();
                \Illuminate\Support\Facades\Log::channel('audit')->info('Wallet retrieved', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'balance' => $wallet->current_balance,
                ]);
                return response()->json([
                    'data' => [
                        'id' => $wallet->id,
                        'tenant_id' => $wallet->tenant_id,
                        'current_balance' => $wallet->current_balance,
                        'hold_amount' => $wallet->hold_amount ?? 0,
                        'available_balance' => $wallet->current_balance - ($wallet->hold_amount ?? 0),
                    ],
                    'correlation_id' => $correlationId,
                ], 200);
            } catch (\Throwable $e) {
                return $this->errorResponse($e, $correlationId);
            }
        }
        /**
         * Show wallet details
         */
        public function show(Request $request, Wallet $wallet): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $tenantId = (int) tenant('id');
                if ($wallet->tenant_id !== $tenantId) {
                    return response()->json([
                        'error' => 'Unauthorized',
                        'correlation_id' => $correlationId,
                    ], 403);
                }
                return response()->json([
                    'data' => [
                        'id' => $wallet->id,
                        'tenant_id' => $wallet->tenant_id,
                        'current_balance' => $wallet->current_balance,
                        'hold_amount' => $wallet->hold_amount ?? 0,
                        'available_balance' => $wallet->current_balance - ($wallet->hold_amount ?? 0),
                    ],
                    'correlation_id' => $correlationId,
                ], 200);
            } catch (\Throwable $e) {
                return $this->errorResponse($e, $correlationId);
            }
        }
        /**
         * Deposit funds to wallet (testing only)
         */
        public function deposit(Request $request, Wallet $wallet): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'amount' => 'required|integer|min:100',
                    'reason' => 'nullable|string',
                ]);
                $tenantId = (int) tenant('id');
                if ($wallet->tenant_id !== $tenantId) {
                    return response()->json([
                        'error' => 'Unauthorized',
                        'correlation_id' => $correlationId,
                    ], 403);
                }
                $transaction = $this->walletService->credit(
                    tenantId: $wallet->tenant_id,
                    amount: $validated['amount'],
                    type: 'deposit',
                    correlationId: $correlationId,
                    reason: $validated['reason'] ?? 'API deposit',
                );
                return response()->json([
                    'data' => [
                        'transaction_id' => $transaction->id,
                        'amount' => $transaction->amount,
                        'balance_after' => $transaction->balance_after,
                    ],
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                return $this->errorResponse($e, $correlationId);
            }
        }
        /**
         * Withdraw funds from wallet
         */
        public function withdraw(Request $request, Wallet $wallet): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'amount' => 'required|integer|min:100',
                    'reason' => 'nullable|string',
                ]);
                $tenantId = (int) tenant('id');
                if ($wallet->tenant_id !== $tenantId) {
                    return response()->json([
                        'error' => 'Unauthorized',
                        'correlation_id' => $correlationId,
                    ], 403);
                }
                $transaction = $this->walletService->debit(
                    tenantId: $wallet->tenant_id,
                    amount: $validated['amount'],
                    type: 'withdrawal',
                    correlationId: $correlationId,
                    reason: $validated['reason'] ?? 'API withdrawal',
                );
                return response()->json([
                    'data' => [
                        'transaction_id' => $transaction->id,
                        'amount' => $transaction->amount,
                        'balance_after' => $transaction->balance_after,
                    ],
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                return $this->errorResponse($e, $correlationId);
            }
        }
}
