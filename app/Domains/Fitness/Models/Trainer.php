declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fitness\Models;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final /**
 * Trainer
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class Trainer extends Model
{
    use SoftDeletes;

    protected $table = 'trainers';
    protected $fillable = ['tenant_id', 'gym_id', 'user_id', 'full_name', 'bio', 'specializations', 'experience_years', 'hourly_rate', 'certification_url', 'rating', 'review_count', 'class_count', 'is_verified', 'is_active', 'correlation_id'];
    protected $casts = [
        'specializations' => 'collection',
        'experience_years' => 'integer',
        'hourly_rate' => 'float',
        'rating' => 'float',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id')));
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fitnessClasses(): HasMany
    {
        return $this->hasMany(FitnessClass::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(TrainerSchedule::class);
    }
}
