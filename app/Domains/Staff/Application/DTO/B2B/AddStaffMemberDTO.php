<?php

declare(strict_types=1);

namespace App\Domains\Staff\Application\DTO\B2B;

use Spatie\LaravelData\Data;
use App\Domains\Staff\Domain\Enums\Vertical;
use App\Domains\Staff\Domain\ValueObjects\FullName;
use App\Domains\Staff\Domain\ValueObjects\ContactInfo;
use Ramsey\Uuid\UuidInterface;

/**
 * Class AddStaffMemberDTO
 *
 * Part of the Staff vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final readonly class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Staff\Application\DTO\B2B
 */
final readonly class AddStaffMemberDTO extends Data
{
    public function __construct(
        private readonly FullName $fullName,
        private readonly ContactInfo $contactInfo,
        private readonly Vertical $vertical,
        private readonly UuidInterface $tenantId,
        private readonly ?UuidInterface $businessGroupId,
        private readonly ?UuidInterface $verticalResourceId,
        private readonly string $correlationId
    ) {

    }
}
