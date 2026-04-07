<?php

declare(strict_types=1);

namespace Modules\Commissions\Application\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;
use App\Models\Tenant;

/**
 * @OA\Schema(
 *     title="CommissionData DTO",
 *     description="Data Transfer Object for commission calculation",
 *     @OA\Property(property="tenant_id", type="integer", description="ID of the tenant"),
 *     @OA\Property(property="amount", type="integer", description="Transaction amount in cents"),
 *     @OA\Property(property="vertical", type="string", description="Business vertical"),
 *     @OA\Property(property="source_type", type="string", description="Source entity type"),
 *     @OA\Property(property="source_id", type="integer", description="Source entity ID"),
 *     @OA\Property(property="correlation_id", type="string", format="uuid", description="Correlation ID for tracing")
 * )
 */
final class CommissionData extends Data
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $amount,
        public readonly string $vertical,
        public readonly string $source_type,
        public readonly int $source_id,
        public readonly ?string $correlation_id = null,
    ) {
    }

    /**
     * Create a new instance from a request.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            tenant_id: $request->input('tenant_id', Tenant::current()->id),
            amount: (int) $request->input('amount'),
            vertical: $request->input('vertical'),
            source_type: $request->input('source_type'),
            source_id: (int) $request->input('source_id'),
            correlation_id: $request->header('X-Correlation-ID') ?? (string) Str::uuid(),
        );
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenant_id,
            'amount' => $this->amount,
            'vertical' => $this->vertical,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'correlation_id' => $this->correlation_id,
        ];
    }
}
