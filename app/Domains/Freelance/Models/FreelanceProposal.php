declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Freelance\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final /**
 * FreelanceProposal
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FreelanceProposal extends BaseModel
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
