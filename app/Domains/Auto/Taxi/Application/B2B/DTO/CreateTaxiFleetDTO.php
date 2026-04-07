<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Application\B2B\DTO;

use App\Shared\Traits\StaticCreate;
use Illuminate\Http\Request;

/**
 * Class CreateTaxiFleetDTO
 *
 * Part of the Auto vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Auto\Taxi\Application\B2B\DTO
 */
final readonly class CreateTaxiFleetDTO
{
    use StaticCreate;

    public function __construct(
        public string $name,
        public int $tenantId,
        private ?string $correlationId = null) {

    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            tenantId: $request->input('tenant_id'),
            correlationId: $request->header('X-Correlation-ID'),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            tenantId: $data['tenant_id'],
        );
    }
}
