<?php declare(strict_types=1);

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Modules\Payments\Services\PaymentOrchestrator;

final class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentOrchestrator $payments,
        private readonly LogManager $log,
    ) {}

    public function init(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'payment_method' => ['required', 'string'],
            'idempotency_key' => ['sometimes', 'string'],
            'wallet_id' => ['required', 'integer'],
            'description' => ['sometimes', 'string', 'max:255'],
            'metadata' => ['sometimes', 'array'],
            'recurrent' => ['sometimes', 'boolean'],
        ]);

        $tenant = $request->user()?->tenant ?: Tenant::findOrFail(tenant('id'));
        $service = $this->payments->forTenant($tenant)->withCorrelationId(Str::uuid()->toString());

        $result = $service->initPayment(
            amount: (int) $validated['amount'],
            currency: $validated['currency'] ?? 'RUB',
            paymentMethod: $validated['payment_method'],
            walletId: (int) $validated['wallet_id'],
            idempotencyKey: $validated['idempotency_key'] ?? Str::uuid()->toString(),
            description: $validated['description'] ?? 'Payment',
            metadata: $validated['metadata'] ?? [],
            recurrent: (bool) ($validated['recurrent'] ?? false),
            ip: $request->ip(),
            device: (string) $request->header('X-Device-Fingerprint', ''),
        );

        return response()->json([
            'success' => true,
            'payment_url' => $result['payment_url'],
            'transaction_id' => $result['transaction_id'],
            'provider_payment_id' => $result['provider_payment_id'],
            'correlation_id' => $service->getCorrelationId(),
        ], 201);
    }

    public function status(PaymentTransaction $transaction): JsonResponse
    {
        $tenant = Tenant::findOrFail(tenant('id'));
        $service = $this->payments->forTenant($tenant)->withCorrelationId(Str::uuid()->toString());

        $status = $service->syncStatus($transaction);

        return response()->json([
            'success' => true,
            'status' => $status,
            'correlation_id' => $service->getCorrelationId(),
        ]);
    }

    public function refund(Request $request, PaymentTransaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['sometimes', 'integer', 'min:1'],
            'reason' => ['sometimes', 'string', 'max:255'],
        ]);

        $tenant = Tenant::findOrFail(tenant('id'));
        $service = $this->payments->forTenant($tenant)->withCorrelationId(Str::uuid()->toString());

        $refunded = $service->refundPayment(
            transaction: $transaction,
            amount: $validated['amount'] ?? null,
            reason: $validated['reason'] ?? 'user_request',
        );

        return response()->json([
            'success' => true,
            'transaction' => $refunded,
            'correlation_id' => $service->getCorrelationId(),
        ]);
    }
}
