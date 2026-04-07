<?php declare(strict_types=1);

namespace App\Domains\DemandForecast\DTOs;

use Illuminate\Http\Request;

/**
 * Class UpdateDemandForecastDto
 *
 * Part of the DemandForecast vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\DemandForecast\DTOs
 */
final readonly class UpdateDemandForecastDto
{
    public function __construct(
        public ?string $name,
        public ?string $description,
        public ?string $status,
        public string  $correlationId) {}

    public static function from(Request $request): self
    {
        return new self(
            name:          $request->string('name')->toString() ?: null,
            description:   $request->string('description')->toString() ?: null,
            status:        $request->string('status')->toString() ?: null,
            correlationId: $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid()),
        );
    }

    /**
     * Handle toArray operation.
     *
     * @throws \DomainException
     */
    public function toArray(): array
    {
        return array_filter([
            'name'           => $this->name,
            'description'    => $this->description,
            'status'         => $this->status,
            'correlation_id' => $this->correlationId,
        ], fn($v) => $v !== null);
    }
}