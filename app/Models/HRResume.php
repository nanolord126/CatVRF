<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use App\Traits\HasEcosystemTracing;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HRResume extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    use BelongsToTenant;
    use HasEcosystemTracing, SoftDeletes;

    protected $table = 'hr_resumes';

    protected $fillable = [
        'user_id',
        'experience_history',
        'skills',
        'portfolio_links',
        'ai_talent_score',
        'ai_skills_analysis',
        'correlation_id',
    ];

    protected $casts = [
        'experience_history' => 'array',
        'skills' => 'array',
        'portfolio_links' => 'array',
        'ai_skills_analysis' => 'array',
        'ai_talent_score' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}








