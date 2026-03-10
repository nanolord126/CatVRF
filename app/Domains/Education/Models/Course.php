<?php

namespace App\Domains\Education\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Traits\Common\HasEcosystemAuth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $table = 'courses';
    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'tenant_id',
        'instructor_id',
        'title',
        'description',
        'status',
        'duration_hours',
        'price',
        'max_students',
        'start_date',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'instructor_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }
}
