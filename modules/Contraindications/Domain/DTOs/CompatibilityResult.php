<?php

declare(strict_types=1);

namespace Modules\Contraindications\Domain\DTOs;

use Modules\Contraindications\Domain\Entities\Allergy;
use Modules\Contraindications\Domain\Entities\Contraindication;

final readonly class CompatibilityResult
{
    /**
     * @param array<Allergy> $allergyConflicts
     * @param array<Contraindication> $contraindicationConflicts
     */
    public function __construct(
        public bool $isCompatible,
        public array $allergyConflicts = [],
        public array $contraindicationConflicts = [],
    ) {
    }

    public function hasConflicts(): bool
    {
        return !empty($this->allergyConflicts) || !empty($this->contraindicationConflicts);
    }

    public function getSeverity(): string
    {
        if (empty($this->allergyConflicts) && empty($this->contraindicationConflicts)) {
            return 'none';
        }

        $maxSeverity = 'low';

        foreach ($this->allergyConflicts as $allergy) {
            $severity = $allergy->severity->value;
            if ($severity === 'life_threatening') {
                return 'critical';
            }
            if ($severity === 'severe' && $maxSeverity !== 'critical') {
                $maxSeverity = 'high';
            }
        }

        foreach ($this->contraindicationConflicts as $contraindication) {
            $severity = $contraindication->severity->value;
            if ($severity === 'critical') {
                return 'critical';
            }
            if ($severity === 'high' && $maxSeverity !== 'critical') {
                $maxSeverity = 'high';
            }
        }

        return $maxSeverity;
    }
}
