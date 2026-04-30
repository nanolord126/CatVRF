<?php

declare(strict_types=1);

namespace App\Events;

use Carbon\CarbonImmutable;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Queue\SerializesModels;
use App\Models\PaymentTransaction;

/**
 * Event: Payment processed.
 * Broadcast: private-tenant.{tenantId}
 */
final class PaymentProcessed implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithBroadcasting;
    use SerializesModels;

    private readonly PaymentTransaction $payment;

    private readonly string $status;

    private readonly string $correlationId;

    private readonly int $tenantId;

    public function __construct(
        PaymentTransaction $payment,
        string $status,
        string $correlationId
    ) {
        $this->payment = $payment;
        $this->status = $status;
        $this->correlationId = $correlationId;
        $this->tenantId = $payment->tenant_id;
    }

    /**
     * Канал для broadcast
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel("tenant.{$this->tenantId}");
    }

    /**
     * Имя события в фронтенде
     */
    public function broadcastAs(): string
    {
        return 'payment.processed';
    }

    /**
     * Данные для broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->payment->id,
            'uuid' => $this->payment->uuid,
            'status' => $this->status,
            'amount' => $this->payment->amount,
            'provider' => $this->payment->provider_code,
            'correlation_id' => $this->correlationId,
            'processed_at' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
