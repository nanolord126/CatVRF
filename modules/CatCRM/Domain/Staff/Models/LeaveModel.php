<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * LeaveModel — Layer 9: Database/Persistence Layer (Eloquent Model)
 */
final class LeaveModel extends Model
{
    use HasFactory;

    protected $table = 'staff_leaves';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'type',
        'status',
        'start_date',
        'end_date',
        'reason',
        'approved_by',
        'approved_at',
        'reject_reason',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'approved_at' => 'datetime',
        'metadata' => 'json',
    ];

    public function employee()
    {
        return $this->belongsTo(EmployeeModel::class, 'employee_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(EmployeeModel::class, 'approved_by');
    }
}
