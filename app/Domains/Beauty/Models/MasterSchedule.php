<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MasterSchedule extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids;

        protected $table = 'master_schedules';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'master_id',
            'date',
            'slots',
            'blocked_hours',
            'is_day_off',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'date' => 'date',
            'slots' => 'array',
            'blocked_hours' => 'array',
            'is_day_off' => 'boolean',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
        }

        public function master(): BelongsTo
        {
            return $this->belongsTo(Master::class, 'master_id');
        }
}
