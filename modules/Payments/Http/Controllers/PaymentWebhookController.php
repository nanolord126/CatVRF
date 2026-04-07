<?php declare(strict_types=1);

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Payments\Services\PaymentOrchestrator;

final class PaymentWebhookController extends Controller
{
    public function __construct(private readonly PaymentOrchestrator $payments) {}

    public function __invoke(Request $request): JsonResponse
    {
        $service = $this->payments->withCorrelationId(Str::uuid()->toString());

        $transaction = $service->handleWebhook($request->all());

        return response()->json([
            'success' => true,
            'transaction_id' => $transaction->id,
            'status' => $transaction->status,
            'correlation_id' => $service->getCorrelationId(),
        ]);
    }
}
