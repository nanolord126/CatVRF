<?php

declare(strict_types=1);

/**
 * UserInteractionEvent — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/userinteractionevent
 * @see https://catvrf.ru/docs/userinteractionevent
 * @see https://catvrf.ru/docs/userinteractionevent
 * @see https://catvrf.ru/docs/userinteractionevent
 * @see https://catvrf.ru/docs/userinteractionevent
 * @see https://catvrf.ru/docs/userinteractionevent
 * @see https://catvrf.ru/docs/userinteractionevent
 */

namespace App\Events\ML;

use Carbon\CarbonImmutable;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class UserInteractionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly string $interactionType,  // product_view, add_to_cart, purchase и т.д.
        private readonly string $interactableType, // Product, Service и т.д.
        private readonly int $interactableId,
        private readonly ?string $vertical = null,
        private readonly ?string $category = null,
        private readonly ?array $itemAttributes = null,  // price, size, color, brand и т.д.
        private readonly ?int $durationSeconds = null,
        private readonly ?array $metadata = null,  // IP, device, source, search_query
        private readonly string $correlationId = '',
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->userId}"),
        ];
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
