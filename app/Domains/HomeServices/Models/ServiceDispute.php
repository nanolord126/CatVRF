declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Models;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final /**
 * ServiceDispute
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ServiceDispute extends Model
{
    protected $table = 'service_disputes';
    protected $fillable = ['tenant_id', 'job_id', 'initiator_id', 'type', 'description', 'status', 'resolution', 'resolved_by', 'refund_amount', 'evidence', 'resolved_at', 'correlation_id'];
    protected $hidden = [];
    protected $casts = ['evidence' => 'collection', 'refund_amount' => 'float', 'resolved_at' => 'datetime'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', fn($q) => $q->where('tenant_id', tenant('id')));
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function job(): BelongsTo { return $this->belongsTo(ServiceJob::class); }
    public function initiator(): BelongsTo { return $this->belongsTo(User::class, 'initiator_id'); }
}
