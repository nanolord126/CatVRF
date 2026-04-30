<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Contraindications\Domain\Entities\Allergy;
use Modules\Contraindications\Domain\Entities\Contraindication;
use Modules\Contraindications\Domain\Enums\AllergySeverity;
use Modules\Contraindications\Domain\Enums\ContraindicationSeverity;
use Modules\Contraindications\Domain\ValueObjects\Scope;
use Modules\Contraindications\Infrastructure\Models\AllergyModel;
use Modules\Contraindications\Infrastructure\Models\ContraindicationModel;

trait HasContraindications
{
    public function allergies(): MorphMany
    {
        return $this->morphMany(AllergyModel::class, 'subject');
    }

    public function contraindications(): MorphMany
    {
        return $this->morphMany(ContraindicationModel::class, 'subject');
    }

    /**
     * Add an allergy to this subject (user or pet)
     */
    public function addAllergy(
        string $name,
        AllergySeverity $severity,
        ?string $reaction = null,
        array $scopes = []
    ): AllergyModel {
        return $this->allergies()->create([
            'tenant_id' => tenant()->id,
            'name' => $name,
            'severity' => $severity->value,
            'reaction' => $reaction,
            'scopes' => $scopes,
            'is_active' => true,
        ]);
    }

    /**
     * Add a contraindication to this subject (user or pet)
     */
    public function addContraindication(
        string $name,
        ContraindicationSeverity $severity,
        ?string $description = null,
        array $scopes = []
    ): ContraindicationModel {
        return $this->contraindications()->create([
            'tenant_id' => tenant()->id,
            'name' => $name,
            'description' => $description,
            'severity' => $severity->value,
            'scopes' => $scopes,
            'is_active' => true,
        ]);
    }

    /**
     * Get active allergies filtered by scope
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveAllergiesForScope(Scope $scope)
    {
        return $this->allergies()
            ->where('is_active', true)
            ->where(function ($query) use ($scope) {
                $query->whereJsonContains('scopes', $scope->value)
                    ->orWhere('scopes', '[]');
            })
            ->get();
    }

    /**
     * Get active contraindications filtered by scope
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveContraindicationsForScope(Scope $scope)
    {
        return $this->contraindications()
            ->where('is_active', true)
            ->where(function ($query) use ($scope) {
                $query->whereJsonContains('scopes', $scope->value)
                    ->orWhere('scopes', '[]');
            })
            ->get();
    }
}
