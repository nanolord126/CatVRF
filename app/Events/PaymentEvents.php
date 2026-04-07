<?php declare(strict_types=1);

namespace App\Events;

final class PaymentInitiatedEvent
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

        private string $correlationId;

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

        private string $correlationId;

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

        private string $correlationId;

        public function __construct(
            public int $userId,
            public int $tenantId,
            public int $paymentId,
            public int $amount,
            public string $transactionId,
            private ?string $receiptUrl = null,
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

        private string $correlationId;

        public function __construct(
            public int $userId,
            public int $tenantId,
            public int $paymentId,
            public string $errorCode,
            public string $errorMessage,
            private bool $canRetry = true,
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

        private string $correlationId;

        public function __construct(
            public int $userId,
            public int $tenantId,
            public int $paymentId,
            public int $refundAmount,
            public string $refundReason,
            private int $daysToAccount = 3,
            ?string $correlationId = null
        ) {
            $this->correlationId = $correlationId ?? \Illuminate\Support\Str::uuid()->toString();
        }
}
