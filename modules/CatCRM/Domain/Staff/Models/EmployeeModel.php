<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * EmployeeModel — Layer 9: Database/Persistence Layer (Eloquent Model)
 * 
 * Eloquent model for staff_employees table
 * Part of 9-layer architecture
 */
final class EmployeeModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff_employees';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'phone',
        'position',
        'department',
        'role',
        'status',
        'manager_id',
        'hire_date',
        'termination_date',
        'avatar',
        'skills',
        'level',
        'experience_points',
        'performance_score',
        'burnout_risk',
        'slack_id',
        'teams_id',
        'metadata',
    ];

    protected $casts = [
        'hire_date' => 'datetime',
        'termination_date' => 'datetime',
        'skills' => 'array',
        'metadata' => 'json',
    ];

    protected $hidden = [
        'metadata',
    ];

    // Relationships
    public function manager()
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim("{$this->last_name} {$this->first_name} {$this->middle_name}");
    }
}
