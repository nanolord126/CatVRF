<?php declare(strict_types=1);

namespace App\Domains\Freelance\Models;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FreelanceReview extends BaseModel
{
    use SoftDeletes;

    protected $table = 'freelance_reviews';

    protected $fillable = [
        'tenant_id',
        'contract_id',
        'reviewer_id',
        'freelancer_id',
        'client_id',
        'review_type',
        'communication_rating',
        'work_quality_rating',
        'timeliness_rating',
        'overall_rating',
        'comment',
        'review_aspects',
        'verified_contract',
        'would_hire_again',
        'status',
        'helpful_count',
        'unhelpful_count',
        'correlation_id',
    ];

    protected $casts = [
        'review_aspects' => 'json',
        'verified_contract' => 'boolean',
        'would_hire_again' => 'boolean',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(FreelanceContract::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(Freelancer::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
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
