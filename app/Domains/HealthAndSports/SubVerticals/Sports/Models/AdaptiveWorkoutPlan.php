<?php

declare(strict_types=1);

namespace App\Domains\Sports\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Database\Factories\AdaptiveWorkoutPlanFactory;

final class AdaptiveWorkoutPlan extends Model
{
    use HasFactory;
    use TenantScoped;

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
        return $this->belongsTo(User::class);
    }

    protected static function newFactory()
    {
        return AdaptiveWorkoutPlanFactory::new();
    }
}
