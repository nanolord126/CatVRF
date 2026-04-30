<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * SkillModel — Layer 9: Database/Persistence Layer (Eloquent Model)
 */
final class SkillModel extends Model
{
    use HasFactory;

    protected $table = 'staff_skills';

    protected $fillable = [
        'tenant_id',
        'name',
        'category',
        'description',
        'level',
        'proficiency',
        'last_assessed',
        'metadata',
    ];

    protected $casts = [
        'last_assessed' => 'datetime',
        'metadata' => 'json',
    ];
}
