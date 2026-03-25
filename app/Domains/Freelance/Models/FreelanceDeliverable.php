declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Freelance\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final /**
 * FreelanceDeliverable
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FreelanceDeliverable extends BaseModel
{
    use SoftDeletes;

    protected $table = 'freelance_deliverables';

    protected $fillable = [
        'tenant_id',
        'contract_id',
        'freelancer_id',
        'title',
        'description',
        'files',
        'status',
        'submitted_at',
        'approved_at',
        'revision_notes',
        'revision_count',
        'correlation_id',
    ];

    protected $casts = [
        'files' => 'json',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(FreelanceContract::class);
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
