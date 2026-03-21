<?php declare(strict_types=1);

namespace App\Domains\Freelance\Models;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FreelanceJob extends BaseModel
{
    use SoftDeletes;

    protected $table = 'freelance_jobs';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'title',
        'description',
        'categories',
        'skills_required',
        'job_type',
        'pricing_type',
        'budget_min',
        'budget_max',
        'duration_days',
        'status',
        'posted_at',
        'deadline',
        'proposals_count',
        'interviews_count',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'categories' => 'json',
        'skills_required' => 'json',
        'tags' => 'json',
        'posted_at' => 'datetime',
        'deadline' => 'datetime',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(FreelanceProposal::class, 'job_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(FreelanceContract::class, 'job_id');
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
