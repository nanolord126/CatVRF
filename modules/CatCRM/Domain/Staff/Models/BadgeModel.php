<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * BadgeModel — Layer 9: Database/Persistence Layer (Eloquent Model)
 */
final class BadgeModel extends Model
{
    use HasFactory;

    protected $table = 'staff_badges';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'icon',
        'category',
        'points_required',
        'condition',
        'is_rare',
        'is_legendary',
        'metadata',
    ];

    protected $casts = [
        'is_rare' => 'boolean',
        'is_legendary' => 'boolean',
        'metadata' => 'json',
    ];
}
