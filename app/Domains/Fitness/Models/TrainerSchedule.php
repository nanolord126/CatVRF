declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fitness\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final /**
 * TrainerSchedule
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class TrainerSchedule extends Model
{
    protected $table = 'trainer_schedules';
    protected $fillable = ['tenant_id', 'trainer_id', 'day_of_week', 'start_time', 'end_time', 'is_available', 'correlation_id'];
    protected $casts = [
        'start_time' => 'time',
        'end_time' => 'time',
        'is_available' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id')));
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }
}
