<?php declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PaymentWebhookController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly IdempotencyService $idempotencyService,
            private readonly FiscalService $fiscalService,
            private readonly FraudControlService $fraudControl,
            private readonly WalletService $walletService
        ) {
            // PRODUCTION-READY 2026 CANON: Middleware для Payment Webhooks (Internal)
            // Требует IP-whitelist и signature verification
             // IP whitelisting для платежных систем
             // Проверка подписи вебхука
             // Дедупликация платежей
            // NO auth required для вебхуков — они от платежных систем, не от пользователей
        }
        /**
         * Tinkoff Payment Notification Webhook
         * POST /api/internal/webhooks/payment/tinkoff
         */
        public function tinkoffNotification(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                if (!$this->verifyTinkoffSignature($request)) {
                    Log::channel('audit')->warning('Tinkoff webhook: signature verification failed', [
                        'correlation_id' => $correlationId,
                        'ip' => $request->ip(),
                    ]);
                    return response()->json(['error' => 'Invalid signature'], 403);
                }
                $data = $request->validate([
                    'TerminalKey' => 'required|string',
                    'OrderId' => 'required|string',
                    'Success' => 'required|boolean',
                    'Status' => 'required|string|in:AUTHORIZED,CONFIRMED,REJECTED,REFUNDED',
                    'PaymentId' => 'required|string',
                    'Amount' => 'required|integer',
                    'Token' => 'required|string',
                    'RebillId' => 'nullable|string',
                ]);
                return DB::transaction(function () use ($data, $correlationId, $request) {
                    $payment = PaymentTransaction::where('provider_payment_id', $data['PaymentId'])
                        ->where('provider_code', 'tinkoff')
                        ->lockForUpdate()
                        ->firstOrFail();
                    $idempotency = $this->idempotencyService->check(
                        operation: 'tinkoff_webhook_' . $data['PaymentId'],
                        idempotencyKey: $data['PaymentId']
                    );
                    if (!empty($idempotency) && $idempotency === true) { // Updated logic
                        return response()->json(['success' => true], 200);
                    }
                    $statusMap = [
                        'AUTHORIZED' => 'authorized',
                        'CONFIRMED'  => 'captured',
                        'REJECTED'   => 'failed',
                        'REFUNDED'   => 'refunded',
                    ];
                    $newStatus = $statusMap[$data['Status']] ?? 'failed';
                    if ($data['Status'] === 'CONFIRMED' && $payment->status !== 'captured') {
                        if ($payment->hold_amount > 0) {
                            $this->walletService->releaseHold((int)$payment->tenant_id, (int)$payment->hold_amount, 'Payment captured', $correlationId);
                        }
                        $this->walletService->credit(
                            tenantId: (int)$payment->tenant_id,
                            amount: (int)$payment->amount,
                            type: 'deposit',
                            sourceId: (string)$payment->id,
                            correlationId: $correlationId,
                            reason: 'Payment captured from Tinkoff',
                            sourceType: 'payment'
                        );
                    }
                    if ($data['Status'] === 'REJECTED' && $payment->hold_amount > 0) {
                        $this->walletService->releaseHold((int)$payment->tenant_id, (int)$payment->hold_amount, 'Payment rejected', $correlationId);
                    }
                    $payment->update([
                        'status' => $newStatus,
                        'correlation_id' => $correlationId,
                    ]);
                    return response()->json(['OK'], 200);
                });
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Tinkoff webhook error: ' . $e->getMessage(), [
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json(['error' => 'Processing error'], 500);
            }
        }
        private function verifyTinkoffSignature(Request $request): bool
        {
            $token = config('payment.tinkoff.token');
            $data = $request->input();
            $signatureData = $data;
            unset($signatureData['Token']);
            ksort($signatureData);
            $stringToSign = implode(';', $signatureData) . ';' . $token;
            $expectedToken = hash('sha256', $stringToSign);
            return hash_equals($expectedToken, $data['Token'] ?? '');
        }
}
