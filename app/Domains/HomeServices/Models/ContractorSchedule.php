<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ContractorSchedule extends Model
{
    protected $table = 'contractor_schedules';
    protected $fillable = ['tenant_id', 'contractor_id', 'day_of_week', 'start_time', 'end_time', 'is_available', 'correlation_id'];
    protected $hidden = [];
    protected $casts = ['is_available' => 'boolean', 'start_time' => 'time', 'end_time' => 'time'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', fn($q) => $q->where('tenant_id', tenant('id')));
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function contractor(): BelongsTo { return $this->belongsTo(Contractor::class); }
}
