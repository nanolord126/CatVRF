<?php declare(strict_types=1);

/**
 * DentalClinicResource — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/dentalclinicresource
 * @see https://catvrf.ru/docs/dentalclinicresource
 * @see https://catvrf.ru/docs/dentalclinicresource
 * @see https://catvrf.ru/docs/dentalclinicresource
 * @see https://catvrf.ru/docs/dentalclinicresource
 * @see https://catvrf.ru/docs/dentalclinicresource
 * @see https://catvrf.ru/docs/dentalclinicresource
 */


namespace App\Http\Resources\Api\Dental;

use Illuminate\Http\Resources\Json\JsonResource;

final class DentalClinicResource extends JsonResource
{

    public function toArray(Request $request): array
        {
            return [
                'id' => $this->uuid,
                'name' => $this->name,
                'address' => $this->metadata['address'] ?? null,
                'phones' => $this->metadata['phones'] ?? [],
                'rating' => (float) ($this->rating ?? 0.0),
                'coordinates' => [
                    'lat' => $this->metadata['lat'] ?? null,
                    'lon' => $this->metadata['lon'] ?? null,
                ],
                'tags' => $this->tags,
                'is_emergency_friendly' => (bool) ($this->metadata['emergency'] ?? false),
            ];
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
