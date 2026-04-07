<?php declare(strict_types=1);

namespace App\Domains\DemandForecast\DTOs;

use Illuminate\Http\Request;

/**
 * Class CreateDemandForecastDto
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
final readonly class CreateDemandForecastDto
{
    public function __construct(
        public int     $tenantId,
        public ?int    $businessGroupId,
        public string  $name,
        public ?string $description,
        public string  $status,
        public string  $correlationId,
        private ?string $idempotencyKey = null) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId:        (int) tenant()->id,
            businessGroupId: $request->integer('business_group_id') ?: null,
            name:            $request->string('name')->toString(),
            description:     $request->string('description')->toString() ?: null,
            status:          $request->string('status', 'active')->toString(),
            correlationId:   $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid()),
            idempotencyKey:  $request->header('X-Idempotency-Key'),
        );
    }

    /**
     * Handle toArray operation.
     *
     * @throws \DomainException
     */
    public function toArray(): array
    {
        return [
            'tenant_id'         => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'name'              => $this->name,
            'description'       => $this->description,
            'status'            => $this->status,
            'correlation_id'    => $this->correlationId,
        ];
    }
}