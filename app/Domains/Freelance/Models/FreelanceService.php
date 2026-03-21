<?php declare(strict_types=1);

namespace App\Domains\Freelance\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FreelanceService extends BaseModel
{
    use SoftDeletes;

    protected $table = 'freelance_services';

    protected $fillable = [
        'tenant_id',
        'freelancer_id',
        'title',
        'description',
        'categories',
        'tags',
        'pricing_type',
        'hourly_rate',
        'starting_price',
        'estimated_days',
        'deliverables',
        'requirements',
        'status',
        'rating',
        'review_count',
        'total_orders',
        'correlation_id',
    ];

    protected $casts = [
        'categories' => 'json',
        'tags' => 'json',
        'deliverables' => 'json',
        'requirements' => 'json',
        'rating' => 'float',
    ];

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
