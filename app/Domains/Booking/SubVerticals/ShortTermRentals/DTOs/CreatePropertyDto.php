<?php

declare(strict_types=1);

namespace App\Domains\ShortTermRentals\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Class CreatePropertyDto
 *
 * Part of the ShortTermRentals vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 */
final readonly class CreatePropertyDto
{
    public function __construct(
        private readonly int $tenantId,
        private readonly ?int $businessGroupId,
        private readonly int $userId,
        private readonly string $correlationId,
        private readonly array $data,
        private readonly ?string $idempotencyKey = null,
        private readonly bool $isB2B = false
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId:        (int) tenant()?->id,
            businessGroupId: $request->input('business_group_id') ? (int) $request->input('business_group_id') : null,
            userId:          (int) $request->user()?->id,
            correlationId:   $request->header('X-Correlation-ID', Str::uuid()->toString()),
            data:            $request->validated(),
            idempotencyKey:  $request->header('Idempotency-Key'),
            isB2B:           $request->has('inn') && $request->has('business_card_id'),
        );
    }

    public function toArray(): array
    {
        return array_merge($this->data, [
            'tenant_id'         => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id'           => $this->userId,
            'correlation_id'    => $this->correlationId,
        ]);
    }
}
