<?php

declare(strict_types=1);

namespace App\Domains\Consulting\HR\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BusinessGroup;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class JobVacancy extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'hr_vacancies';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'title',
        'description',
        'requirements', // jsonb: skills, experience
        'salary_min',
        'salary_max',
        'currency',
        'status',      // open, closed, draft, filled
        'location',
        'remote_allowed',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'requirements' => 'json',
        'salary_min' => 'integer',
        'salary_max' => 'integer',
        'remote_allowed' => 'boolean',
        'tags' => 'json',
    ];

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class, 'business_group_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'vacancy_id');
    }
}
