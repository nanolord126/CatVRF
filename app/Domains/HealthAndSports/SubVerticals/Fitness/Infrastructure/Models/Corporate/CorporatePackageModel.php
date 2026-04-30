<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models\Corporate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Corporate\Entities\CorporatePackage;

final class CorporatePackageModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_corporate_packages';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'price_per_employee',
        'min_employees',
        'max_employees',
        'duration_months',
        'included_services',
        'features',
        'is_active',
    ];

    protected $casts = [
        'price_per_employee' => 'decimal:2',
        'included_services' => 'array',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    public static function fromDomain(CorporatePackage $package): self
    {
        return new self([
            'id' => $package->id > 0 ? $package->id : null,
            'tenant_id' => $package->tenantId,
            'name' => $package->name,
            'description' => $package->description,
            'price_per_employee' => $package->pricePerEmployee,
            'min_employees' => $package->minEmployees,
            'max_employees' => $package->maxEmployees,
            'duration_months' => $package->durationMonths,
            'included_services' => $package->includedServices,
            'features' => $package->features,
            'is_active' => $package->isActive,
        ]);
    }

    public function updateFromDomain(CorporatePackage $package): void
    {
        $this->name = $package->name;
        $this->description = $package->description;
        $this->price_per_employee = $package->pricePerEmployee;
        $this->min_employees = $package->minEmployees;
        $this->max_employees = $package->maxEmployees;
        $this->duration_months = $package->durationMonths;
        $this->included_services = $package->includedServices;
        $this->features = $package->features;
        $this->is_active = $package->isActive;
    }

    public function toDomain(): CorporatePackage
    {
        return new CorporatePackage(
            id: $this->id,
            tenantId: $this->tenant_id,
            name: $this->name,
            description: $this->description,
            pricePerEmployee: (float) $this->price_per_employee,
            minEmployees: $this->min_employees,
            maxEmployees: $this->max_employees,
            durationMonths: $this->duration_months,
            includedServices: $this->included_services,
            features: $this->features,
            isActive: $this->is_active,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }
}
