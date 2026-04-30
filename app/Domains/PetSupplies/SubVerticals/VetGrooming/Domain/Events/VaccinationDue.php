<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\VetGrooming\Domain\Entities\PetVaccination;

/**
 * VaccinationDue Event
 * 
 * Fired when a vaccination is due or overdue.
 * Triggers notification jobs to remind pet owners.
 */
final class VaccinationDue
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PetVaccination $vaccination,
        public readonly bool $isOverdue = false,
    ) {}
}
