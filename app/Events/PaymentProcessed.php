<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\PaymentTransaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие обработки платежа (Real-Time)
 * Триггер: PaymentService::process()
 * Broadcast: private-tenant.{tenantId}
 * 
 * @package App\Events
 */
final class PaymentProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public readonly PaymentTransaction $payment;
    public readonly string $status;
    public readonly string $correlationId;
    public readonly int $tenantId;

    /**
     * @param PaymentTransaction $payment
     * @param string $status
     * @param string $correlationId
     */
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
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel("tenant.{$this->tenantId}");
    }

    /**
     * Имя события в фронтенде
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'payment.processed';
    }

    /**
     * Данные для broadcast
     * @return array
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
            'processed_at' => now()->toIso8601String(),
        ];
    }
}
