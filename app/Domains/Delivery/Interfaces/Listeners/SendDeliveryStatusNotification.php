<?php

declare(strict_types=1);

/**
 * SendDeliveryStatusNotification — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/senddeliverystatusnotification
 */


namespace App\Domains\Delivery\Interfaces\Listeners;

use App\Domains\Delivery\Domain\Events\DeliveryStatusChanged;
use App\Domains\Delivery\Interfaces\Notifications\DeliveryStatusNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\ChannelManager;

/**
 * Class SendDeliveryStatusNotification
 *
 * Part of the Delivery vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Delivery\Interfaces\Listeners
 */
final class SendDeliveryStatusNotification implements ShouldQueue
{
    public function __construct(
        private readonly ChannelManager $notifier,
    ) {}

    /**
     * Handle delivery status change event.
     *
     * Sends notification to tenant owner about delivery status update.
     * Maintains correlation_id chain through the event.
     */
    public function handle(DeliveryStatusChanged $event): void
    {
        $delivery = $event->delivery;
        $user = $delivery->tenant->owner;

        $this->notifier->send($user, new DeliveryStatusNotification($delivery));
    }
}
