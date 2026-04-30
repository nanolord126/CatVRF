<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VetGrooming\Domain\Entities\ExoticSafetyProtocol;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticGroup;

final class ExoticSafetyProtocolModel extends Model
{
    protected $table = 'exotic_safety_protocols';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'category',
        'subcategory',
        'group',
        'species',
        'checklist_items',
        'risk_factors',
        'required_equipment',
        'emergency_procedures',
        'is_active',
        'version',
    ];

    protected $casts = [
        'checklist_items' => 'array',
        'risk_factors' => 'array',
        'required_equipment' => 'array',
        'emergency_procedures' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function toDomain(): ExoticSafetyProtocol
    {
        return new ExoticSafetyProtocol(
            id: $this->id,
            tenantId: $this->tenant_id,
            name: $this->name,
            description: $this->description,
            category: ExoticCategory::from($this->category),
            subcategory: $this->subcategory,
            group: ExoticGroup::from($this->group),
            species: $this->species,
            checklistItems: $this->checklist_items,
            riskFactors: $this->risk_factors,
            requiredEquipment: $this->required_equipment,
            emergencyProcedures: $this->emergency_procedures,
            isActive: $this->is_active,
            version: $this->version,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(ExoticSafetyProtocol $protocol): self
    {
        return new self([
            'id' => $protocol->id,
            'tenant_id' => $protocol->tenantId,
            'name' => $protocol->name,
            'description' => $protocol->description,
            'category' => $protocol->category->value,
            'subcategory' => $protocol->subcategory,
            'group' => $protocol->group->value,
            'species' => $protocol->species,
            'checklist_items' => $protocol->checklistItems,
            'risk_factors' => $protocol->riskFactors,
            'required_equipment' => $protocol->requiredEquipment,
            'emergency_procedures' => $protocol->emergencyProcedures,
            'is_active' => $protocol->isActive,
            'version' => $protocol->version,
            'created_at' => $protocol->createdAt,
            'updated_at' => $protocol->updatedAt,
        ]);
    }
}
