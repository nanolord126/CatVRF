<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Events\PaymentFailed;
use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Payment\Models\PaymentRecord;
use App\Domains\Payment\Enums\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PaymentIntegrationService
{
    public function handleSuccess(PaymentRecord $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => PaymentStatus::CAPTURED,
                'captured_at' => now(),
            ]);

            event(new PaymentSucceeded($payment));

            Log::channel('audit')->info('Payment succeeded', [
                'payment_id' => $payment->id,
                'tenant_id' => $payment->tenant_id,
                'amount' => $payment->amount_kopecks / 100,
            ]);
        });
    }

    public function handleFailure(PaymentRecord $payment, string $errorMessage): void
    {
        DB::transaction(function () use ($payment, $errorMessage) {
            $payment->update([
                'status' => PaymentStatus::FAILED,
                'failed_at' => now(),
            ]);

            event(new PaymentFailed($payment, $errorMessage));

            Log::channel('audit')->info('Payment failed', [
                'payment_id' => $payment->id,
                'tenant_id' => $payment->tenant_id,
                'error_message' => $errorMessage,
            ]);
        });
    }

    public function linkPaymentToPayable(PaymentRecord $payment, string $payableType, int $payableId): void
    {
        $payment->update([
            'payable_type' => $payableType,
            'payable_id' => $payableId,
        ]);

        Log::channel('audit')->info('Payment linked to payable', [
            'payment_id' => $payment->id,
            'payable_type' => $payableType,
            'payable_id' => $payableId,
            'tenant_id' => $payment->tenant_id,
        ]);
    }
}
