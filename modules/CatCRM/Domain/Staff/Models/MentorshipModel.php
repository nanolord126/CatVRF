<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * MentorshipModel — Layer 9: Database/Persistence Layer (Eloquent Model)
 */
final class MentorshipModel extends Model
{
    use HasFactory;

    protected $table = 'staff_mentorships';

    protected $fillable = [
        'tenant_id',
        'mentor_id',
        'mentee_id',
        'goals',
        'feedback',
        'status',
        'start_date',
        'end_date',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'metadata' => 'json',
    ];

    public function mentor()
    {
        return $this->belongsTo(EmployeeModel::class, 'mentor_id');
    }

    public function mentee()
    {
        return $this->belongsTo(EmployeeModel::class, 'mentee_id');
    }
}
