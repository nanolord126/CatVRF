<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ShiftModel — Layer 9: Database/Persistence Layer (Eloquent Model)
 */
final class ShiftModel extends Model
{
    use HasFactory;

    protected $table = 'staff_shifts';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'start_time',
        'end_time',
        'location',
        'status',
        'latitude',
        'longitude',
        'metadata',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'metadata' => 'json',
    ];

    public function employee()
    {
        return $this->belongsTo(EmployeeModel::class, 'employee_id');
    }
}
