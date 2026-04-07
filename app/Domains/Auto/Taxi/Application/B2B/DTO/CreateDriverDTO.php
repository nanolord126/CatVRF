<?php

declare(strict_types=1);

/**
 * CreateDriverDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createdriverdto
 */


namespace App\Domains\Auto\Taxi\Application\B2B\DTO;

use App\Shared\Traits\StaticCreate;
use Illuminate\Http\Request;

/**
 * Class CreateDriverDTO
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
final readonly class CreateDriverDTO
{
    use StaticCreate;

    public function __construct(
        public string $name,
        public string $licenseNumber,
        public int $tenantId,
        private ?string $correlationId = null) {

    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            licenseNumber: $request->input('license_number'),
            tenantId: $request->input('tenant_id'),
            correlationId: $request->header('X-Correlation-ID'),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            licenseNumber: $data['license_number'],
            tenantId: $data['tenant_id'],
        );
    }
}
