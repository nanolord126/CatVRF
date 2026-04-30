<?php

declare(strict_types=1);

namespace Modules\Contraindications\Domain\Entities;

use Modules\Contraindications\Domain\Enums\ContraindicationSeverity;
use Modules\Contraindications\Domain\ValueObjects\Scope;

final readonly class Contraindication
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public ?int $userId,
        public ?int $petId,
        public string $name,
        public ?string $description,
        public ContraindicationSeverity $severity,
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
