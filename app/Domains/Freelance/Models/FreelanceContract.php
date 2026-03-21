<?php declare(strict_types=1);

namespace App\Domains\Freelance\Models;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FreelanceContract extends BaseModel
{
    use SoftDeletes;

    protected $table = 'freelance_contracts';

    protected $fillable = [
        'tenant_id',
        'job_id',
        'freelancer_id',
        'client_id',
        'contract_number',
        'agreed_amount',
        'commission_amount',
        'duration_days',
        'payment_type',
        'milestone_count',
        'milestones',
        'status',
        'start_date',
        'end_date',
        'completed_at',
        'amount_paid',
        'amount_held_escrow',
        'terms',
        'correlation_id',
    ];

    protected $casts = [
        'agreed_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'milestones' => 'json',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'completed_at' => 'datetime',
        'amount_paid' => 'decimal:2',
        'amount_held_escrow' => 'decimal:2',
        'terms' => 'json',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(FreelanceJob::class);
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(Freelancer::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(FreelanceDeliverable::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(FreelanceMessage::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(FreelanceReview::class);
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
