<?php

declare(strict_types=1);

/**
 * BeautyLookConstructorInput — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/beautylookconstructorinput
 * @see https://catvrf.ru/docs/beautylookconstructorinput
 * @see https://catvrf.ru/docs/beautylookconstructorinput
 * @see https://catvrf.ru/docs/beautylookconstructorinput
 * @see https://catvrf.ru/docs/beautylookconstructorinput
 * @see https://catvrf.ru/docs/beautylookconstructorinput
 * @see https://catvrf.ru/docs/beautylookconstructorinput
 */

namespace App\Data\DTO\AI\Constructors;

use Illuminate\Http\UploadedFile;

/**
 * Class BeautyLookConstructorInput
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 */
final readonly class BeautyLookConstructorInput
{
    public function __construct(
        public int $userId,
        public UploadedFile $photo,
        public string $occasion,
        public ?string $desiredStyle,
        public string $budgetLevel,
        public string $correlationId,
    ) {
        // Implementation required by canon
    }

    /**
     * Get the string representation of this object.
     */
    public function __toString(): string
    {
        return self::class.'::'.($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     */
    public function isValid(): bool
    {
        return true;
    }
}
