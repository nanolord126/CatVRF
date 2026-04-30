<?php

declare(strict_types=1);

namespace App\Domains\Pet\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Class SearchPetDto
 *
 * Part of the Pet vertical domain.
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
final readonly class SearchPetDto
{
    public function __construct(
        private readonly int $tenantId,
        private readonly ?int $businessGroupId,
        private readonly int $userId,
        private readonly string $correlationId,
        private readonly ?string $query = null,
        private readonly ?string $status = null,
        private readonly ?string $sortBy = 'created_at',
        private readonly string $sortDir = 'desc',
        public int $perPage = 20,
        private readonly int $page = 1,
        private readonly bool $isB2B = false
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId:        (int) tenant()?->id,
            businessGroupId: $request->input('business_group_id') ? (int) $request->input('business_group_id') : null,
            userId:          (int) $request->user()?->id,
            correlationId:   $request->header('X-Correlation-ID', Str::uuid()->toString()),
            query:           $request->input('q'),
            status:          $request->input('status'),
            sortBy:          $request->input('sort_by', 'created_at'),
            sortDir:         $request->input('sort_dir', 'desc'),
            perPage:         (int) $request->input('per_page', 20),
            page:            (int) $request->input('page', 1),
            isB2B:           $request->has('inn') && $request->has('business_card_id'),
        );
    }
}
