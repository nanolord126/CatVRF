<?php

declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AdaptiveWorkoutPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'user_id',
        'plan_data',
        'embedding',
        'correlation_id',
        'is_active',
    ];

    protected $casts = [
        'plan_data' => 'array',
        'embedding' => 'array',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\AdaptiveWorkoutPlanFactory::new();
    }
}
