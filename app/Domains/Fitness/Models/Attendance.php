declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fitness\Models;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final /**
 * Attendance
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class Attendance extends Model
{
    protected $table = 'attendances';
    protected $fillable = ['tenant_id', 'class_schedule_id', 'member_id', 'checked_in_at', 'checked_out_at', 'duration_minutes', 'status', 'performance_data', 'correlation_id'];
    protected $casts = [
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'duration_minutes' => 'integer',
        'performance_data' => 'collection',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id')));
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
