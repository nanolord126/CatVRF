<?php declare(strict_types=1);

namespace App\Domains\Recommendation\DTOs;

use Illuminate\Http\Request;

/**
 * Class UpdateRecommendationDto
 *
 * Part of the Recommendation vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\Recommendation\DTOs
 */
final readonly class UpdateRecommendationDto
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