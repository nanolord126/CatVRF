<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Domains\Shared\Contraindications\Entities\MaterialAllergy;
use App\Domains\Shared\Contraindications\ValueObjects\AllergenType;
use App\Domains\Shared\Contraindications\ValueObjects\SeverityLevel;

final class UserMaterialAllergy extends Model
{
    use HasFactory;

    protected $table = 'user_material_allergies';

    protected $fillable = [
        'uuid',
        'material_name',
        'allergen_type',
        'severity',
        'triggering_materials',
        'description',
        'medical_reference',
        'is_active',
    ];

    protected $casts = [
        'triggering_materials' => 'array',
        'is_active' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_allergy_pivot');
    }

    public function toDomain(): MaterialAllergy
    {
        return MaterialAllergy::fromArray([
            'uuid' => $this->uuid,
            'material_name' => $this->material_name,
            'allergen_type' => $this->allergen_type,
            'severity' => $this->severity,
            'triggering_materials' => $this->triggering_materials,
            'description' => $this->description,
            'medical_reference' => $this->medical_reference,
            'is_active' => $this->is_active,
        ]);
    }

    public static function fromDomain(MaterialAllergy $allergy): self
    {
        return new self([
            'uuid' => $allergy->getUuid(),
            'material_name' => $allergy->getMaterialName(),
            'allergen_type' => $allergy->getAllergenType()->value,
            'severity' => $allergy->getSeverity()->value,
            'triggering_materials' => $allergy->getTriggeringMaterials(),
            'description' => $allergy->getDescription(),
            'medical_reference' => $allergy->getMedicalReference(),
            'is_active' => $allergy->isActive(),
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, AllergenType $type)
    {
        return $query->where('allergen_type', $type->value);
    }

    public function scopeBySeverity($query, SeverityLevel $severity)
    {
        return $query->where('severity', $severity->value);
    }
}
