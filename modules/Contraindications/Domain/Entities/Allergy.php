<?php

declare(strict_types=1);

namespace Modules\Contraindications\Domain\Entities;

use Modules\Contraindications\Domain\Enums\AllergySeverity;
use Modules\Contraindications\Domain\ValueObjects\Scope;

final readonly class Allergy
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public ?int $userId,
        public ?int $petId,
        public string $name,
        public AllergySeverity $severity,
        public ?string $reaction,
        /** @var array<string> */
        public array $scopes,
        public bool $isActive,
    ) {
    }

    public function isRelevantForScope(Scope $scope): bool
    {
        if (empty($this->scopes)) {
            return true; // Empty scopes means applicable to all
        }

        return in_array($scope->value, $this->scopes, true);
    }
}
