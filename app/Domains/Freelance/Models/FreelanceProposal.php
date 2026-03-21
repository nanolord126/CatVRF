<?php declare(strict_types=1);

namespace App\Domains\Freelance\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FreelanceProposal extends BaseModel
{
    use SoftDeletes;

    protected $table = 'freelance_proposals';

    protected $fillable = [
        'tenant_id',
        'job_id',
        'freelancer_id',
        'proposed_amount',
        'commission_amount',
        'estimated_days',
        'proposal_text',
        'status',
        'responded_at',
        'rating',
        'rating_comment',
        'correlation_id',
    ];

    protected $casts = [
        'proposed_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'responded_at' => 'datetime',
        'rating' => 'float',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(FreelanceJob::class);
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(Freelancer::class);
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
