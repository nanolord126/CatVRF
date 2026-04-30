<?php

declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\Construction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ConstructionProject extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'const_projects';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'client_id',
        'title',
        'description',
        'status',          // draft, planning, active, halted, completed
        'estimated_cost',
        'actual_cost',
        'deadline_at',
        'address',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'estimated_cost' => 'integer',
        'actual_cost' => 'integer',
        'deadline_at' => 'datetime',
        'tags' => 'json',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ConstructionMaterial::class, 'project_id');
    }
}
