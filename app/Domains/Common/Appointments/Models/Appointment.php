<?php declare(strict_types=1);

namespace App\Domains\Common\Appointments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Appointment extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'appointments';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'bookable_type',
            'bookable_id',
            'client_id',
            'datetime_start',
            'datetime_end',
            'price_cents',
            'status',
            'payment_status',
            'correlation_id',
            'tags',
            'notes',
        ];

        protected $casts = [
            'tags' => 'json',
            'datetime_start' => 'datetime',
            'datetime_end' => 'datetime',
            'price_cents' => 'integer',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $appointment) {
                $appointment->uuid = $appointment->uuid ?? (string) Str::uuid();
                $appointment->status = $appointment->status ?? 'pending';
                $appointment->payment_status = $appointment->payment_status ?? 'unpaid';
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant('id')) {
                    $builder->where('tenant_id', tenant('id'));
                }
            });
        }

        public function bookable(): MorphTo
        {
            return $this->morphTo();
        }

        public function client(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'client_id');
        }

        public function scopePending(Builder $query): Builder
        {
            return $query->where('status', 'pending');
        }

        public function scopeConfirmed(Builder $query): Builder
        {
            return $query->where('status', 'confirmed');
        }

        public function scopeCompleted(Builder $query): Builder
        {
            return $query->where('status', 'completed');
        }
}
