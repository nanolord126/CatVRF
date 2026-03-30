<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Attendance extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
