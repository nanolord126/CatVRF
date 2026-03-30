<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ClassSchedule extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'class_schedules';
        protected $fillable = ['tenant_id', 'fitness_class_id', 'day_of_week', 'start_time', 'end_time', 'max_participants', 'current_participants', 'scheduled_at', 'is_cancelled', 'correlation_id'];
        protected $casts = [
            'max_participants' => 'integer',
            'current_participants' => 'integer',
            'scheduled_at' => 'datetime',
            'is_cancelled' => 'boolean',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id')));
        }

        public function tenant(): BelongsTo
        {
            return $this->belongsTo(Tenant::class);
        }

        public function fitnessClass(): BelongsTo
        {
            return $this->belongsTo(FitnessClass::class);
        }

        public function attendances(): HasMany
        {
            return $this->hasMany(Attendance::class);
        }
}
