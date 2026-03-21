<?php declare(strict_types=1);

namespace App\Domains\Freelance\Models;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Freelancer extends BaseModel
{
    use SoftDeletes;

    protected $table = 'freelancers';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'business_group_id',
        'full_name',
        'bio',
        'hourly_rate',
        'skills',
        'specializations',
        'languages',
        'experience_years',
        'portfolio_url',
        'website',
        'certifications',
        'rating',
        'review_count',
        'jobs_completed',
        'active_jobs',
        'is_verified',
        'is_active',
        'last_active_at',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'skills' => 'json',
        'specializations' => 'json',
        'languages' => 'json',
        'certifications' => 'json',
        'rating' => 'float',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'last_active_at' => 'datetime',
        'tags' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(FreelanceService::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(FreelanceProposal::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(FreelanceContract::class, 'freelancer_id');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(FreelanceDeliverable::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(FreelanceReview::class, 'freelancer_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if ($tenantId = tenant()?->id) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }
}
