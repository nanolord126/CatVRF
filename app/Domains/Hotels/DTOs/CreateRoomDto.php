<?php

declare(strict_types=1);

namespace App\Domains\Hotels\DTOs;

use Illuminate\Http\Request;

/**
 * Class CreateRoomDto
 *
 * Part of the Hotels vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\Hotels\DTOs
 */
final readonly class CreateRoomDto
{
    public function __construct(
        private int     $tenantId,
        private ?int    $businessGroupId,
        private int     $userId,
        private string  $correlationId,
        private array   $data,
        private ?string $idempotencyKey = null,
        private bool    $isB2B = false,
    ) {}

    public static function from(Request $request, int $tenantId): self
    {
        return new self(
            tenantId:        $tenantId,
            businessGroupId: $request->input('business_group_id') ? (int) $request->input('business_group_id') : null,
            userId:          (int) $request->user()?->id,
            correlationId:   $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
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
