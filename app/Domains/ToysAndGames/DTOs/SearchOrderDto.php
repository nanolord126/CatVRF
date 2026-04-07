<?php

declare(strict_types=1);

namespace App\Domains\ToysAndGames\DTOs;

use Illuminate\Http\Request;

/**
 * Class SearchOrderDto
 *
 * Part of the ToysAndGames vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\ToysAndGames\DTOs
 */
final readonly class SearchOrderDto
{
    public function __construct(
        private readonly int     $tenantId,
        private readonly ?int    $businessGroupId,
        private readonly int     $userId,
        private readonly string  $correlationId,
        private ?string $query = null,
        private ?string $status = null,
        private ?string $sortBy = 'created_at',
        private string $sortDir = 'desc',
        public int $perPage = 20,
        private int $page = 1,
        private bool $isB2B = false) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId:        (int) tenant()?->id,
            businessGroupId: $request->input('business_group_id') ? (int) $request->input('business_group_id') : null,
            userId:          (int) $request->user()?->id,
            correlationId:   $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
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
