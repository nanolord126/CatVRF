<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TrainerSchedule extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
