<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * AchievementModel — Layer 9: Database/Persistence Layer (Eloquent Model)
 */
final class AchievementModel extends Model
{
    use HasFactory;

    protected $table = 'staff_achievements';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'badge_id',
        'badge_name',
        'badge_icon',
        'points_awarded',
        'achieved_at',
        'metadata',
    ];

    protected $casts = [
        'achieved_at' => 'datetime',
        'metadata' => 'json',
    ];

    public function employee()
    {
        return $this->belongsTo(EmployeeModel::class, 'employee_id');
    }

    public function badge()
    {
        return $this->belongsTo(BadgeModel::class, 'badge_id');
    }
}
