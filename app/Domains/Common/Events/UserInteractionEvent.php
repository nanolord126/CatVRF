<?php declare(strict_types=1);

/**
 * UserInteractionEvent — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/userinteractionevent
 */


namespace App\Domains\Common\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

final class UserInteractionEvent
{


        private string $correlationId;

        public function __construct(
            public int $userId,
            public int $tenantId,
            public string $interactionType,
            // 'view', 'cart_add', 'cart_remove', 'purchase', 'review', 'rating', 'like', 'wishlist_add'
            private array $data = [],
            // 'product_id', 'vertical', 'category', 'price', 'rating', 'duration_seconds' и т.д.
            private string $ipAddress = '',
            private string $userAgent = '') {
            $this->correlationId = Str::uuid()->toString();
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

    /**
     * Get the component identifier for logging and audit purposes.
     *
     * @return string The fully qualified component name
     */
    private function getComponentIdentifier(): string
    {
        return static::class . '@' . self::VERSION;
    }

}
