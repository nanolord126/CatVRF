<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Payment::Initiated - платёж инициирован
 */
final class PaymentInitiatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $correlationId;

    public function __construct(
        public int $userId,
        public int $tenantId,
        public int $paymentId,
        public int $amount, // в копейках
        public string $paymentMethod,
        public ?string $description = null,
        ?string $correlationId = null
    ) {
        $this->correlationId = $correlationId ?? \Illuminate\Support\Str::uuid()->toString();
    }
}

/**
 * Payment::Authorized - платёж авторизован
 */
final class PaymentAuthorizedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $correlationId;

    public function __construct(
        public int $userId,
        public int $tenantId,
        public int $paymentId,
        public int $amount,
        ?string $correlationId = null
    ) {
        $this->correlationId = $correlationId ?? \Illuminate\Support\Str::uuid()->toString();
    }
}

/**
 * Payment::Captured - платёж успешно выполнен
 */
final class PaymentCapturedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $correlationId;

    public function __construct(
        public int $userId,
        public int $tenantId,
        public int $paymentId,
        public int $amount,
        public string $transactionId,
        public ?string $receiptUrl = null,
        ?string $correlationId = null
    ) {
        $this->correlationId = $correlationId ?? \Illuminate\Support\Str::uuid()->toString();
    }
}

/**
 * Payment::Failed - платёж не выполнен
 */
final class PaymentFailedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $correlationId;

    public function __construct(
        public int $userId,
        public int $tenantId,
        public int $paymentId,
        public string $errorCode,
        public string $errorMessage,
        public bool $canRetry = true,
        ?string $correlationId = null
    ) {
        $this->correlationId = $correlationId ?? \Illuminate\Support\Str::uuid()->toString();
    }
}

/**
 * Payment::Refunded - возврат одобрен
 */
final class PaymentRefundedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $correlationId;

    public function __construct(
        public int $userId,
        public int $tenantId,
        public int $paymentId,
        public int $refundAmount,
        public string $refundReason,
        public int $daysToAccount = 3,
        ?string $correlationId = null
    ) {
        $this->correlationId = $correlationId ?? \Illuminate\Support\Str::uuid()->toString();
    }
}
